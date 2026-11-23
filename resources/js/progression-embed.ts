import { mountProgressionGraph } from '../../packages/progression-graph/src/index.ts';
import type { GraphLayout, Locale, NodeState, PlayerState, Snapshot, ViewHandle, ViewTransform } from '../../packages/progression-graph/src/index.ts';
import { fromApi, getJson, HttpError, withLayout, type ApiState } from './progression-data.ts';
import { iconResolver, type IconIndex } from './progression-icons.ts';

/**
 * The progression viewer in the game's embedded browser (spec 018 D2 a). The mod answers
 * `window.arfforniaQuery` (a CEF message router): `session` gives the player's read-only token,
 * `target` asks the game server to target a milestone, which checks it before the website does.
 * The token never appears in a URL and is never logged.
 */

interface PageConfig {
  mode: 'fixture' | 'api';
  modelUrl: string;
  iconsUrl: string;
  iconBase: string;
  stateUrl: string;
  locale: Locale;
  messages: Record<'no_model' | 'no_session' | 'target_set' | 'target_clear' | 'target_done' | 'target_locked' | 'target_pending' | 'target_refused', string>;
}

interface Session {
  token: string;
  expires_at: number;
}

interface CefQuery {
  request: string;
  persistent?: boolean;
  onSuccess: (response: string) => void;
  onFailure: (code: number, message: string) => void;
}

type Bridge = (query: CefQuery) => number;

function bridge(): Bridge | null {
  return (window as unknown as { arfforniaQuery?: Bridge }).arfforniaQuery ?? null;
}

function ask<T>(query: Bridge, request: Record<string, unknown>): Promise<T> {
  return new Promise((resolve, reject) => {
    query({
      request: JSON.stringify(request),
      onSuccess: (response) => resolve(JSON.parse(response || 'null') as T),
      onFailure: (code, message) => reject(new Error(`bridge ${String(request.type)}: ${code} ${message}`)),
    });
  });
}

async function main() {
  const root = document.getElementById('progression-root');
  const config = (window as unknown as { ArfforniaProgression?: PageConfig }).ArfforniaProgression;
  if (!root || !config) return;
  const query = bridge();
  let snapshot: Snapshot & { layout: GraphLayout };
  let icon: (item: string) => string | null;
  try {
    const [model, iconIndex] = await Promise.all([getJson<Snapshot>(config.modelUrl), getJson<IconIndex>(config.iconsUrl)]);
    snapshot = await withLayout(model);
    icon = iconResolver(iconIndex, config.iconBase);
  } catch (error) {
    root.dataset.ready = 'error';
    root.querySelector('[data-loading]')?.setAttribute('hidden', '');
    const message = error instanceof HttpError && error.status === 404 ? root.querySelector('[data-message]') : root.querySelector('[data-error]');
    if (message && error instanceof HttpError && error.status === 404) message.textContent = config.messages.no_model;
    message?.removeAttribute('hidden');
    console.error(String(error));
    return;
  }

  let session: Session | null = null;
  let handle: ViewHandle | null = null;
  let view: ViewTransform | null = null;
  let selected: string | null = null;
  let theme: 'dark' | 'light' = 'dark';
  let state: PlayerState | null = null;
  let target: string | null = null;
  let pending: string | null = null;
  let refusedKey: string | null = null;
  let loads = 0;

  async function token(renew: boolean): Promise<string | null> {
    if (!query) return null;
    if (renew || !session || session.expires_at - Date.now() / 1000 < 60) {
      session = await ask<Session>(query, { type: 'session', renew });
    }
    return session.token;
  }

  async function fetchState(): Promise<ApiState | null> {
    for (const renew of [false, true]) {
      const bearer = await token(renew);
      if (!bearer) return null;
      try {
        return await getJson<ApiState>(config!.stateUrl, { Authorization: `Bearer ${bearer}` });
      } catch (error) {
        if (error instanceof HttpError && error.status === 401 && !renew) continue;
        if (error instanceof HttpError && error.status === 404) return null;
        throw error;
      }
    }
    return null;
  }

  function targetButton(key: string, nodeState: NodeState): HTMLElement | null {
    if (!state) return null;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'embed-target';
    const m = config!.messages;
    if (pending === key) {
      button.textContent = m.target_pending;
      button.disabled = true;
    } else if (nodeState === 'done') {
      button.textContent = m.target_done;
      button.disabled = true;
    } else if (key === target) {
      button.textContent = m.target_clear;
      button.classList.add('is-on');
      button.addEventListener('click', () => void setTarget(key, null));
    } else if (nodeState === 'available') {
      button.textContent = m.target_set;
      button.addEventListener('click', () => void setTarget(key, key));
    } else {
      button.textContent = m.target_locked;
      button.disabled = true;
    }
    if (refusedKey === key) {
      const wrap = document.createElement('div');
      const note = document.createElement('p');
      note.className = 'embed-target__refused';
      note.setAttribute('role', 'alert');
      note.textContent = m.target_refused;
      wrap.append(button, note);
      return wrap;
    }
    return button;
  }

  function mount() {
    if (handle) view = handle.getView();
    handle?.destroy();
    handle = mountProgressionGraph(root!, {
      snapshot,
      state,
      locale: config!.locale,
      theme,
      icon,
      deepLink: false,
      notice: query ? null : { text: config!.messages.no_session },
      target,
      panelAction: targetButton,
      onSelect: (key) => {
        selected = key;
      },
      onThemeChange: (next) => {
        theme = next;
        handle?.setTheme(next);
      },
    });
    if (view) handle.setView(view);
    if (selected) handle.select(selected);
  }

  async function refresh() {
    const load = ++loads;
    try {
      const raw = await fetchState();
      if (load !== loads) return;
      state = raw ? fromApi(raw) : null;
      target = raw?.target ?? null;
    } catch (error) {
      console.error(String(error));
    }
    mount();
    root!.dataset.ready = 'true';
  }

  async function setTarget(key: string, milestone: string | null) {
    if (!query || pending) return;
    pending = key;
    refusedKey = null;
    mount();
    try {
      await ask(query, { type: 'target', milestone });
    } catch (error) {
      refusedKey = key;
      console.error(String(error));
    }
    pending = null;
    await refresh();
  }

  window.addEventListener('arffornia:shown', () => void refresh());
  await refresh();
}

main();
