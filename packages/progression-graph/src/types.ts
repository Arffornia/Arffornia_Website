export type Locale = 'en' | 'fr';

export interface Text {
  en: string;
  fr?: string;
}

export interface ItemCount {
  item: string;
  count: number;
}

export interface Stage {
  number: number;
  name: Text;
  description?: Text;
  threshold: number;
  module: ItemCount[];
}

export interface Graph {
  key: string;
  name: Text;
  mod_id?: string | null;
  branch: 'tech' | 'magic' | 'neutral';
  icon_item?: string;
}

export type Validation = { type: 'deliver'; items: ItemCount[] };

export interface Unlock {
  item: string;
  gate_mode: 'machine' | 'ingredient' | 'crafting';
  recipes_to_ban?: string[];
  recipe?: unknown;
  shop_price?: number | null;
}

export interface Milestone {
  key: string;
  graph: string;
  stage: number;
  title: Text;
  description?: Text;
  icon_type?: string;
  requires: string[];
  validation: Validation | null;
  unlocks: Unlock[];
  effort?: number | null;
  power?: Record<string, unknown> | null;
  source: 'manual' | 'tool' | 'legacy';
  layout?: { x?: number; y?: number };
}

export interface Snapshot {
  schema_version: 1;
  version: number;
  status: 'draft' | 'published' | 'archived';
  source?: string;
  stages: Stage[];
  graphs: Graph[];
  milestones: Milestone[];
  layout?: GraphLayout;
}

export interface Point {
  x: number;
  y: number;
}

export interface LayoutEdge {
  from: string;
  to: string;
  points: Point[];
  junctions?: Point[];
}

export interface LayoutGraph {
  height: number;
  nodes: Record<string, Point>;
  edges: LayoutEdge[];
}

/**
 * Computed once per snapshot (spec 018 §3) and never by a client at view time.
 * Node positions are relative to their graph row; stage columns are shared by every row.
 */
export interface GraphLayout {
  format: 'arffornia.progression-layout/1';
  node: { width: number; height: number; tile: number };
  columns: { stage: number; x: number; width: number }[];
  graphs: Record<string, LayoutGraph>;
}

export interface Delivery {
  item: string;
  delivered: number;
}

/** Subset of `GET /api/progressions/{id}/state` (spec 017) that the viewer reads. */
export interface PlayerState {
  player?: { name: string; uuid?: string };
  done: string[];
  open_stage: number;
  deliveries?: Delivery[];
}

export type NodeState = 'done' | 'available' | 'locked' | 'neutral';
