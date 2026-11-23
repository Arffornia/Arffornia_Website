import type { GraphLayout, PlayerState, Snapshot } from '../../packages/progression-graph/src/index.ts';

/** `GET /api/players/{uuid}/progression/state` (spec 017), the part the viewer reads. */
export interface ApiState {
  open_stage: number;
  target?: string | null;
  player?: { name: string };
  completed: { key: string }[];
  stages: { number: number; module: { item: string; delivered: number }[] }[];
}

export class HttpError extends Error {
  readonly status: number;
  constructor(status: number, url: string) {
    super(`${url}: HTTP ${status}`);
    this.status = status;
  }
}

export async function getJson<T>(url: string, headers: Record<string, string> = {}): Promise<T> {
  const response = await fetch(url, { headers: { Accept: 'application/json', ...headers } });
  if (!response.ok) throw new HttpError(response.status, url);
  return response.json() as Promise<T>;
}

export function fromApi(state: ApiState): PlayerState {
  const open = state.stages.find((s) => s.number === state.open_stage);
  return {
    ...(state.player ? { player: { name: state.player.name } } : {}),
    done: state.completed.map((c) => c.key),
    open_stage: state.open_stage,
    deliveries: (open?.module ?? []).map((m) => ({ item: m.item, delivered: m.delivered })),
  };
}

/**
 * Published snapshots do not carry the layout yet (the editor will compute it at publish, spec 018 §3).
 * Until then it is computed here once per version and kept in the browser.
 */
export async function withLayout(snapshot: Snapshot): Promise<Snapshot & { layout: GraphLayout }> {
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
