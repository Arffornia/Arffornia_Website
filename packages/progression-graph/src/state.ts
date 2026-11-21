import type { Milestone, NodeState, PlayerState, Snapshot, Stage } from './types.ts';

export interface LinkSets {
  /** Requirement inside the same graph, at the same or an earlier stage: drawn by the layout. */
  forward: { from: string; to: string }[];
  /** Requirement at a later stage than the milestone: a data error, drawn as an overlay. */
  backward: { from: string; to: string }[];
  /** Requirement in another graph: shown on the node and in the detail panel. */
  crossGraph: { from: string; to: string }[];
}

export function splitLinks(snapshot: Snapshot): LinkSets {
  const byKey = new Map(snapshot.milestones.map((m) => [m.key, m]));
  const sets: LinkSets = { forward: [], backward: [], crossGraph: [] };
  for (const milestone of snapshot.milestones) {
    for (const key of milestone.requires) {
      const required = byKey.get(key);
      if (!required) continue;
      const link = { from: key, to: milestone.key };
      if (required.graph !== milestone.graph) sets.crossGraph.push(link);
      else if (required.stage > milestone.stage) sets.backward.push(link);
      else sets.forward.push(link);
    }
  }
  return sets;
}

export function nodeStates(snapshot: Snapshot, state: PlayerState | null): Map<string, NodeState> {
  const states = new Map<string, NodeState>();
  if (!state) {
    for (const m of snapshot.milestones) states.set(m.key, 'neutral');
    return states;
  }
  const done = new Set(state.done);
  const known = new Set(snapshot.milestones.map((m) => m.key));
  for (const m of snapshot.milestones) {
    if (done.has(m.key)) states.set(m.key, 'done');
    else if (m.stage <= state.open_stage && m.requires.every((k) => !known.has(k) || done.has(k))) states.set(m.key, 'available');
    else states.set(m.key, 'locked');
  }
  return states;
}

export interface StageProgress {
  stage: Stage;
  done: number;
  total: number;
  needed: number;
  thresholdMet: boolean;
}

export function stageProgress(snapshot: Snapshot, state: PlayerState, stageNumber = state.open_stage): StageProgress | null {
  const stage = snapshot.stages.find((s) => s.number === stageNumber);
  if (!stage) return null;
  const done = new Set(state.done);
  const inStage = snapshot.milestones.filter((m) => m.stage === stageNumber);
  const doneCount = inStage.filter((m) => done.has(m.key)).length;
  const needed = Math.ceil(inStage.length * stage.threshold);
  return { stage, done: doneCount, total: inStage.length, needed, thresholdMet: doneCount >= needed };
}

export function moduleProgress(stage: Stage, state: PlayerState): { item: string; need: number; have: number }[] {
  const delivered = new Map((state.deliveries ?? []).map((d) => [d.item, d.delivered]));
  return stage.module.map((m) => ({ item: m.item, need: m.count, have: Math.min(m.count, delivered.get(m.item) ?? 0) }));
}

export function dependents(snapshot: Snapshot): Map<string, Milestone[]> {
  const out = new Map<string, Milestone[]>();
  for (const m of snapshot.milestones) {
    for (const k of m.requires) {
      const list = out.get(k) ?? [];
      list.push(m);
      out.set(k, list);
    }
  }
  return out;
}

/** Every milestone reachable by following `requires` (upstream) or dependents (downstream), the start excluded. */
export function lineage(snapshot: Snapshot): { upstream(key: string): Set<string>; downstream(key: string): Set<string> } {
  const byKey = new Map(snapshot.milestones.map((m) => [m.key, m]));
  const children = new Map<string, string[]>();
  for (const m of snapshot.milestones) {
    for (const k of m.requires) {
      if (!byKey.has(k)) continue;
      const list = children.get(k) ?? [];
      list.push(m.key);
      children.set(k, list);
    }
  }
  const walk = (start: string, next: (key: string) => readonly string[]) => {
    const seen = new Set<string>();
    const stack = [...next(start)];
    while (stack.length) {
      const key = stack.pop()!;
      if (seen.has(key) || key === start || !byKey.has(key)) continue;
      seen.add(key);
      stack.push(...next(key));
    }
    return seen;
  };
  const up = new Map<string, Set<string>>();
  const down = new Map<string, Set<string>>();
  return {
    upstream(key) {
      if (!up.has(key)) up.set(key, walk(key, (k) => byKey.get(k)?.requires ?? []));
      return up.get(key)!;
    },
    downstream(key) {
      if (!down.has(key)) down.set(key, walk(key, (k) => children.get(k) ?? []));
      return down.get(key)!;
    },
  };
}

/** Milestones still to do before `key`, in the order a player can do them (stage, then dependency depth). */
export function pathTo(snapshot: Snapshot, state: PlayerState, key: string): Milestone[] {
  const byKey = new Map(snapshot.milestones.map((m) => [m.key, m]));
  const done = new Set(state.done);
  const todo = [...lineage(snapshot).upstream(key)].filter((k) => !done.has(k)).map((k) => byKey.get(k)!);
  const depth = new Map<string, number>();
  const depthOf = (m: Milestone, guard = new Set<string>()): number => {
    if (depth.has(m.key)) return depth.get(m.key)!;
    if (guard.has(m.key)) return 0;
    guard.add(m.key);
    const d = 1 + Math.max(0, ...m.requires.map((k) => byKey.get(k)).filter((x): x is Milestone => !!x && !done.has(x.key)).map((x) => depthOf(x, guard)));
    depth.set(m.key, d);
    return d;
  };
  return todo.sort((a, b) => a.stage - b.stage || depthOf(a) - depthOf(b) || a.key.localeCompare(b.key));
}
