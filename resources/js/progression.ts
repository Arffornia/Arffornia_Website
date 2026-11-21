import { mountProgressionGraph } from '../../packages/progression-graph/src/index.ts';
import type { GraphLayout, Locale, PlayerState, Snapshot } from '../../packages/progression-graph/src/index.ts';

interface PageConfig {
  modelUrl: string;
  stateUrl: string | null;
  iconsUrl: string;
  iconBase: string;
  locale: Locale;
  title: string | null;
}

const THEME_KEY = 'arffornia.progression.theme';

async function getJson<T>(url: string): Promise<T> {
  const response = await fetch(url, { headers: { Accept: 'application/json' } });
  if (!response.ok) throw new Error(`${url}: HTTP ${response.status}`);
  return response.json() as Promise<T>;
}

function storedTheme(): 'dark' | 'light' {
  try {
    return localStorage.getItem(THEME_KEY) === 'light' ? 'light' : 'dark';
  } catch {
    return 'dark';
  }
}

async function main() {
  const root = document.getElementById('progression-root');
  const config = (window as unknown as { ArfforniaProgression?: PageConfig }).ArfforniaProgression;
  if (!root || !config) return;

  try {
    const [snapshot, icons, state] = await Promise.all([
      getJson<Snapshot>(config.modelUrl),
      getJson<Record<string, string>>(config.iconsUrl),
      config.stateUrl ? getJson<PlayerState>(config.stateUrl) : Promise.resolve(null),
    ]);
    if (!snapshot.layout) throw new Error('snapshot has no layout');
    mountProgressionGraph(root, {
      snapshot: snapshot as Snapshot & { layout: GraphLayout },
      state,
      locale: config.locale,
      title: config.title ?? undefined,
      theme: storedTheme(),
      icon: (item) => (icons[item] ? config.iconBase + icons[item].split('/').map(encodeURIComponent).join('/') : null),
      onThemeChange: (theme) => {
        try {
          localStorage.setItem(THEME_KEY, theme);
        } catch {
          // Private mode: the choice simply is not remembered.
        }
      },
    });
    root.dataset.ready = 'true';
  } catch (error) {
    root.dataset.ready = 'error';
    root.querySelector('[data-loading]')?.setAttribute('hidden', '');
    root.querySelector('[data-error]')?.removeAttribute('hidden');
    console.error(error);
  }
}

main();
