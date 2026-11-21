import ELK from 'elkjs/lib/elk.bundled.js';
import { splitLinks } from './state.ts';
import type { GraphLayout, LayoutEdge, LayoutGraph, Point, Snapshot } from './types.ts';

export const NODE = { width: 112, height: 84, tile: 48 } as const;
/** Links attach to the middle of the icon tile, not of the caption block. */
export const PORT_Y = NODE.tile / 2;
const ROW_HEAD = 40;
const ROW_FOOT = 16;
const COLUMN_PAD = 28;

const ELK_OPTIONS: Record<string, string> = {
  'elk.algorithm': 'layered',
  'elk.direction': 'RIGHT',
  'elk.edgeRouting': 'ORTHOGONAL',
  'elk.partitioning.activate': 'true',
  'elk.layered.nodePlacement.strategy': 'BRANDES_KOEPF',
  'elk.layered.nodePlacement.bk.fixedAlignment': 'BALANCED',
  'elk.layered.nodePlacement.bk.edgeStraightening': 'IMPROVE_STRAIGHTNESS',
  'elk.layered.mergeEdges': 'false',
  // Components laid out apart lose their partition: an isolated stage 6 milestone would land in column 1.
  'elk.separateConnectedComponents': 'false',
  'elk.layered.considerModelOrder.strategy': 'NODES_AND_EDGES',
  'elk.spacing.nodeNode': '22',
  'elk.layered.spacing.nodeNodeBetweenLayers': '56',
  'elk.layered.spacing.edgeNodeBetweenLayers': '18',
  'elk.layered.spacing.edgeEdgeBetweenLayers': '10',
  'elk.spacing.edgeEdge': '10',
  'elk.padding': '[top=0,left=0,bottom=0,right=0]',
};

interface ElkPort { id: string; x: number; y: number; width: number; height: number; layoutOptions: Record<string, string> }
interface ElkSection { startPoint: Point; endPoint: Point; bendPoints?: Point[] }
interface ElkOutEdge { id: string; sections?: ElkSection[]; junctionPoints?: Point[] }
interface ElkOutNode { id: string; x: number; y: number }
interface ElkOut { children: ElkOutNode[]; edges: ElkOutEdge[]; height: number }

interface RawGraph {
  key: string;
  nodes: Map<string, Point & { stage: number }>;
  edges: LayoutEdge[];
  height: number;
}

export async function computeLayout(snapshot: Snapshot): Promise<GraphLayout> {
  const elk = new ELK();
  const { forward } = splitLinks(snapshot);
  const raws: RawGraph[] = [];

  for (const graph of snapshot.graphs) {
    const milestones = snapshot.milestones.filter((m) => m.graph === graph.key);
    if (milestones.length === 0) {
      raws.push({ key: graph.key, nodes: new Map(), edges: [], height: 0 });
      continue;
    }
    const keys = new Set(milestones.map((m) => m.key));
    const links = forward.filter((l) => keys.has(l.to));
    const incoming = new Map<string, number>();
    const children = milestones.map((m) => {
      const ports: ElkPort[] = [
        { id: `${m.key}>out`, x: NODE.width, y: PORT_Y, width: 0, height: 0, layoutOptions: { 'elk.port.side': 'EAST' } },
      ];
      return {
        id: m.key,
        width: NODE.width,
        height: NODE.height,
        ports,
        layoutOptions: { 'elk.partitioning.partition': String(m.stage), 'elk.portConstraints': 'FIXED_POS' },
      };
    });
    const byId = new Map(children.map((c) => [c.id, c]));
    const edges = links.map((l, i) => {
      const n = incoming.get(l.to) ?? 0;
      incoming.set(l.to, n + 1);
      const port = `${l.to}<in${n}`;
      byId.get(l.to)!.ports.push({ id: port, x: 0, y: PORT_Y, width: 0, height: 0, layoutOptions: { 'elk.port.side': 'WEST' } });
      return { id: `e${i}`, sources: [`${l.from}>out`], targets: [port], link: l };
    });

    const out = (await elk.layout({
      id: graph.key,
      layoutOptions: ELK_OPTIONS,
      children,
      edges: edges.map(({ id, sources, targets }) => ({ id, sources, targets })),
    })) as unknown as ElkOut;

    const stageOf = new Map(milestones.map((m) => [m.key, m.stage]));
    const nodes = new Map(out.children.map((c) => [c.id, { x: c.x, y: c.y + ROW_HEAD, stage: stageOf.get(c.id)! }]));
    const outEdges: LayoutEdge[] = out.edges.map((e, i) => {
      const section = e.sections?.[0];
      const points = section ? [section.startPoint, ...(section.bendPoints ?? []), section.endPoint] : [];
      return {
        from: edges[i].link.from,
        to: edges[i].link.to,
        points: points.map((p) => ({ x: p.x, y: p.y + ROW_HEAD })),
        junctions: (e.junctionPoints ?? []).map((p) => ({ x: p.x, y: p.y + ROW_HEAD })),
      };
    });
    raws.push({ key: graph.key, nodes, edges: outEdges, height: out.height + ROW_HEAD + ROW_FOOT });
  }

  return alignColumns(snapshot, raws);
}

/**
 * ELK lays each graph out on its own, so a stage spans different x ranges per graph. Every graph is
 * remapped with a monotonic piecewise-linear function of x onto shared stage columns: inside a stage
 * it is a translation, between stages a stretch, so orthogonal links stay orthogonal and never cross
 * a node they did not cross before.
 */
function alignColumns(snapshot: Snapshot, raws: RawGraph[]): GraphLayout {
  const stages = [...snapshot.stages].map((s) => s.number).sort((a, b) => a - b);
  const extent = (raw: RawGraph, stage: number) => {
    const xs = [...raw.nodes.values()].filter((n) => n.stage === stage).map((n) => n.x);
    return xs.length ? { min: Math.min(...xs), max: Math.max(...xs) + NODE.width } : null;
  };
  const columns: GraphLayout['columns'] = [];
  let x = 0;
  for (const stage of stages) {
    const widest = Math.max(NODE.width, ...raws.map((r) => extent(r, stage)).filter((e) => e).map((e) => e!.max - e!.min));
    const width = widest + 2 * COLUMN_PAD;
    columns.push({ stage, x, width });
    x += width;
  }

  const graphs: Record<string, LayoutGraph> = {};
  for (const raw of raws) {
    const anchors: { from: number; to: number }[] = [];
    for (const col of columns) {
      const e = extent(raw, col.stage);
      if (!e) continue;
      const left = col.x + (col.width - (e.max - e.min)) / 2;
      anchors.push({ from: e.min, to: left }, { from: e.max, to: left + (e.max - e.min) });
    }
    const map = (v: number): number => {
      if (anchors.length === 0) return v;
      if (v <= anchors[0].from) return anchors[0].to - (anchors[0].from - v);
      for (let i = 1; i < anchors.length; i++) {
        const a = anchors[i - 1], b = anchors[i];
        if (v <= b.from) return b.from === a.from ? a.to : a.to + ((v - a.from) * (b.to - a.to)) / (b.from - a.from);
      }
      const last = anchors[anchors.length - 1];
      return last.to + (v - last.from);
    };
    const nodes: Record<string, Point> = {};
    for (const [key, n] of raw.nodes) nodes[key] = { x: round(map(n.x)), y: round(n.y) };
    graphs[raw.key] = {
      height: round(raw.height),
      nodes,
      edges: raw.edges.map((e) => ({
        from: e.from,
        to: e.to,
        points: e.points.map((p) => ({ x: round(map(p.x)), y: round(p.y) })),
        junctions: e.junctions?.map((p) => ({ x: round(map(p.x)), y: round(p.y) })),
      })),
    };
  }
  return { format: 'arffornia.progression-layout/1', node: { ...NODE }, columns, graphs };
}

const round = (v: number) => Math.round(v * 10) / 10;
