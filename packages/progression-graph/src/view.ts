import { translator, type Translator } from './i18n.ts';
import { dependents, lineage, moduleProgress, nodeStates, pathTo, splitLinks, stageProgress } from './state.ts';
import { rank } from './search.ts';
import type { Graph, GraphLayout, Locale, Milestone, NodeState, PlayerState, Point, Snapshot } from './types.ts';

export interface ViewOptions {
  snapshot: Snapshot & { layout: GraphLayout };
  state?: PlayerState | null;
  locale?: Locale;
  theme?: 'dark' | 'light';
  /** URL of the 3D inventory render of an item, or null when there is none. */
  icon?: (item: string) => string | null;
  title?: string;
  /** Called when the viewer's own sun/moon button is used; the host page applies the theme. */
  onThemeChange?: (theme: 'dark' | 'light') => void;
  /** Keep the selected milestone in the URL hash (#m=<key>) so a view can be shared. Default true. */
  deepLink?: boolean;
}

export interface ViewHandle {
  select(key: string | null): void;
  focus(key: string): void;
  /** Applies a theme chosen by the host page, without calling onThemeChange back. */
  setTheme(theme: 'dark' | 'light'): void;
  openSearch(): void;
  fit(): void;
  destroy(): void;
}

const SVG = 'http://www.w3.org/2000/svg';
const STAGE_HEAD = 44;
const MIN_ZOOM = 0.08;
const MAX_ZOOM = 2.5;
let instances = 0;

type Attrs = Record<string, string | number>;

function svg<K extends keyof SVGElementTagNameMap>(tag: K, attrs: Attrs = {}, parent?: Element): SVGElementTagNameMap[K] {
  const node = document.createElementNS(SVG, tag);
  for (const [k, v] of Object.entries(attrs)) node.setAttribute(k, String(v));
  parent?.appendChild(node);
  return node;
}

function html<K extends keyof HTMLElementTagNameMap>(tag: K, className = '', parent?: Element, text?: string): HTMLElementTagNameMap[K] {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined) node.textContent = text;
  parent?.appendChild(node);
  return node;
}

/** A missing render must not show a broken image: swap in the fallback (initials) or nothing. */
function onMissing(img: HTMLImageElement | SVGImageElement, fallback?: () => Node) {
  img.addEventListener('error', () => img.replaceWith(...(fallback ? [fallback()] : [])), { once: true });
}

const ICONS = {
  plus: 'M12 5v14M5 12h14',
  minus: 'M5 12h14',
  fit: 'M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5',
  sun: 'M12 4V2M12 22v-2M4.9 4.9 3.5 3.5M20.5 20.5l-1.4-1.4M4 12H2M22 12h-2M4.9 19.1l-1.4 1.4M20.5 3.5l-1.4 1.4M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10z',
  moon: 'M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z',
  close: 'M6 6l12 12M18 6 6 18',
  search: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
  check: 'M5 12.5l4.5 4.5L19 7.5',
};

function iconSvg(path: string): SVGSVGElement {
  const s = svg('svg', { viewBox: '0 0 24 24', 'aria-hidden': 'true', class: 'arf-pg__glyph' });
  svg('path', { d: path }, s);
  return s;
}

export function humanItem(item: string): string {
  const path = item.replace(/^#/, '').split(':')[1] ?? item;
  const last = path.split('/').pop() ?? path;
  return last.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

/** Orthogonal polyline with rounded corners. */
export function roundedPath(points: Point[], radius = 7): string {
  if (points.length === 0) return '';
  let d = `M${points[0].x} ${points[0].y}`;
  for (let i = 1; i < points.length - 1; i++) {
    const p = points[i - 1], c = points[i], n = points[i + 1];
    const r = Math.min(radius, Math.hypot(c.x - p.x, c.y - p.y) / 2, Math.hypot(n.x - c.x, n.y - c.y) / 2);
    const a = { x: c.x - Math.sign(c.x - p.x) * r, y: c.y - Math.sign(c.y - p.y) * r };
    const b = { x: c.x + Math.sign(n.x - c.x) * r, y: c.y + Math.sign(n.y - c.y) * r };
    d += ` L${a.x} ${a.y} Q${c.x} ${c.y} ${b.x} ${b.y}`;
  }
  const last = points[points.length - 1];
  return `${d} L${last.x} ${last.y}`;
}

function wrap(text: string, maxChars: number, maxLines: number): string[] {
  const words = text.split(/\s+/).filter(Boolean);
  const lines: string[] = [];
  let line = '';
  for (const word of words) {
    const next = line ? `${line} ${word}` : word;
    if (next.length <= maxChars || !line) line = next;
    else {
      lines.push(line);
      line = word;
    }
  }
  if (line) lines.push(line);
  if (lines.length > maxLines) {
    const kept = lines.slice(0, maxLines);
    kept[maxLines - 1] = kept[maxLines - 1].slice(0, maxChars - 1).replace(/\s+$/, '') + '…';
    return kept;
  }
  return lines.map((l) => (l.length > maxChars + 4 ? l.slice(0, maxChars) + '…' : l));
}

export function mountProgressionGraph(container: HTMLElement, options: ViewOptions): ViewHandle {
  const { snapshot } = options;
  const layout = snapshot.layout;
  const state = options.state ?? null;
  const tr: Translator = translator(options.locale ?? 'en');
  const iconOf = options.icon ?? (() => null);
  const N = layout.node;
  const tileX = (N.width - N.tile) / 2;
  const markerPrefix = `arf-pg-${++instances}-arrow`;

  const byKey = new Map(snapshot.milestones.map((m) => [m.key, m]));
  const graphByKey = new Map(snapshot.graphs.map((g) => [g.key, g]));
  const states = nodeStates(snapshot, state);
  const links = splitLinks(snapshot);
  const children = dependents(snapshot);
  const family = lineage(snapshot);
  /** Later-stage requirements per milestone: data errors, shown as a badge and drawn only on hover or selection. */
  const backwardOf = new Map<string, string[]>();
  for (const l of links.backward) backwardOf.set(l.to, [...(backwardOf.get(l.to) ?? []), l.from]);
  const hidden = new Set<string>();
  let selected: string | null = null;
  let hovered: string | null = null;
  const reducedMotion = typeof matchMedia === 'function' && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /** The old editor gave every graph the grass block: show the graph's first unlocked item instead. */
  function graphIcon(g: Graph): string | null {
    if (g.icon_item && g.icon_item !== 'minecraft:grass_block') return iconOf(g.icon_item);
    const first = snapshot.milestones.filter((m) => m.graph === g.key && m.unlocks.length).sort((a, b) => a.stage - b.stage)[0];
    const item = first?.unlocks[0]?.item ?? g.icon_item;
    return item ? iconOf(item) : null;
  }

  container.replaceChildren();
  const root = html('div', 'arf-pg', container);
  root.dataset.theme = options.theme ?? 'dark';
  if (!state) root.classList.add('arf-pg--catalog');

  const top = html('header', 'arf-pg__top', root);
  const brand = html('div', 'arf-pg__brand', top);
  html('b', '', brand, tr.t('title'));
  html('span', '', brand, options.title ?? (state?.player?.name ?? tr.t('catalog')));
  renderHeader(top);

  const toolbar = html('div', 'arf-pg__toolbar', root);
  const chips = html('div', 'arf-pg__chips', toolbar);
  const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
  const search = html('button', 'arf-pg__search', toolbar);
  search.type = 'button';
  search.setAttribute('aria-label', `${tr.t('search_button')} (${isMac ? '⌘' : 'Ctrl'}+K)`);
  search.append(iconSvg(ICONS.search));
  html('span', '', search, tr.t('palette_placeholder'));
  html('kbd', '', search, isMac ? '⌘K' : 'Ctrl K');
  search.addEventListener('click', () => openPalette());

  const stage = html('div', 'arf-pg__stage', root);
  const canvas = svg('svg', { class: 'arf-pg__canvas', role: 'img', 'aria-label': tr.t('title') }, stage);
  const defs = svg('defs', {}, canvas);
  for (const kind of ['base', 'done', 'in', 'out', 'back']) {
    const m = svg('marker', { id: `${markerPrefix}-${kind}`, viewBox: '0 0 10 10', refX: 9, refY: 5, markerWidth: 6, markerHeight: 6, orient: 'auto-start-reverse' }, defs);
    svg('path', { d: 'M0 1 L9 5 L0 9 z', class: `arf-pg__arrow arf-pg__arrow--${kind}` }, m);
  }
  const world = svg('g', { class: 'arf-pg__world' }, canvas);

  const legend = html('div', 'arf-pg__legend', stage);
  const legendItems: [string, string][] = state
    ? [['done', tr.t('done')], ['available', tr.t('available')], ['locked', tr.t('locked')]]
    : [];
  legendItems.push(['in', tr.t('incoming')], ['out', tr.t('outgoing')]);
  if (links.backward.length) legendItems.push(['back', tr.t('backward')]);
  for (const [kind, label] of legendItems) {
    const item = html('span', `arf-pg__legend-item arf-pg__legend-item--${kind}`, legend);
    html('i', '', item);
    item.append(label);
  }

  const tools = html('div', 'arf-pg__tools', stage);
  const tool = (glyph: string, label: string, onClick: () => void) => {
    const b = html('button', 'arf-pg__tool', tools);
    b.type = 'button';
    b.title = label;
    b.setAttribute('aria-label', label);
    b.append(iconSvg(glyph));
    b.addEventListener('click', onClick);
    return b;
  };
  tool(ICONS.plus, tr.t('zoom_in'), () => zoomBy(1.25));
  tool(ICONS.minus, tr.t('zoom_out'), () => zoomBy(1 / 1.25));
  tool(ICONS.fit, tr.t('fit'), () => fit());
  const themeButton = tool(ICONS.sun, '', () => setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark'));
  themeButton.classList.add('arf-pg__tool--theme');
  syncThemeButton();

  const panel = html('aside', 'arf-pg__panel', stage);
  panel.hidden = true;

  // ---- graph chips ----
  const allChip = html('button', 'arf-pg__chip is-on', chips, tr.t('all'));
  allChip.type = 'button';
  allChip.addEventListener('click', () => {
    if (hidden.size === 0) snapshot.graphs.forEach((g) => hidden.add(g.key));
    else hidden.clear();
    syncChips();
    draw();
    fit();
  });
  const chipByGraph = new Map<string, HTMLButtonElement>();
  for (const g of snapshot.graphs) {
    if (!layout.graphs[g.key]?.height) continue;
    const chip = html('button', 'arf-pg__chip is-on', chips);
    chip.type = 'button';
    chip.dataset.graph = g.key;
    const src = graphIcon(g);
    if (src) {
      const img = html('img', '', chip);
      img.src = src;
      img.alt = '';
      onMissing(img);
    }
    chip.append(tr.text(g.name));
    chip.addEventListener('click', () => {
      if (hidden.has(g.key)) hidden.delete(g.key);
      else hidden.add(g.key);
      syncChips();
      draw();
      fit();
    });
    chipByGraph.set(g.key, chip);
  }
  function syncChips() {
    allChip.classList.toggle('is-on', hidden.size === 0);
    for (const [key, chip] of chipByGraph) {
      chip.classList.toggle('is-on', !hidden.has(key));
      chip.setAttribute('aria-pressed', String(!hidden.has(key)));
    }
  }

  // ---- world ----
  const nodeEls = new Map<string, SVGGElement>();
  const edgeEls: { from: string; to: string; el: SVGPathElement }[] = [];
  const rowTop = new Map<string, number>();
  let worldHeight = 0;
  const worldWidth = layout.columns.reduce((w, c) => Math.max(w, c.x + c.width), 0);

  function draw() {
    world.replaceChildren();
    nodeEls.clear();
    edgeEls.length = 0;
    rowTop.clear();
    let y = STAGE_HEAD;
    for (const g of snapshot.graphs) {
      const lg = layout.graphs[g.key];
      if (!lg?.height || hidden.has(g.key)) continue;
      rowTop.set(g.key, y);
      y += lg.height;
    }
    worldHeight = y;

    const cols = svg('g', { class: 'arf-pg__cols' }, world);
    for (const [i, c] of layout.columns.entries()) {
      const current = state?.open_stage === c.stage;
      svg('rect', { class: `arf-pg__col${i % 2 ? ' is-odd' : ''}${current ? ' is-current' : ''}`, x: c.x, y: 0, width: c.width, height: worldHeight }, cols);
      const label = svg('text', { class: `arf-pg__col-label${current ? ' is-current' : ''}`, x: c.x + c.width / 2, y: 28, 'text-anchor': 'middle' }, cols);
      const s = snapshot.stages.find((st) => st.number === c.stage);
      label.textContent = tr.t('stage', { n: c.stage }).toUpperCase();
      if (s) svg('title', {}, label).textContent = tr.text(s.name);
    }

    const rows = svg('g', { class: 'arf-pg__rows' }, world);
    const edgesLayer = svg('g', { class: 'arf-pg__edges' }, world);
    overlayLayer = svg('g', { class: 'arf-pg__overlay' }, world);
    const nodesLayer = svg('g', { class: 'arf-pg__nodes' }, world);

    for (const [key, top] of rowTop) {
      const g = graphByKey.get(key)!;
      const lg = layout.graphs[key];
      svg('line', { class: 'arf-pg__row-rule', x1: 0, x2: worldWidth, y1: top, y2: top }, rows);
      const head = svg('g', { class: 'arf-pg__row-head', transform: `translate(12 ${top + 10})` }, rows);
      const src = graphIcon(g);
      if (src) onMissing(svg('image', { href: src, x: 0, y: 0, width: 22, height: 22 }, head));
      svg('text', { x: src ? 30 : 0, y: 16, class: 'arf-pg__row-label' }, head).textContent = tr.text(g.name);

      for (const e of lg.edges) {
        const from = lg.nodes[e.from], to = lg.nodes[e.to];
        if (!from || !to || e.points.length < 2) continue;
        const pts = e.points.map((p) => ({ x: p.x, y: p.y + top }));
        pts.unshift({ x: from.x + tileX + N.tile, y: pts[0].y });
        pts.push({ x: to.x + tileX, y: pts[pts.length - 1].y });
        const path = svg('path', { class: 'arf-pg__edge', d: roundedPath(pts) }, edgesLayer);
        path.dataset.from = e.from;
        path.dataset.to = e.to;
        edgeEls.push({ from: e.from, to: e.to, el: path });
        for (const j of e.junctions ?? []) svg('circle', { class: 'arf-pg__junction', cx: j.x, cy: j.y + top, r: 3 }, edgesLayer).dataset.from = e.from;
      }

      for (const [mk, pos] of Object.entries(lg.nodes)) {
        const m = byKey.get(mk);
        if (m) nodesLayer.append(drawNode(m, pos.x, pos.y + top));
      }
    }

    applyClasses();
  }

  let overlayLayer: SVGGElement | null = null;

  /** Orthogonal route around both milestones (left side, then a lane under their captions), like the other links. */
  function backwardPath(from: string, to: string): string {
    const a = nodeCenter(from), b = nodeCenter(to);
    const ax = a.x - N.width / 2 - 6, bx = b.x - N.width / 2 - 6;
    const lane = Math.max(a.y, b.y) - N.tile / 2 + N.height + 8;
    return roundedPath([
      { x: a.x - N.tile / 2, y: a.y },
      { x: ax, y: a.y },
      { x: ax, y: lane },
      { x: bx, y: lane },
      { x: bx, y: b.y },
      { x: b.x - N.tile / 2 - 1, y: b.y },
    ], 9);
  }

  function drawBackward(active: string | null) {
    if (!overlayLayer) return;
    overlayLayer.replaceChildren();
    if (!active) return;
    const pairs = links.backward.filter((l) => l.to === active || l.from === active);
    for (const l of pairs) {
      if (!nodeEls.has(l.from) || !nodeEls.has(l.to)) continue;
      svg('path', { class: 'arf-pg__edge arf-pg__edge--back', d: backwardPath(l.from, l.to), 'marker-end': `url(#${markerPrefix}-back)` }, overlayLayer);
    }
  }

  function nodeCenter(key: string): Point {
    const m = byKey.get(key)!;
    const top = rowTop.get(m.graph) ?? 0;
    const p = layout.graphs[m.graph].nodes[key];
    return { x: p.x + N.width / 2, y: p.y + top + N.tile / 2 };
  }

  function drawNode(m: Milestone, x: number, y: number): SVGGElement {
    const st: NodeState = states.get(m.key) ?? 'neutral';
    const g = svg('g', { class: `arf-pg__node is-${st}`, transform: `translate(${x} ${y})`, tabindex: 0, role: 'button' });
    g.dataset.key = m.key;
    g.setAttribute('aria-label', tr.text(m.title));
    svg('rect', { class: 'arf-pg__hit', x: 0, y: 0, width: N.width, height: N.height, rx: 10 }, g);
    svg('rect', { class: 'arf-pg__glow', x: tileX - 4, y: -4, width: N.tile + 8, height: N.tile + 8, rx: 13 }, g);
    svg('rect', { class: 'arf-pg__tile', x: tileX, y: 0, width: N.tile, height: N.tile, rx: 10 }, g);
    const item = m.unlocks[0]?.item ?? (m.validation ? (typeof m.validation.items[0] === 'string' ? m.validation.items[0] : m.validation.items[0]?.item) : undefined);
    const src = item ? iconOf(item) : null;
    const initials = () => {
      const t = svg('text', { class: 'arf-pg__fallback', x: N.width / 2, y: N.tile / 2 + 5, 'text-anchor': 'middle' });
      t.textContent = tr.text(m.title).slice(0, 2).toUpperCase();
      return t;
    };
    if (src) onMissing(svg('image', { class: 'arf-pg__icon', href: src, x: tileX + 6, y: 6, width: N.tile - 12, height: N.tile - 12 }, g), initials);
    else g.append(initials());
    if (st === 'done') {
      svg('circle', { class: 'arf-pg__badge', cx: tileX + N.tile - 2, cy: 2, r: 8 }, g);
      svg('path', { class: 'arf-pg__badge-mark', d: `M${tileX + N.tile - 6} 2.5 l3 3 l5 -6` }, g);
    }
    if (m.requires.some((k) => byKey.get(k) && byKey.get(k)!.graph !== m.graph)) {
      svg('circle', { class: 'arf-pg__cross', cx: tileX + 2, cy: 2, r: 5 }, g);
    }
    const errors = backwardOf.get(m.key);
    if (errors) {
      const warn = svg('g', { class: 'arf-pg__warn', transform: `translate(${tileX + 2} ${N.tile - 2})` }, g);
      svg('circle', { r: 8 }, warn);
      svg('text', { y: 4, 'text-anchor': 'middle' }, warn).textContent = '!';
      svg('title', {}, warn).textContent = errors.map((k) => backwardTip(k, m)).join('\n');
    }
    const lines = wrap(tr.text(m.title), 17, 2);
    const caption = svg('text', { class: 'arf-pg__caption', x: N.width / 2, y: N.tile + 16, 'text-anchor': 'middle' }, g);
    lines.forEach((line, i) => {
      svg('tspan', { x: N.width / 2, dy: i === 0 ? 0 : 13 }, caption).textContent = line;
    });
    svg('title', {}, g).textContent = tr.text(m.title);
    nodeEls.set(m.key, g);
    return g;
  }

  function backwardTip(requiredKey: string, m: Milestone): string {
    const r = byKey.get(requiredKey)!;
    return tr.t('backward_tip', { title: tr.text(r.title), from: r.stage, to: m.stage });
  }

  function applyClasses() {
    const active = hovered ?? selected;
    const up = active ? family.upstream(active) : null;
    const down = active ? family.downstream(active) : null;
    for (const [key, el] of nodeEls) {
      el.classList.toggle('is-selected', key === selected);
      el.classList.toggle('is-active', key === active);
      el.classList.toggle('is-upstream', !!up?.has(key));
      el.classList.toggle('is-downstream', !!down?.has(key));
    }
    for (const e of edgeEls) {
      const done = states.get(e.from) === 'done' && states.get(e.to) === 'done';
      const isIn = !!active && !!up && up.has(e.from) && (e.to === active || up.has(e.to));
      const isOut = !!active && !!down && down.has(e.to) && (e.from === active || down.has(e.from));
      e.el.classList.toggle('is-done', done);
      e.el.classList.toggle('is-in', isIn);
      e.el.classList.toggle('is-out', isOut);
      e.el.classList.toggle('is-faded', !!active && !isIn && !isOut);
      const kind = isIn ? 'in' : isOut ? 'out' : done ? 'done' : 'base';
      e.el.setAttribute('marker-end', `url(#${markerPrefix}-${kind})`);
    }
    root.classList.toggle('has-focus', !!active);
    root.classList.toggle('has-selection', !!selected);
    drawBackward(active);
  }

  // ---- header ----
  function renderHeader(parent: HTMLElement) {
    if (!state) return;
    const progress = stageProgress(snapshot, state);
    if (!progress) return;
    const next = progress.stage.number + 1;
    const pct = Math.round(progress.stage.threshold * 100);
    const w1 = html('section', 'arf-pg__widget', parent);
    const h1 = html('h2', '', w1);
    html('span', '', h1, `${tr.t('stage', { n: progress.stage.number })} · ${tr.text(progress.stage.name)}`);
    html('strong', '', h1, tr.t('milestones', { done: progress.done, total: progress.total }));
    const bar = html('div', 'arf-pg__bar', w1);
    html('i', '', bar).style.width = `${progress.total ? (100 * progress.done) / progress.total : 0}%`;
    html('span', 'arf-pg__tick', bar).style.left = `${pct}%`;
    const hint = html('p', 'arf-pg__hint', w1);
    const message = progress.thresholdMet
      ? tr.t('threshold_met', { pct, next })
      : tr.t('threshold_left', { left: progress.needed - progress.done, pct, next });
    for (const [i, part] of message.split(/\{\/?b\}/).entries()) {
      if (i % 2) html('b', '', hint, part);
      else hint.append(part);
    }

    const w2 = html('section', 'arf-pg__widget', parent);
    const h2 = html('h2', '', w2);
    html('span', '', h2, tr.t('elevator'));
    html('strong', '', h2, tr.t('elevator_phase', { n: progress.stage.number, total: snapshot.stages.length }));
    const phases = html('div', 'arf-pg__phases', w2);
    for (const s of snapshot.stages) {
      html('i', s.number < progress.stage.number ? 'is-done' : s.number === progress.stage.number ? 'is-current' : '', phases);
    }
    const mod = moduleProgress(progress.stage, state);
    if (mod.length === 0) {
      html('p', 'arf-pg__hint', w2, tr.t('module_empty'));
      return;
    }
    const list = html('div', 'arf-pg__deliveries', w2);
    for (const d of mod) {
      const row = html('div', `arf-pg__delivery${d.have >= d.need ? ' is-full' : ''}`, list);
      row.title = humanItem(d.item);
      const src = iconOf(d.item);
      if (src) {
        const img = html('img', '', row);
        img.src = src;
        img.alt = humanItem(d.item);
        onMissing(img, () => html('span', 'arf-pg__delivery-fallback', undefined, humanItem(d.item).slice(0, 2)));
      } else html('span', 'arf-pg__delivery-fallback', row, humanItem(d.item).slice(0, 2));
      html('span', 'arf-pg__count', row, `${tr.number(d.have)} / ${tr.number(d.need)}`);
      html('i', '', html('div', 'arf-pg__bar', row)).style.width = `${(100 * d.have) / d.need}%`;
    }
    html('p', 'arf-pg__hint', w2, tr.t('module_hint', { next }));
  }

  // ---- detail panel ----
  function itemRow(parent: HTMLElement, item: string, extra?: string) {
    const row = html('li', 'arf-pg__item', parent);
    const src = iconOf(item);
    if (src) {
      const img = html('img', '', row);
      img.src = src;
      img.alt = '';
      onMissing(img, () => html('span', 'arf-pg__item-fallback', undefined, humanItem(item).slice(0, 2)));
    } else html('span', 'arf-pg__item-fallback', row, humanItem(item).slice(0, 2));
    const label = html('span', '', row, humanItem(item));
    label.title = item;
    if (extra) html('small', '', row, extra);
  }

  function milestoneRow(parent: HTMLElement, m: Milestone, from: Milestone) {
    const row = html('li', '', parent);
    const b = html('button', `arf-pg__link is-${states.get(m.key) ?? 'neutral'}`, row, tr.text(m.title));
    b.type = 'button';
    if (m.graph !== from.graph) html('small', '', b, ` · ${tr.t('other_graph', { graph: tr.text(graphByKey.get(m.graph)?.name) })}`);
    b.addEventListener('click', () => focus(m.key));
  }

  function renderPanel() {
    panel.replaceChildren();
    const m = selected ? byKey.get(selected) : undefined;
    panel.hidden = !m;
    if (!m) return;
    const st = states.get(m.key) ?? 'neutral';
    const close = html('button', 'arf-pg__close', panel);
    close.type = 'button';
    close.title = tr.t('close');
    close.setAttribute('aria-label', tr.t('close'));
    close.append(iconSvg(ICONS.close));
    close.addEventListener('click', () => select(null));

    const head = html('div', 'arf-pg__panel-head', panel);
    const tile = html('div', `arf-pg__panel-tile is-${st}`, head);
    const item = m.unlocks[0]?.item;
    const src = item ? iconOf(item) : null;
    if (src) {
      const img = html('img', '', tile);
      img.src = src;
      img.alt = '';
      onMissing(img);
    }
    const titles = html('div', '', head);
    html('h3', '', titles, tr.text(m.title));
    html('p', 'arf-pg__meta', titles, `${tr.text(graphByKey.get(m.graph)?.name)} · ${tr.t('stage_of', { n: m.stage })}`);
    if (st !== 'neutral') html('span', `arf-pg__state is-${st}`, titles, tr.t(st));
    if (m.description) html('p', 'arf-pg__desc', panel, tr.text(m.description));

    const errors = backwardOf.get(m.key);
    if (errors) {
      html('h4', 'arf-pg__errors-title', panel, tr.t('data_errors'));
      const list = html('ul', 'arf-pg__list arf-pg__errors', panel);
      for (const k of errors) html('li', '', list, backwardTip(k, m));
    }

    const section = (title: string) => {
      html('h4', '', panel, title);
      return html('ul', 'arf-pg__list', panel);
    };
    const v = m.validation;
    if (!v) html('p', 'arf-pg__hint', panel, tr.t('validate_none'));
    else {
      const list = section(tr.t(v.type === 'deliver' ? 'validate_deliver' : v.type === 'craft' ? 'validate_craft' : 'validate_obtain'));
      for (const i of v.items) {
        if (typeof i === 'string') itemRow(list, i);
        else itemRow(list, i.item, `× ${tr.number(i.count)}`);
      }
    }
    const unlocks = section(tr.t('unlocks'));
    if (m.unlocks.length === 0) html('li', 'arf-pg__none', unlocks, tr.t('nothing'));
    for (const u of m.unlocks) itemRow(unlocks, u.item, tr.t(`gate_${u.gate_mode}`));
    const reqs = m.requires.map((k) => byKey.get(k)).filter((x): x is Milestone => !!x);
    const reqList = section(tr.t('requires'));
    if (reqs.length === 0) html('li', 'arf-pg__none', reqList, tr.t('nothing'));
    for (const r of reqs) milestoneRow(reqList, r, m);
    const next = children.get(m.key) ?? [];
    const nextList = section(tr.t('leads_to'));
    if (next.length === 0) html('li', 'arf-pg__none', nextList, tr.t('nothing'));
    for (const n of next) milestoneRow(nextList, n, m);

    if (state && st !== 'done') {
      const path = pathTo(snapshot, state, m.key);
      const list = section(tr.t('path'));
      if (path.length === 0) html('li', 'arf-pg__none', list, tr.t('path_ready'));
      else {
        html('li', 'arf-pg__path-count', list, tr.t('path_left', { n: path.length }));
        for (const step of path.slice(0, 8)) milestoneRow(list, step, m);
        if (path.length > 8) html('li', 'arf-pg__none', list, tr.t('path_more', { n: path.length - 8 }));
      }
    }
  }

  function select(key: string | null) {
    selected = key && byKey.has(key) ? key : null;
    applyClasses();
    renderPanel();
    writeHash();
  }

  // ---- deep link ----
  const deepLink = options.deepLink !== false && typeof location !== 'undefined' && typeof history !== 'undefined';
  function readHash(): string | null {
    if (!deepLink) return null;
    const match = /(?:^#|&)m=([^&]+)/.exec(location.hash);
    return match ? decodeURIComponent(match[1]) : null;
  }
  function writeHash() {
    if (!deepLink) return;
    const url = selected ? `#m=${encodeURIComponent(selected)}` : location.pathname + location.search;
    if ((selected ? url : '') === location.hash) return;
    history.replaceState(history.state, '', url);
  }

  // ---- pan and zoom ----
  const view = { x: 0, y: 0, k: 1 };
  function applyView() {
    world.setAttribute('transform', `translate(${view.x} ${view.y}) scale(${view.k})`);
  }
  function viewport() {
    const r = canvas.getBoundingClientRect();
    return { w: r.width || 800, h: r.height || 600, left: r.left, top: r.top };
  }
  function zoomAt(factor: number, cx: number, cy: number) {
    userMoved = true;
    const k = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, view.k * factor));
    view.x = cx - ((cx - view.x) * k) / view.k;
    view.y = cy - ((cy - view.y) * k) / view.k;
    view.k = k;
    applyView();
  }
  function zoomBy(factor: number) {
    const v = viewport();
    zoomAt(factor, v.w / 2, v.h / 2);
  }
  function fit() {
    const v = viewport();
    const pad = 24;
    const k = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, Math.min((v.w - 2 * pad) / worldWidth, (v.h - 2 * pad) / Math.max(worldHeight, 1))));
    view.k = k;
    view.x = (v.w - worldWidth * k) / 2;
    view.y = Math.max(pad, (v.h - worldHeight * k) / 2);
    applyView();
  }
  /** Readable start: the open stage (or the first one) at a zoom where captions are legible. */
  function home() {
    const v = viewport();
    const col = layout.columns.find((c) => c.stage === state?.open_stage) ?? layout.columns[0];
    view.k = 0.85;
    if (worldWidth * view.k <= v.w) view.x = (v.w - worldWidth * view.k) / 2;
    else view.x = Math.min(16, Math.max(v.w - worldWidth * view.k - 16, v.w / 2 - (col.x + col.width / 2) * view.k));
    view.y = 12;
    applyView();
  }

  let flight = 0;
  /** Smooth pan and zoom to a target view; a jump when the visitor prefers reduced motion. */
  function flyTo(target: { x: number; y: number; k: number }, done?: () => void) {
    userMoved = true;
    const id = ++flight;
    if (reducedMotion || typeof requestAnimationFrame !== 'function') {
      Object.assign(view, target);
      applyView();
      done?.();
      return;
    }
    const v = viewport();
    const centre = (w: { x: number; y: number; k: number }) => ({ x: (v.w / 2 - w.x) / w.k, y: (v.h / 2 - w.y) / w.k });
    const c0 = centre(view), c1 = centre(target);
    const k0 = view.k;
    const distance = Math.hypot(c1.x - c0.x, c1.y - c0.y) * Math.min(k0, target.k);
    const t0 = performance.now();
    const duration = 560;
    const ease = (t: number) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
    const step = (now: number) => {
      if (id !== flight) return;
      const t = Math.min(1, (now - t0) / duration);
      const e = ease(t);
      // Long trips zoom out a little mid-flight, so the eye keeps its bearings.
      const dip = 1 - 0.3 * Math.sin(Math.PI * e) * Math.min(1, distance / 1200);
      const k = (k0 + (target.k - k0) * e) * dip;
      const cx = c0.x + (c1.x - c0.x) * e, cy = c0.y + (c1.y - c0.y) * e;
      view.k = k;
      view.x = v.w / 2 - cx * k;
      view.y = v.h / 2 - cy * k;
      applyView();
      if (t < 1) requestAnimationFrame(step);
      else {
        Object.assign(view, target);
        applyView();
        done?.();
      }
    };
    requestAnimationFrame(step);
  }

  function viewFor(key: string) {
    const c = nodeCenter(key);
    const v = viewport();
    const panelSpace = v.w > 900 ? 380 : 0;
    const k = Math.max(view.k, 0.9);
    return { k, x: (v.w - panelSpace) / 2 - c.x * k, y: v.h / 2 - c.y * k };
  }

  function focus(key: string, animate = true) {
    const m = byKey.get(key);
    if (!m) return;
    if (hidden.has(m.graph)) {
      hidden.delete(m.graph);
      syncChips();
      draw();
    }
    select(key);
    const target = viewFor(key);
    if (animate) flyTo(target);
    else {
      Object.assign(view, target);
      applyView();
    }
    nodeEls.get(key)?.focus({ preventScroll: true });
  }

  function flyToStage(stageNumber: number) {
    const col = layout.columns.find((c) => c.stage === stageNumber);
    if (!col) return;
    const v = viewport();
    const k = Math.max(view.k, 0.75);
    flyTo({ k, x: v.w / 2 - (col.x + col.width / 2) * k, y: 12 });
  }

  let drag: { id: number; x: number; y: number; moved: boolean } | null = null;
  const onPointerDown = (e: PointerEvent) => {
    if (e.button !== 0) return;
    drag = { id: e.pointerId, x: e.clientX, y: e.clientY, moved: false };
  };
  const onPointerMove = (e: PointerEvent) => {
    if (!drag || drag.id !== e.pointerId) return;
    const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
    if (!drag.moved && Math.hypot(dx, dy) < 4) return;
    if (!drag.moved) canvas.setPointerCapture(e.pointerId);
    drag.moved = true;
    userMoved = true;
    view.x += dx;
    view.y += dy;
    drag.x = e.clientX;
    drag.y = e.clientY;
    applyView();
    stage.classList.add('is-panning');
  };
  const onPointerUp = (e: PointerEvent) => {
    if (!drag || drag.id !== e.pointerId) return;
    const wasDrag = drag.moved;
    drag = null;
    stage.classList.remove('is-panning');
    if (wasDrag) return;
    const node = (e.target as Element).closest?.('.arf-pg__node') as SVGGElement | null;
    select(node?.dataset.key ?? null);
  };
  const onPointerOver = (e: PointerEvent) => {
    if (drag?.moved) return;
    const node = (e.target as Element).closest?.('.arf-pg__node') as SVGGElement | null;
    const key = node?.dataset.key ?? null;
    if (key === hovered) return;
    hovered = key;
    applyClasses();
  };
  const onPointerLeave = () => {
    if (hovered === null) return;
    hovered = null;
    applyClasses();
  };
  const onWheel = (e: WheelEvent) => {
    e.preventDefault();
    const v = viewport();
    zoomAt(Math.exp(-e.deltaY * 0.0015), e.clientX - v.left, e.clientY - v.top);
  };
  /** Arrow keys walk the graph from the selection: left to a requirement, right to a dependent, up and down in the stage column. */
  function neighbour(key: string, direction: string): string | null {
    const m = byKey.get(key)!;
    const visible = (k: string) => nodeEls.has(k);
    if (direction === 'ArrowLeft') return m.requires.find(visible) ?? null;
    if (direction === 'ArrowRight') return (children.get(key) ?? []).map((c) => c.key).find(visible) ?? null;
    const column = snapshot.milestones
      .filter((o) => o.graph === m.graph && o.stage === m.stage && visible(o.key))
      .sort((a, b) => nodeCenter(a.key).y - nodeCenter(b.key).y);
    const i = column.findIndex((o) => o.key === key);
    const j = direction === 'ArrowUp' ? i - 1 : i + 1;
    return column[j]?.key ?? null;
  }
  const onKey = (e: KeyboardEvent) => {
    const target = e.target as Element;
    if (target.closest?.('.arf-pg__palette')) return;
    if (e.key === 'Escape') select(null);
    else if ((e.key === 'Enter' || e.key === ' ') && target.classList?.contains('arf-pg__node')) {
      e.preventDefault();
      select((target as SVGGElement).dataset.key ?? null);
    } else if (selected && ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
      const next = neighbour(selected, e.key);
      e.preventDefault();
      if (next) focus(next);
    }
  };
  const onGlobalKey = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && !e.altKey && e.key.toLowerCase() === 'k' && root.isConnected) {
      e.preventDefault();
      if (palette.hidden) openPalette();
      else closePalette();
    }
  };
  canvas.addEventListener('pointerdown', onPointerDown);
  canvas.addEventListener('pointermove', onPointerMove);
  canvas.addEventListener('pointerup', onPointerUp);
  canvas.addEventListener('pointercancel', () => (drag = null));
  canvas.addEventListener('wheel', onWheel, { passive: false });
  canvas.addEventListener('pointerover', onPointerOver);
  canvas.addEventListener('pointerleave', onPointerLeave);
  root.addEventListener('keydown', onKey);
  document.addEventListener('keydown', onGlobalKey);

  // ---- command palette (Ctrl+K / Cmd+K) ----
  type Entry =
    | { kind: 'milestone'; label: string; meta: string; key: string; icon: string | null }
    | { kind: 'item'; label: string; meta: string; key: string; icon: string | null }
    | { kind: 'graph'; label: string; meta: string; graph: string; icon: string | null }
    | { kind: 'stage'; label: string; meta: string; stage: number; icon: null };

  const entries: Entry[] = [];
  for (const m of snapshot.milestones) {
    const item = m.unlocks[0]?.item;
    entries.push({ kind: 'milestone', label: tr.text(m.title), meta: `${tr.text(graphByKey.get(m.graph)?.name)} · ${tr.t('stage_of', { n: m.stage })}`, key: m.key, icon: item ? iconOf(item) : null });
  }
  const itemHome = new Map<string, Milestone>();
  for (const m of snapshot.milestones) {
    const items = [...m.unlocks.map((u) => u.item), ...(m.validation?.items ?? []).map((i) => (typeof i === 'string' ? i : i.item))];
    for (const item of items) {
      const known = itemHome.get(item);
      if (!item.startsWith('#') && (!known || m.stage < known.stage)) itemHome.set(item, m);
    }
  }
  for (const [item, m] of itemHome) {
    entries.push({ kind: 'item', label: humanItem(item), meta: tr.t('item_in', { title: tr.text(m.title) }), key: m.key, icon: iconOf(item) });
  }
  for (const g of snapshot.graphs) {
    if (!layout.graphs[g.key]?.height) continue;
    entries.push({ kind: 'graph', label: tr.text(g.name), meta: tr.t('graph_only'), graph: g.key, icon: graphIcon(g) });
  }
  for (const st of snapshot.stages) {
    entries.push({ kind: 'stage', label: `${tr.t('stage', { n: st.number })} · ${tr.text(st.name)}`, meta: '', stage: st.number, icon: null });
  }

  const palette = html('div', 'arf-pg__palette', root);
  palette.hidden = true;
  palette.setAttribute('role', 'dialog');
  palette.setAttribute('aria-modal', 'true');
  palette.setAttribute('aria-label', tr.t('search_button'));
  const paletteBox = html('div', 'arf-pg__palette-box', palette);
  const paletteField = html('label', 'arf-pg__palette-field', paletteBox);
  paletteField.append(iconSvg(ICONS.search));
  const paletteInput = html('input', '', paletteField);
  paletteInput.type = 'text';
  paletteInput.placeholder = tr.t('palette_placeholder');
  paletteInput.setAttribute('aria-label', tr.t('palette_placeholder'));
  paletteInput.setAttribute('role', 'combobox');
  paletteInput.setAttribute('aria-expanded', 'true');
  paletteInput.autocomplete = 'off';
  paletteInput.spellcheck = false;
  const results = html('ul', 'arf-pg__results', paletteBox);
  results.id = `${markerPrefix}-results`;
  results.setAttribute('role', 'listbox');
  paletteInput.setAttribute('aria-controls', results.id);
  html('p', 'arf-pg__palette-keys', paletteBox, tr.t('palette_keys'));
  let shown: Entry[] = [];
  let cursor = 0;

  function defaultEntries(): Entry[] {
    const available = snapshot.milestones.filter((m) => states.get(m.key) === 'available').map((m) => m.key);
    const pick = new Set(available.slice(0, 6));
    return [...entries.filter((e) => e.kind === 'milestone' && pick.has(e.key)), ...entries.filter((e) => e.kind === 'graph').slice(0, 8)];
  }

  function renderResults() {
    const q = paletteInput.value.trim();
    // Milestones first on equal footing: they are what the graph is made of.
    const boost: Record<Entry['kind'], number> = { milestone: 12, graph: 8, stage: 4, item: 0 };
    shown = q
      ? rank(q, entries, (e) => e.label, 40)
          .map((r) => ({ ...r, score: r.score + boost[r.entry.kind] }))
          .sort((a, b) => b.score - a.score)
          .slice(0, 14)
          .map((r) => r.entry)
      : defaultEntries();
    cursor = Math.min(cursor, Math.max(0, shown.length - 1));
    results.replaceChildren();
    if (shown.length === 0) {
      html('li', 'arf-pg__results-empty', results, tr.t('palette_empty'));
      paletteInput.removeAttribute('aria-activedescendant');
      return;
    }
    shown.forEach((entry, i) => {
      const li = html('li', `arf-pg__result${i === cursor ? ' is-cursor' : ''}`, results);
      li.id = `${markerPrefix}-r${i}`;
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', String(i === cursor));
      const icon = html('span', 'arf-pg__result-icon', li);
      if (entry.icon) {
        const img = html('img', '', icon);
        img.src = entry.icon;
        img.alt = '';
        onMissing(img, () => document.createTextNode(entry.label.slice(0, 2).toUpperCase()));
      } else icon.textContent = entry.kind === 'stage' ? String(entry.stage) : entry.label.slice(0, 2).toUpperCase();
      const text = html('span', 'arf-pg__result-text', li);
      html('b', '', text, entry.label);
      if (entry.meta) html('small', '', text, entry.meta);
      html('span', `arf-pg__result-kind is-${entry.kind}`, li, tr.t(`kind_${entry.kind}`));
      li.addEventListener('pointermove', () => {
        if (cursor === i) return;
        cursor = i;
        syncCursor();
      });
      li.addEventListener('click', () => run(entry));
    });
    syncCursor();
  }

  function syncCursor() {
    results.querySelectorAll('.arf-pg__result').forEach((li, i) => {
      li.classList.toggle('is-cursor', i === cursor);
      li.setAttribute('aria-selected', String(i === cursor));
      if (i === cursor) li.scrollIntoView({ block: 'nearest' });
    });
    paletteInput.setAttribute('aria-activedescendant', `${markerPrefix}-r${cursor}`);
  }

  function run(entry: Entry) {
    closePalette();
    if (entry.kind === 'milestone' || entry.kind === 'item') focus(entry.key);
    else if (entry.kind === 'graph') {
      hidden.clear();
      for (const g of snapshot.graphs) if (g.key !== entry.graph) hidden.add(g.key);
      syncChips();
      draw();
      fit();
    } else flyToStage(entry.stage);
  }

  let returnFocus: Element | null = null;
  function openPalette() {
    returnFocus = document.activeElement;
    palette.hidden = false;
    paletteInput.value = '';
    cursor = 0;
    renderResults();
    paletteInput.focus();
  }
  function closePalette() {
    if (palette.hidden) return;
    palette.hidden = true;
    if (returnFocus instanceof HTMLElement || returnFocus instanceof SVGElement) returnFocus.focus({ preventScroll: true });
  }

  paletteInput.addEventListener('input', () => {
    cursor = 0;
    renderResults();
  });
  paletteInput.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (shown.length === 0) return;
      cursor = (cursor + (e.key === 'ArrowDown' ? 1 : shown.length - 1)) % shown.length;
      syncCursor();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (shown[cursor]) run(shown[cursor]);
    } else if (e.key === 'Escape') {
      e.preventDefault();
      closePalette();
    } else if (e.key === 'Tab') e.preventDefault();
  });
  palette.addEventListener('pointerdown', (e) => {
    if (e.target === palette) closePalette();
  });

  // ---- theme ----
  function syncThemeButton() {
    const dark = root.dataset.theme === 'dark';
    const label = dark ? tr.t('theme_light') : tr.t('theme_dark');
    themeButton.title = label;
    themeButton.setAttribute('aria-label', label);
    themeButton.replaceChildren(iconSvg(dark ? ICONS.sun : ICONS.moon));
  }
  function applyTheme(theme: 'dark' | 'light') {
    root.dataset.theme = theme;
    syncThemeButton();
  }
  function setTheme(theme: 'dark' | 'light') {
    applyTheme(theme);
    options.onThemeChange?.(theme);
  }

  let userMoved = false;
  const resize = new ResizeObserver(() => {
    if (!userMoved) home();
  });
  draw();
  syncChips();
  home();
  resize.observe(stage);
  const linked = readHash();
  if (linked && byKey.has(linked)) requestAnimationFrame(() => focus(linked, false));

  return {
    select,
    focus: (key: string) => focus(key),
    setTheme: applyTheme,
    openSearch: openPalette,
    fit,
    destroy() {
      resize.disconnect();
      document.removeEventListener('keydown', onGlobalKey);
      root.remove();
    },
  };
}
