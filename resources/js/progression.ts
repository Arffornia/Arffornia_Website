import { mountProgressionGraph } from '../../packages/progression-graph/src/index.ts';
import type { GraphLayout, Locale, PlayerState, Snapshot } from '../../packages/progression-graph/src/index.ts';

interface PageConfig {
  mode: 'fixture' | 'api';
  modelUrl: string;
  stateUrl: string | null;
  iconsUrl: string;
  iconBase: string;
  locale: Locale;
  title: string | null;
  messages: { private: string; no_model: string };
}

interface SiteTheme {
  get(): 'dark' | 'light';
  set(theme: 'dark' | 'light'): void;
}

/** `GET /api/players/{uuid}/progression/state` (spec 017), the part the viewer reads. */
interface ApiState {
  open_stage: number;
  completed: { key: string }[];
  stages: { number: number; module: { item: string; delivered: number }[] }[];
}

class HttpError extends Error {
  readonly status: number;
  constructor(status: number, url: string) {
    super(`${url}: HTTP ${status}`);
    this.status = status;
  }
}

async function getJson<T>(url: string): Promise<T> {
  const response = await fetch(url, { headers: { Accept: 'application/json' } });
  if (!response.ok) throw new HttpError(response.status, url);
  return response.json() as Promise<T>;
}

/** The graph follows the site theme (components/themeToggle.blade.php); its own switch drives the site. */
function siteTheme(): SiteTheme | null {
  return (window as unknown as { ArfforniaTheme?: SiteTheme }).ArfforniaTheme ?? null;
}

function fromApi(state: ApiState): PlayerState {
  const open = state.stages.find((s) => s.number === state.open_stage);
  return {
    done: state.completed.map((c) => c.key),
    open_stage: state.open_stage,
    deliveries: (open?.module ?? []).map((m) => ({ item: m.item, delivered: m.delivered })),
  };
}

/**
 * Published snapshots do not carry the layout yet (the editor will compute it at publish, spec 018 §3).
 * Until then it is computed here once per version and kept in the browser.
 */
async function withLayout(snapshot: Snapshot): Promise<Snapshot & { layout: GraphLayout }> {
  if (snapshot.layout) return snapshot as Snapshot & { layout: GraphLayout };
  const key = `arffornia.progression.layout.v${snapshot.version}`;
  try {
    const cached = localStorage.getItem(key);
    if (cached) return { ...snapshot, layout: JSON.parse(cached) as GraphLayout };
  } catch {
    // Storage blocked: compute every time.
  }
  const { computeLayout } = await import('../../packages/progression-graph/src/layout.ts');
  const layout = await computeLayout(snapshot);
  try {
    localStorage.setItem(key, JSON.stringify(layout));
  } catch {
    // Quota or private mode: fine, it is only a cache.
  }
  return { ...snapshot, layout };
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

async function main() {
  const root = document.getElementById('progression-root');
  const config = (window as unknown as { ArfforniaProgression?: PageConfig }).ArfforniaProgression;
  if (!root || !config) return;

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
    const [laidOut, icons, player] = await Promise.all([withLayout(snapshot), getJson<Record<string, string>>(config.iconsUrl), loadState(config)]);
    const handle = mountProgressionGraph(root, {
      snapshot: laidOut,
      state: player.state,
      locale: config.locale,
      title: player.hidden ? `${config.title ?? ''} · ${config.messages.private}` : (config.title ?? undefined),
      theme: siteTheme()?.get() ?? (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light'),
      icon: (item) => (icons[item] ? config.iconBase + icons[item].split('/').map(encodeURIComponent).join('/') : null),
      onThemeChange: (theme) => siteTheme()?.set(theme),
    });
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
