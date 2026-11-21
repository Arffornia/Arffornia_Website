import type { Milestone, Snapshot } from '../src/types.ts';

export function milestone(key: string, stage: number, requires: string[] = [], graph = 'tech'): Milestone {
  return { key: `${graph}/${key}`, graph, stage, title: { en: key }, requires: requires.map((r) => (r.includes('/') ? r : `${graph}/${r}`)), validation: null, unlocks: [], source: 'manual' };
}

export function snapshot(milestones: Milestone[], graphs = ['tech']): Snapshot {
  const stages = [...new Set(milestones.map((m) => m.stage))].sort((a, b) => a - b);
  return {
    schema_version: 1,
    version: 1,
    status: 'published',
    stages: Array.from({ length: Math.max(...stages) }, (_, i) => ({ number: i + 1, name: { en: `S${i + 1}` }, threshold: 0.8, module: [] })),
    graphs: graphs.map((key) => ({ key, name: { en: key }, branch: 'tech' as const })),
    milestones,
  };
}
