import { mountProgressionGraph, nodeStates } from '../../packages/progression-graph/src/index.ts';
import type { Locale, PlayerState, Snapshot, ViewHandle } from '../../packages/progression-graph/src/index.ts';
import { fromApi, getJson, HttpError, withLayout, type ApiState } from './progression-data.ts';
import { iconResolver, type IconIndex } from './progression-icons.ts';

interface PageConfig {
  mode: 'fixture' | 'api';
  modelUrl: string;
  stateUrl: string | null;
  iconsUrl: string;
  iconBase: string;
  locale: Locale;
  title: string | null;
  messages: { private: string; no_model: string; next_title: string; next_empty: string; privacy_failed: string };
  notice: { text: string; action?: { label: string; href: string } } | null;
}

interface SiteTheme {
  get(): 'dark' | 'light';
  set(theme: 'dark' | 'light'): void;
}

/** The graph follows the site theme (components/themeToggle.blade.php); its own switch drives the site. */
function siteTheme(): SiteTheme | null {
  return (window as unknown as { ArfforniaTheme?: SiteTheme }).ArfforniaTheme ?? null;
}

function showMessage(root: HTMLElement, text: string) {
  root.querySelector('[data-loading]')?.setAttribute('hidden', '');
  const message = root.querySelector('[data-message]');
  if (message) {
    message.textContent = text;
    message.removeAttribute('hidden');
  }
}

async function loadState(config: PageConfig): Promise<{ state: PlayerState | null; hidden: boolean }> {
  if (!config.stateUrl) return { state: null, hidden: false };
  try {
    const raw = await getJson<ApiState | PlayerState>(config.stateUrl);
    return { state: config.mode === 'api' ? fromApi(raw as ApiState) : (raw as PlayerState), hidden: false };
  } catch (error) {
    // A private progression answers exactly like a missing one: show the tree without a player.
    if (error instanceof HttpError && error.status === 404) return { state: null, hidden: true };
    throw error;
  }
}

/** Spec 019 player view: what the player can do now, the available milestones sorted by stage. */
function showNext(snapshot: Snapshot, state: PlayerState, config: PageConfig, handle: ViewHandle) {
  const box = document.querySelector<HTMLElement>('[data-progression-side] [data-next]');
  const list = box?.querySelector('ol');
  if (!box || !list) return;
  const states = nodeStates(snapshot, state);
  const available = snapshot.milestones
    .filter((m) => states.get(m.key) === 'available')
    .map((m) => ({ key: m.key, stage: m.stage, title: (config.locale === 'fr' ? m.title.fr : undefined) ?? m.title.en }))
    .sort((a, b) => a.stage - b.stage || a.title.localeCompare(b.title));
  list.replaceChildren();
  if (available.length === 0) {
    const empty = document.createElement('li');
    empty.className = 'is-empty';
    empty.textContent = config.messages.next_empty;
    list.append(empty);
  }
  for (const milestone of available) {
    const item = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.key = milestone.key;
    const stage = document.createElement('span');
    stage.className = 'stage';
    stage.textContent = String(milestone.stage);
    button.append(stage, document.createTextNode(milestone.title));
    button.addEventListener('click', () => handle.focus(milestone.key));
    item.append(button);
    list.append(item);
  }
  box.removeAttribute('hidden');
}

/** The public/private switch (spec 019 Q4); the page reverts it when the site refuses the change. */
function bindPrivacy(config: PageConfig) {
  const input = document.querySelector<HTMLInputElement>('[data-privacy-switch]');
  if (!input) return;
  input.addEventListener('change', async () => {
    const wanted = input.checked;
    input.disabled = true;
    try {
      const response = await fetch(input.dataset.url ?? '', {
        method: 'PUT',
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': input.dataset.csrf ?? '' },
        body: JSON.stringify({ public: wanted }),
      });
      if (!response.ok) throw new HttpError(response.status, input.dataset.url ?? '');
      input.checked = ((await response.json()) as { public: boolean }).public;
    } catch (error) {
      input.checked = !wanted;
      const hint = document.querySelector('[data-privacy-hint]');
      if (hint) hint.textContent = config.messages.privacy_failed;
      console.error(error);
    } finally {
      input.disabled = false;
    }
  });
}

async function main() {
  const root = document.getElementById('progression-root');
  const config = (window as unknown as { ArfforniaProgression?: PageConfig }).ArfforniaProgression;
  if (!root || !config) return;
  bindPrivacy(config);

  try {
    let snapshot: Snapshot;
    try {
      snapshot = await getJson<Snapshot>(config.modelUrl);
    } catch (error) {
      if (error instanceof HttpError && error.status === 404) {
        showMessage(root, config.messages.no_model);
        root.dataset.ready = 'empty';
        return;
      }
      throw error;
    }
    const [laidOut, iconIndex, player] = await Promise.all([withLayout(snapshot), getJson<IconIndex>(config.iconsUrl), loadState(config)]);
    const icon = iconResolver(iconIndex, config.iconBase);
    const handle = mountProgressionGraph(root, {
      snapshot: laidOut,
      state: player.state,
      locale: config.locale,
      title: player.hidden ? `${config.title ?? ''} · ${config.messages.private}` : (config.title ?? undefined),
      theme: siteTheme()?.get() ?? (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light'),
      icon,
      onThemeChange: (theme) => siteTheme()?.set(theme),
      brand: false,
      searchButton: false,
      notice: config.notice,
    });
    if (player.state) showNext(laidOut, player.state, config, handle);
    const searchButton =document.querySelector<HTMLButtonElement>('[data-progression-search]');
    if (searchButton) {
      const shortcut = searchButton.querySelector('[data-shortcut]');
      if (shortcut) shortcut.textContent = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘K' : 'Ctrl K';
      searchButton.addEventListener('click', () => handle.openSearch());
    }
    window.addEventListener('arffornia:theme', (event) => handle.setTheme((event as CustomEvent<{ theme: 'dark' | 'light' }>).detail.theme));
    root.dataset.ready = 'true';
  } catch (error) {
    root.dataset.ready = 'error';
    root.querySelector('[data-loading]')?.setAttribute('hidden', '');
    root.querySelector('[data-error]')?.removeAttribute('hidden');
    console.error(error);
  }
}

main();
