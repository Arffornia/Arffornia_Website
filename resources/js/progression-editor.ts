import { mountProgressionGraph } from '../../packages/progression-graph/src/index.ts';
import { computeLayout } from '../../packages/progression-graph/src/layout.ts';
import type { Graph, Locale, Milestone, PickHit, Snapshot, Stage, Text, ViewHandle, ViewTransform } from '../../packages/progression-graph/src/index.ts';
import { iconResolver, type IconIndex } from './progression-icons.ts';

/** Admin progression editor (spec 019): the shared graph component on a draft, edited through the draft API. */

interface EditorConfig {
  api: string;
  csrf: string;
  iconsUrl: string;
  iconBase: string;
  locale: Locale;
  i18n: Record<string, string>;
}

interface Draft {
  id: number;
  status: string;
  revision: number;
  base_version: number | null;
  published_version: number | null;
  document: Snapshot;
  subject?: string | null;
  can_undo: boolean;
  can_redo: boolean;
}

interface HistoryEntry {
  id: number;
  type: string;
  action: string | null;
  subject: string | null;
  state: string | null;
  revision: number;
  user: string | null;
  at: string | null;
}

type Mode = 'view' | 'add' | 'delete' | 'link' | 'unlink' | 'move';
type PanelView =
  | { kind: 'none' }
  | { kind: 'milestone'; key: string }
  | { kind: 'new-milestone'; graph: string; stage: number }
  | { kind: 'graph'; key: string | null }
  | { kind: 'stage'; number: number | null }
  | { kind: 'history' }
  | { kind: 'review' };

interface Finding {
  severity: 'error' | 'warning' | 'info';
  code: string;
  subject: string;
  message: string;
}

interface Diff {
  against: number | null;
  empty: boolean;
  stages: { added: number[]; removed: number[]; changed: { key: number; fields: string[] }[] };
  graphs: { added: string[]; removed: string[]; changed: { key: string; fields: string[] }[] };
  milestones: { added: string[]; removed: string[]; moved: { key: string; from: { stage: number; graph: string }; to: { stage: number; graph: string } }[]; changed: { key: string; fields: string[] }[] };
  links: { added: { from: string; to: string }[]; removed: { from: string; to: string }[] };
  unlocks: { added: { milestone: string; item: string }[]; removed: { milestone: string; item: string }[]; changed: { milestone: string; item: string; fields: string[] }[] };
  marks: Record<string, string>;
}

const MODES: [Mode, string][] = [['view', 'v'], ['add', 'a'], ['delete', 'd'], ['link', 'l'], ['unlink', 'u'], ['move', 'm']];

class ApiError extends Error {
  constructor(readonly status: number, message: string, readonly body: Record<string, unknown>) {
    super(message);
  }
}

const config = (window as unknown as { ArfforniaEditor?: EditorConfig }).ArfforniaEditor;
const root = document.getElementById('progression-editor');

function t(key: string, params: Record<string, string | number> = {}): string {
  let text = config?.i18n[key] ?? key;
  for (const [name, value] of Object.entries(params)) text = text.replaceAll(`:${name}`, String(value));
  return text;
}

type Child = Node | string | null | undefined | false;
function h<K extends keyof HTMLElementTagNameMap>(tag: K, attrs: Record<string, string | boolean | number | undefined> = {}, ...children: Child[]): HTMLElementTagNameMap[K] {
  const el = document.createElement(tag);
  for (const [name, value] of Object.entries(attrs)) {
    if (value === undefined || value === false) continue;
    if (name === 'class') el.className = String(value);
    else el.setAttribute(name, value === true ? '' : String(value));
  }
  for (const child of children) if (child) el.append(child);
  return el;
}

function button(label: string, onClick: () => void, attrs: Record<string, string | boolean | undefined> = {}): HTMLButtonElement {
  const b = h('button', { type: 'button', ...attrs }, label);
  b.addEventListener('click', onClick);
  return b;
}

function field(label: string, input: HTMLElement): HTMLLabelElement {
  return h('label', { class: 'pe-field' }, h('span', {}, label), input);
}

function input(name: string, value: string | number | undefined, attrs: Record<string, string | boolean | undefined> = {}): HTMLInputElement {
  const el = h('input', { name, ...attrs });
  el.value = value === undefined || value === null ? '' : String(value);
  return el;
}

function textarea(name: string, value: string, rows = 3): HTMLTextAreaElement {
  const el = h('textarea', { name, rows: String(rows) });
  el.value = value;
  return el;
}

function select(name: string, options: [string, string][], value: string): HTMLSelectElement {
  const el = h('select', { name });
  for (const [v, label] of options) el.append(h('option', { value: v, selected: v === value }, label));
  return el;
}

const text = (value: Text | undefined): string => (value ? (config?.locale === 'fr' ? (value.fr ?? value.en) : value.en) : '');

function formValue(form: HTMLFormElement, name: string): string {
  const el = form.elements.namedItem(name) as HTMLInputElement | null;
  return el ? el.value : '';
}

function textPair(form: HTMLFormElement, name: string): { en: string; fr: string | null } {
  return { en: formValue(form, `${name}_en`), fr: formValue(form, `${name}_fr`) || null };
}

/** One entry per line: `<item or #tag> [count]`. */
function parseCounts(raw: string): { item: string; count: number }[] {
  return raw
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const [item, count] = line.split(/\s+/);
      return { item, count: Math.max(1, Number(count ?? 1) || 1) };
    });
}

function formatCounts(entries: { item?: string; tag?: string; count?: number }[] | undefined): string {
  return (entries ?? []).map((e) => `${e.tag ? `#${e.tag}` : e.item} ${e.count ?? 1}`).join('\n');
}

class Editor {
  draft!: Draft;
  mode: Mode = 'view';
  panelView: PanelView = { kind: 'none' };
  linkFrom: string | null = null;
  graphFilter: string | null = null;
  handle: ViewHandle | null = null;
  view: ViewTransform | null = null;
  renderSeq = 0;
  marks: Record<string, string> | null = null;
  icon: (item: string) => string | null = () => null;

  readonly sidebar = h('aside', { class: 'pe-side' });
  readonly bar = h('div', { class: 'pe-bar' });
  readonly canvas = h('div', { class: 'pe-canvas' });
  readonly panel = h('aside', { class: 'pe-panel', 'aria-live': 'polite' });
  readonly status = h('p', { class: 'pe-status', role: 'status' });

  constructor(readonly el: HTMLElement, readonly cfg: EditorConfig) {}

  async start() {
    this.el.replaceChildren(this.sidebar, h('section', { class: 'pe-main' }, this.bar, this.status, this.canvas), this.panel);
    document.addEventListener('keydown', (e) => this.onKey(e));
    try {
      const index = await this.request<IconIndex>('GET', this.cfg.iconsUrl);
      this.icon = iconResolver(index, this.cfg.iconBase);
    } catch {
      // No icon manifest (local dev): milestones show their initials.
    }
    this.setDraft(await this.request<Draft>('POST', this.cfg.api, {}));
    this.el.dataset.ready = 'true';
  }

  get doc(): Snapshot {
    return this.draft.document;
  }

  milestone(key: string): Milestone | undefined {
    return this.doc.milestones.find((m) => m.key === key);
  }

  async request<T>(method: string, url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': this.cfg.csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = (await response.json().catch(() => ({}))) as Record<string, unknown>;
    if (!response.ok) {
      const errors = data.errors as Record<string, string[]> | undefined;
      const message = errors ? Object.values(errors).flat().join(' ') : String(data.message ?? response.statusText);
      throw new ApiError(response.status, message, data);
    }
    return data as T;
  }

  draftUrl(path = ''): string {
    return `${this.cfg.api}/${this.draft.id}${path}`;
  }

  say(message: string, kind: 'info' | 'error' = 'info') {
    this.status.textContent = message;
    this.status.dataset.kind = kind;
  }

  async act(action: string, payload: Record<string, unknown>): Promise<Draft | null> {
    try {
      const next = await this.request<Draft>('POST', this.draftUrl('/actions'), { revision: this.draft.revision, action, payload });
      this.say(t('saved', { action: t(`action.${action}`) }));
      this.setDraft(next);
      return next;
    } catch (error) {
      await this.fail(error);
      return null;
    }
  }

  async fail(error: unknown) {
    const message = error instanceof Error ? error.message : String(error);
    this.say(message, 'error');
    if (error instanceof ApiError && error.status === 409) this.setDraft(await this.request<Draft>('GET', this.draftUrl()));
  }

  setDraft(draft: Draft) {
    this.draft = draft;
    if (this.panelView.kind === 'milestone' && !this.milestone(this.panelView.key)) this.panelView = { kind: 'none' };
    this.renderBar();
    this.renderSidebar();
    this.renderPanel();
    void this.renderGraph();
  }

  setMode(mode: Mode) {
    this.mode = mode;
    this.linkFrom = null;
    this.el.dataset.mode = mode;
    this.say(t(`mode_hint.${mode}`));
    this.renderBar();
  }

  onKey(e: KeyboardEvent) {
    const target = e.target as HTMLElement;
    if (target.closest('input, textarea, select, [contenteditable]')) return;
    const key = e.key.toLowerCase();
    if ((e.ctrlKey || e.metaKey) && !e.altKey && (key === 'z' || key === 'y')) {
      e.preventDefault();
      void this.step(key === 'y' || e.shiftKey ? 'redo' : 'undo');
      return;
    }
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    const mode = MODES.find(([, key]) => key === e.key.toLowerCase());
    if (mode) this.setMode(mode[0]);
  }

  // ---- bar ----
  renderBar() {
    const d = this.draft;
    const modes = h('div', { class: 'pe-modes', role: 'toolbar', 'aria-label': t('modes') });
    for (const [mode, key] of MODES) {
      const b = button(t(`mode.${mode}`), () => this.setMode(mode), { 'data-mode': mode, 'aria-pressed': String(this.mode === mode), title: `${t(`mode_hint.${mode}`)} (${key.toUpperCase()})` });
      modes.append(b);
    }
    const file = h('input', { type: 'file', accept: 'application/json,.json', hidden: true, 'data-import': true });
    file.addEventListener('change', () => void this.importFile(file));
    this.bar.replaceChildren(
      h('div', { class: 'pe-draft' }, h('strong', {}, t('draft_title', { id: d.id })), h('span', { 'data-revision': String(d.revision) }, d.base_version === null ? t('draft_meta_unpublished', { revision: d.revision }) : t('draft_meta', { revision: d.revision, base: d.base_version }))),
      h('div', { class: 'pe-history', role: 'group', 'aria-label': t('history') },
        button(t('undo'), () => void this.step('undo'), { 'data-action': 'undo', disabled: !d.can_undo, title: `${t('undo')} (Ctrl+Z)` }),
        button(t('redo'), () => void this.step('redo'), { 'data-action': 'redo', disabled: !d.can_redo, title: `${t('redo')} (Ctrl+Shift+Z)` }),
        button(t('history'), () => this.showPanel({ kind: 'history' }), { 'data-action': 'history' }),
      ),
      button(t('review'), () => this.showPanel({ kind: 'review' }), { 'data-action': 'review', class: 'is-primary' }),
      modes,
      h('div', { class: 'pe-actions' },
        button(t('fit'), () => this.handle?.fit(), { 'data-action': 'fit' }),
        h('a', { href: this.draftUrl('/document'), download: `progression-draft-${d.id}.json`, 'data-action': 'export', class: 'pe-button' }, t('export')),
        button(t('import'), () => file.click(), { 'data-action': 'import' }),
        file,
        button(t('discard'), () => void this.discard(), { 'data-action': 'discard', class: 'is-danger' }),
      ),
    );
  }

  async step(direction: 'undo' | 'redo') {
    if (direction === 'undo' ? !this.draft.can_undo : !this.draft.can_redo) return;
    try {
      const next = await this.request<Draft>('POST', this.draftUrl(`/${direction}`), { revision: this.draft.revision });
      this.say(t(direction === 'undo' ? 'undone' : 'redone'));
      this.setDraft(next);
    } catch (error) {
      await this.fail(error);
    }
  }

  historyPanel(): HTMLElement[] {
    const list = h('ol', { class: 'pe-log', 'data-list': 'history' }, h('li', { class: 'pe-hint' }, t('loading')));
    void this.request<{ entries: HistoryEntry[] }>('GET', this.draftUrl('/history')).then(({ entries }) => {
      list.replaceChildren(...entries.map((e) => {
        const what = e.type === 'edit' ? t(`action.${e.action}`) : t(`log.${e.type}`);
        const when = e.at ? new Date(e.at).toLocaleString(this.cfg.locale) : '';
        return h('li', { 'data-type': e.type, 'data-state': e.state ?? undefined },
          h('strong', {}, what),
          e.subject ? h('span', { class: 'pe-key' }, e.subject) : null,
          h('span', { class: 'pe-hint' }, `${e.user ?? t('someone')} · ${when} · r${e.revision}${e.state && e.state !== 'applied' ? ` · ${t(`state.${e.state}`)}` : ''}`),
        );
      }));
    }).catch((error) => void this.fail(error));
    return [h('h3', {}, t('history_title', { id: this.draft.id })), list];
  }

  reviewPanel(): HTMLElement[] {
    const checks = h('div', { 'data-section': 'checks' }, h('p', { class: 'pe-hint' }, t('loading')));
    const changes = h('div', { 'data-section': 'diff' }, h('p', { class: 'pe-hint' }, t('loading')));
    const publish = h('div', { 'data-section': 'publish' });
    const revision = this.draft.revision;
    void Promise.all([
      this.request<{ valid: boolean; warnings: number; findings: Finding[] }>('POST', this.draftUrl('/validate')),
      this.request<Diff>('GET', this.draftUrl('/diff')),
    ]).then(([validation, diff]) => {
      if (this.panelView.kind !== 'review' || this.draft.revision !== revision) return;
      checks.replaceChildren(this.findingList(validation.findings));
      changes.replaceChildren(...this.diffList(diff));
      publish.replaceChildren(this.publishForm(validation.valid, validation.warnings));
      this.marks = diff.marks;
      void this.renderGraph();
    }).catch((error) => void this.fail(error));
    return [
      h('h3', {}, t('review_title')),
      h('h4', {}, t('checks')), checks,
      h('h4', {}, t('changes')), changes,
      h('h4', {}, t('publish')), publish,
    ];
  }

  findingList(findings: Finding[]): HTMLElement {
    if (!findings.length) return h('p', { class: 'pe-ok', 'data-valid': 'true' }, t('no_findings'));
    const list = h('ul', { class: 'pe-findings', 'data-list': 'findings' });
    for (const f of findings) {
      const target = this.milestone(f.subject) ? f.subject : null;
      const subject = target ? button(f.subject, () => this.handle?.focus(target), { class: 'pe-link' }) : h('span', { class: 'pe-key' }, f.subject);
      list.append(h('li', { 'data-severity': f.severity, 'data-code': f.code }, h('b', {}, t(`severity.${f.severity}`)), subject, h('span', {}, f.message)));
    }
    return list;
  }

  diffList(diff: Diff): HTMLElement[] {
    const against = diff.against === null ? t('against_nothing') : t('against_version', { version: diff.against });
    if (diff.empty) return [h('p', { class: 'pe-hint' }, against), h('p', { 'data-diff-empty': true }, t('no_changes'))];
    const title = (key: string) => text(this.milestone(key)?.title) || key;
    const rows: [string, string][] = [
      ...diff.milestones.added.map((k): [string, string] => ['added', t('diff.milestone_added', { title: title(k) })]),
      ...diff.milestones.removed.map((k): [string, string] => ['removed', t('diff.milestone_removed', { key: k })]),
      ...diff.milestones.moved.map((m): [string, string] => ['moved', t('diff.milestone_moved', { title: title(m.key), from: m.from.stage, to: m.to.stage })]),
      ...diff.milestones.changed.map((c): [string, string] => ['changed', t('diff.milestone_changed', { title: title(c.key), fields: c.fields.join(', ') })]),
      ...diff.links.added.map((l): [string, string] => ['added', t('diff.link_added', { from: title(l.from), to: title(l.to) })]),
      ...diff.links.removed.map((l): [string, string] => ['removed', t('diff.link_removed', { from: l.from, to: l.to })]),
      ...diff.unlocks.added.map((u): [string, string] => ['added', t('diff.unlock_added', { item: u.item, title: title(u.milestone) })]),
      ...diff.unlocks.removed.map((u): [string, string] => ['removed', t('diff.unlock_removed', { item: u.item, title: title(u.milestone) })]),
      ...diff.unlocks.changed.map((u): [string, string] => ['changed', t('diff.unlock_changed', { item: u.item, title: title(u.milestone), fields: u.fields.join(', ') })]),
      ...diff.stages.added.map((n): [string, string] => ['added', t('diff.stage_added', { n })]),
      ...diff.stages.removed.map((n): [string, string] => ['removed', t('diff.stage_removed', { n })]),
      ...diff.stages.changed.map((c): [string, string] => ['changed', t('diff.stage_changed', { n: c.key, fields: c.fields.join(', ') })]),
      ...diff.graphs.added.map((k): [string, string] => ['added', t('diff.graph_added', { key: k })]),
      ...diff.graphs.removed.map((k): [string, string] => ['removed', t('diff.graph_removed', { key: k })]),
      ...diff.graphs.changed.map((c): [string, string] => ['changed', t('diff.graph_changed', { key: c.key, fields: c.fields.join(', ') })]),
    ];
    const list = h('ul', { class: 'pe-diff', 'data-list': 'diff' });
    for (const [kind, line] of rows) list.append(h('li', { 'data-kind': kind }, line));
    return [h('p', { class: 'pe-hint' }, `${against} · ${t('diff_legend')}`), list];
  }

  publishForm(valid: boolean, warnings: number): HTMLElement {
    const confirmBox = h('input', { type: 'checkbox', name: 'confirm_warnings' });
    const submit = h('button', { type: 'submit', class: 'is-primary', disabled: !valid || warnings > 0, 'data-action': 'publish' }, t('publish'));
    confirmBox.addEventListener('change', () => (submit.disabled = !valid || !confirmBox.checked));
    return this.form('publish', (form) => void this.publish(formValue(form, 'notes'), confirmBox.checked),
      valid ? null : h('p', { class: 'pe-error-text', 'data-blocked': true }, t('publish_blocked')),
      field(t('notes'), textarea('notes', '')),
      warnings > 0 ? h('label', { class: 'pe-check' }, confirmBox, t('confirm_warnings', { n: warnings })) : null,
      submit,
    );
  }

  async publish(notes: string, confirmWarnings: boolean) {
    try {
      const result = await this.request<{ version: number }>('POST', this.draftUrl('/publish'), { notes: notes || null, confirm_warnings: confirmWarnings });
      this.marks = null;
      this.panelView = { kind: 'none' };
      this.setDraft(await this.request<Draft>('POST', this.cfg.api, {}));
      this.say(t('published', { version: result.version }));
    } catch (error) {
      await this.fail(error);
    }
  }

  async importFile(file: HTMLInputElement) {
    const chosen = file.files?.[0];
    file.value = '';
    if (!chosen) return;
    try {
      const document = JSON.parse(await chosen.text()) as Snapshot;
      const next = await this.request<Draft>('PUT', this.draftUrl('/document'), { revision: this.draft.revision, document });
      this.say(t('imported'));
      this.setDraft(next);
    } catch (error) {
      await this.fail(error instanceof SyntaxError ? new Error(t('import_not_json')) : error);
    }
  }

  async discard() {
    if (!confirm(t('confirm_discard'))) return;
    try {
      await this.request('POST', this.draftUrl('/discard'));
      this.panelView = { kind: 'none' };
      this.setDraft(await this.request<Draft>('POST', this.cfg.api, {}));
      this.say(t('discarded'));
    } catch (error) {
      await this.fail(error);
    }
  }

  // ---- sidebar: graphs and stages ----
  renderSidebar() {
    const search = input('graph_search', '', { type: 'search', placeholder: t('search_graphs'), 'aria-label': t('search_graphs') });
    const list = h('ul', { class: 'pe-graphs' });
    const fill = () => {
      const q = search.value.trim().toLowerCase();
      list.replaceChildren(
        h('li', {}, button(t('all_graphs'), () => this.filterGraph(null), { class: this.graphFilter === null ? 'is-on' : '', 'data-graph': '' })),
      );
      const byBranch = new Map<string, Graph[]>();
      for (const g of this.doc.graphs) {
        if (q && !`${text(g.name)} ${g.key}`.toLowerCase().includes(q)) continue;
        byBranch.set(g.branch, [...(byBranch.get(g.branch) ?? []), g]);
      }
      for (const [branch, graphs] of byBranch) {
        list.append(h('li', { class: 'pe-group' }, t(`branch.${branch}`)));
        for (const g of graphs) {
          const count = this.doc.milestones.filter((m) => m.graph === g.key).length;
          list.append(h('li', { 'data-graph-row': g.key },
            button(`${text(g.name)} (${count})`, () => this.filterGraph(g.key), { class: this.graphFilter === g.key ? 'is-on' : '', 'data-graph': g.key }),
            button('✎', () => this.showPanel({ kind: 'graph', key: g.key }), { 'aria-label': t('edit_graph'), title: t('edit_graph'), 'data-edit-graph': g.key }),
            button('×', () => void this.deleteGraph(g), { 'aria-label': t('delete_graph'), title: t('delete_graph'), 'data-delete-graph': g.key }),
          ));
        }
      }
    };
    search.addEventListener('input', fill);
    fill();

    const stages = h('ul', { class: 'pe-stages' });
    for (const s of [...this.doc.stages].sort((a, b) => a.number - b.number)) {
      stages.append(h('li', { 'data-stage-row': String(s.number) },
        h('span', {}, `${t('stage_n', { n: s.number })} · ${text(s.name)}`),
        button('✎', () => this.showPanel({ kind: 'stage', number: s.number }), { 'aria-label': t('edit_stage'), title: t('edit_stage'), 'data-edit-stage': String(s.number) }),
        button('×', () => void this.deleteStage(s), { 'aria-label': t('delete_stage'), title: t('delete_stage'), 'data-delete-stage': String(s.number) }),
      ));
    }

    this.sidebar.replaceChildren(
      h('h2', {}, t('graphs')), search, list,
      button(t('add_graph'), () => this.showPanel({ kind: 'graph', key: null }), { 'data-action': 'add-graph' }),
      h('h2', {}, t('stages')), stages,
      button(t('add_stage'), () => this.showPanel({ kind: 'stage', number: null }), { 'data-action': 'add-stage' }),
    );
  }

  filterGraph(key: string | null) {
    this.graphFilter = key;
    this.renderSidebar();
    this.handle?.showGraphs(key ? [key] : null);
    this.view = this.handle?.getView() ?? null;
  }

  async deleteGraph(g: Graph) {
    if (!confirm(t('confirm_delete_graph', { name: text(g.name) }))) return;
    if (await this.act('graph.delete', { key: g.key })) {
      if (this.graphFilter === g.key) this.graphFilter = null;
      this.showPanel({ kind: 'none' });
    }
  }

  async deleteStage(s: Stage) {
    if (!confirm(t('confirm_delete_stage', { n: s.number }))) return;
    if (await this.act('stage.delete', { number: s.number })) this.showPanel({ kind: 'none' });
  }

  // ---- graph ----
  async renderGraph() {
    const seq = ++this.renderSeq;
    const doc = this.doc;
    const layout = await computeLayout(doc);
    if (seq !== this.renderSeq) return;
    if (this.handle) {
      this.view = this.handle.getView();
      this.handle.destroy();
    }
    this.handle = mountProgressionGraph(this.canvas, {
      snapshot: { ...doc, layout },
      locale: this.cfg.locale,
      theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
      icon: this.icon,
      brand: false,
      deepLink: false,
      panel: false,
      onSelect: (key) => {
        if (this.mode === 'view' || this.mode === 'move') this.showPanel(key ? { kind: 'milestone', key } : { kind: 'none' });
      },
      onPick: (hit) => this.onPick(hit),
      draggable: () => this.mode === 'move',
      onDrop: (key, hit) => void this.onDrop(key, hit),
      marks: this.marks ?? undefined,
    });
    if (this.graphFilter && doc.graphs.some((g) => g.key === this.graphFilter)) this.handle.showGraphs([this.graphFilter]);
    if (this.view) this.handle.setView(this.view);
    const selected = this.panelView.kind === 'milestone' ? this.panelView.key : this.linkFrom;
    if (selected) this.handle.select(selected);
    this.canvas.dataset.revision = String(this.draft.revision);
  }

  onPick(hit: PickHit): boolean {
    switch (this.mode) {
      case 'view':
      case 'move':
        return false;
      case 'add':
        if (hit.node) return false;
        this.showPanel({
          kind: 'new-milestone',
          graph: hit.graph ?? this.graphFilter ?? this.doc.graphs[0]?.key ?? '',
          stage: hit.stage ?? this.doc.stages[0]?.number ?? 1,
        });
        return true;
      case 'delete': {
        const m = hit.node ? this.milestone(hit.node) : null;
        if (m && confirm(t('confirm_delete_milestone', { title: text(m.title) }))) void this.act('milestone.delete', { key: m.key });
        return true;
      }
      case 'link':
        if (!hit.node) {
          this.linkFrom = null;
          this.say(t('mode_hint.link'));
          return false;
        }
        if (!this.linkFrom) {
          this.linkFrom = hit.node;
          this.say(t('link_pick_target', { title: text(this.milestone(hit.node)?.title) }));
          return false;
        }
        void this.act('link.create', { from: this.linkFrom, to: hit.node });
        this.linkFrom = null;
        return true;
      case 'unlink':
        if (hit.edge) void this.act('link.delete', hit.edge);
        return true;
    }
  }

  async onDrop(key: string, hit: PickHit) {
    const m = this.milestone(key);
    if (!m || hit.stage === null) return;
    const graph = hit.graph ?? m.graph;
    if (hit.stage === m.stage && graph === m.graph) return;
    await this.act('milestone.move', { key, stage: hit.stage, graph });
  }

  // ---- side panel ----
  showPanel(view: PanelView) {
    const leavingReview = this.panelView.kind === 'review' && view.kind !== 'review';
    this.panelView = view;
    this.renderPanel();
    if (leavingReview && this.marks) {
      this.marks = null;
      void this.renderGraph();
    }
  }

  renderPanel() {
    const v = this.panelView;
    const content =
      v.kind === 'milestone' && this.milestone(v.key) ? this.milestonePanel(this.milestone(v.key)!) :
      v.kind === 'new-milestone' ? this.newMilestoneForm(v.graph, v.stage) :
      v.kind === 'graph' ? this.graphForm(v.key) :
      v.kind === 'stage' ? this.stageForm(v.number) :
      v.kind === 'history' ? this.historyPanel() :
      v.kind === 'review' ? this.reviewPanel() :
      [h('p', { class: 'pe-hint' }, t('panel_empty'))];
    this.panel.replaceChildren(...content);
    this.panel.dataset.view = v.kind;
  }

  form(name: string, onSubmit: (form: HTMLFormElement) => void, ...children: Child[]): HTMLFormElement {
    const form = h('form', { class: 'pe-form', 'data-form': name }, ...children);
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      onSubmit(form);
    });
    return form;
  }

  textFields(name: string, label: string, value: Text | undefined, multiline = false): HTMLElement[] {
    const make = (lang: string, v: string) => (multiline ? textarea(`${name}_${lang}`, v) : input(`${name}_${lang}`, v));
    return [field(`${label} (EN)`, make('en', value?.en ?? '')), field(`${label} (FR)`, make('fr', value?.fr ?? ''))];
  }

  stageOptions(): [string, string][] {
    return [...this.doc.stages].sort((a, b) => a.number - b.number).map((s) => [String(s.number), `${t('stage_n', { n: s.number })} · ${text(s.name)}`]);
  }

  graphOptions(): [string, string][] {
    return this.doc.graphs.map((g) => [g.key, text(g.name)]);
  }

  graphForm(key: string | null): HTMLElement[] {
    const g = key ? this.doc.graphs.find((x) => x.key === key) : undefined;
    return [
      h('h3', {}, g ? t('edit_graph') : t('add_graph')),
      this.form('graph', (form) => {
        const payload = {
          name: textPair(form, 'name'),
          mod_id: formValue(form, 'mod_id') || null,
          branch: formValue(form, 'branch'),
          icon_item: formValue(form, 'icon_item') || null,
        };
        void this.act(g ? 'graph.update' : 'graph.create', g ? { key: g.key, ...payload } : { ...payload, key: formValue(form, 'key') || null }).then((d) => {
          if (d) this.showPanel({ kind: 'none' });
        });
      },
        ...this.textFields('name', t('name'), g?.name),
        g ? h('p', { class: 'pe-key' }, `${t('key')}: ${g.key}`) : field(t('key_optional'), input('key', '')),
        field(t('mod_id'), input('mod_id', g?.mod_id ?? '')),
        field(t('branch'), select('branch', [['tech', t('branch.tech')], ['magic', t('branch.magic')], ['neutral', t('branch.neutral')]], g?.branch ?? 'neutral')),
        field(t('icon_item'), input('icon_item', g?.icon_item ?? '', { placeholder: 'minecraft:book' })),
        h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('save')), button(t('cancel'), () => this.showPanel({ kind: 'none' }))),
      ),
    ];
  }

  stageForm(number: number | null): HTMLElement[] {
    const s = number !== null ? this.doc.stages.find((x) => x.number === number) : undefined;
    return [
      h('h3', {}, s ? t('edit_stage_n', { n: s.number }) : t('add_stage')),
      this.form('stage', (form) => {
        const payload = {
          name: textPair(form, 'name'),
          description: textPair(form, 'description'),
          threshold: Number(formValue(form, 'threshold')),
          module: parseCounts(formValue(form, 'module')),
        };
        void this.act(s ? 'stage.update' : 'stage.create', s ? { number: s.number, ...payload } : payload).then((d) => {
          if (d) this.showPanel({ kind: 'none' });
        });
      },
        ...this.textFields('name', t('name'), s?.name),
        ...this.textFields('description', t('description'), s?.description, true),
        field(t('threshold'), input('threshold', s?.threshold ?? 0.8, { type: 'number', step: '0.05', min: '0.05', max: '1' })),
        field(t('module'), textarea('module', formatCounts(s?.module))),
        h('p', { class: 'pe-hint' }, t('counts_hint')),
        h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('save')), button(t('cancel'), () => this.showPanel({ kind: 'none' }))),
      ),
    ];
  }

  newMilestoneForm(graph: string, stage: number): HTMLElement[] {
    if (!this.doc.graphs.length || !this.doc.stages.length) return [h('p', { class: 'pe-hint' }, t('need_graph_and_stage'))];
    return [
      h('h3', {}, t('add_milestone')),
      this.form('new-milestone', (form) => {
        void this.act('milestone.create', {
          title: textPair(form, 'title'),
          description: textPair(form, 'description'),
          graph: formValue(form, 'graph'),
          stage: Number(formValue(form, 'stage')),
          icon_type: formValue(form, 'icon_type'),
        }).then((d) => {
          if (d?.subject) this.showPanel({ kind: 'milestone', key: d.subject });
          if (d?.subject) this.handle?.select(d.subject);
        });
      },
        ...this.textFields('title', t('title'), undefined),
        ...this.textFields('description', t('description'), undefined, true),
        field(t('graph'), select('graph', this.graphOptions(), graph)),
        field(t('stage'), select('stage', this.stageOptions(), String(stage))),
        field(t('icon_type'), select('icon_type', ['tech', 'pipe', 'magic', 'default'].map((v) => [v, v]), 'tech')),
        h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('create')), button(t('cancel'), () => this.showPanel({ kind: 'none' }))),
      ),
    ];
  }

  milestonePanel(m: Milestone): HTMLElement[] {
    const deliver = m.validation?.type === 'deliver' ? m.validation.items : [];

    const details = this.form('milestone', (form) => {
      const payload: Record<string, unknown> = {
        key: m.key,
        title: textPair(form, 'title'),
        description: textPair(form, 'description'),
        stage: Number(formValue(form, 'stage')),
        graph: formValue(form, 'graph'),
        icon_type: formValue(form, 'icon_type'),
      };
      void this.act('milestone.update', payload);
    },
      ...this.textFields('title', t('title'), m.title),
      ...this.textFields('description', t('description'), m.description, true),
      field(t('graph'), select('graph', this.graphOptions(), m.graph)),
      field(t('stage'), select('stage', this.stageOptions(), String(m.stage))),
      field(t('icon_type'), select('icon_type', ['tech', 'pipe', 'magic', 'default'].map((v) => [v, v]), m.icon_type ?? 'tech')),
      h('p', { class: 'pe-hint' }, t(deliver.length ? 'validation_deliver' : 'validation_none')),
      h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('save')),
        button(t('delete_milestone'), () => {
          if (confirm(t('confirm_delete_milestone', { title: text(m.title) }))) void this.act('milestone.delete', { key: m.key });
        }, { class: 'is-danger', 'data-action': 'delete-milestone' })),
    );

    const requires = h('ul', { class: 'pe-list', 'data-list': 'requires' });
    for (const key of m.requires) {
      const other = this.milestone(key);
      requires.append(h('li', {}, h('span', {}, other ? text(other.title) : key),
        button('×', () => void this.act('link.delete', { from: key, to: m.key }), { 'aria-label': t('unlink'), title: t('unlink'), 'data-unlink': key })));
    }
    if (!m.requires.length) requires.append(h('li', { class: 'pe-hint' }, t('none')));

    return [
      h('h3', {}, text(m.title)),
      h('p', { class: 'pe-key' }, `${t('key')}: ${m.key}`),
      details,
      h('h4', {}, t('requires')), requires,
      h('h4', {}, t('unlocks')), this.unlockSection(m),
      h('h4', {}, t('requirements')), this.requirementSection(m, deliver),
    ];
  }

  unlockSection(m: Milestone): HTMLElement {
    const wrap = h('div', { 'data-section': 'unlocks' });
    const list = h('ul', { class: 'pe-list', 'data-list': 'unlocks' });
    const editor = h('div', {});
    const open = (unlock?: Milestone['unlocks'][number]) => {
      const recipe = unlock?.recipe as { ingredients?: { item?: string; tag?: string; count?: number }[]; result?: { item: string; count?: number }[]; energy?: number; time?: number } | null | undefined;
      const hasRecipe = h('input', { type: 'checkbox', name: 'has_recipe', checked: !!recipe });
      editor.replaceChildren(this.form('unlock', (form) => {
        const ingredients = parseCounts(formValue(form, 'ingredients')).map(({ item, count }) => (item.startsWith('#') ? { tag: item.slice(1), count } : { item, count }));
        const energy = formValue(form, 'energy');
        const time = formValue(form, 'time');
        void this.act('unlock.upsert', {
          key: m.key,
          item: formValue(form, 'item').trim(),
          original_item: unlock?.item ?? null,
          gate_mode: formValue(form, 'gate_mode'),
          recipes_to_ban: formValue(form, 'recipes_to_ban').split('\n').map((s) => s.trim()).filter(Boolean),
          recipe: hasRecipe.checked ? { ingredients, result: parseCounts(formValue(form, 'result')), energy: energy === '' ? null : Number(energy), time: time === '' ? null : Number(time) } : null,
        });
      },
        field(t('item'), input('item', unlock?.item ?? '', { placeholder: 'mekanism:steel_casing', required: true })),
        field(t('gate_mode'), select('gate_mode', [['crafting', t('gate.crafting')], ['machine', t('gate.machine')], ['ingredient', t('gate.ingredient')]], unlock?.gate_mode ?? 'crafting')),
        field(t('recipes_to_ban'), textarea('recipes_to_ban', (unlock?.recipes_to_ban ?? []).join('\n'))),
        h('label', { class: 'pe-check' }, hasRecipe, t('machine_recipe')),
        field(t('ingredients'), textarea('ingredients', formatCounts(recipe?.ingredients))),
        field(t('results'), textarea('result', formatCounts(recipe?.result))),
        h('div', { class: 'pe-row' }, field(t('energy'), input('energy', recipe?.energy, { type: 'number', min: '0' })), field(t('time'), input('time', recipe?.time, { type: 'number', min: '0' }))),
        h('p', { class: 'pe-hint' }, t('counts_hint')),
        h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('save')), button(t('cancel'), () => editor.replaceChildren())),
      ));
    };
    for (const u of m.unlocks) {
      list.append(h('li', { 'data-item': u.item }, h('span', {}, `${u.item} · ${t(`gate.${u.gate_mode}`)}${u.recipe ? ` · ${t('has_recipe')}` : ''}`),
        button('✎', () => open(u), { 'aria-label': t('edit'), title: t('edit'), 'data-edit-unlock': u.item }),
        button('×', () => void this.act('unlock.delete', { key: m.key, item: u.item }), { 'aria-label': t('delete'), title: t('delete'), 'data-delete-unlock': u.item })));
    }
    if (!m.unlocks.length) list.append(h('li', { class: 'pe-hint' }, t('none')));
    wrap.append(list, button(t('add_unlock'), () => open(), { 'data-action': 'add-unlock' }), editor);
    return wrap;
  }

  requirementSection(m: Milestone, items: { item: string; count: number }[]): HTMLElement {
    const wrap = h('div', { 'data-section': 'requirements' });
    const list = h('ul', { class: 'pe-list', 'data-list': 'requirements' });
    const editor = h('div', {});
    const open = (entry?: { item: string; count: number }) => {
      editor.replaceChildren(this.form('requirement', (form) => {
        void this.act('requirement.upsert', { key: m.key, item: formValue(form, 'item').trim(), original_item: entry?.item ?? null, count: Number(formValue(form, 'count')) });
      },
        field(t('item'), input('item', entry?.item ?? '', { placeholder: 'minecraft:iron_ingot', required: true })),
        field(t('amount'), input('count', entry?.count ?? 1, { type: 'number', min: '1', required: true })),
        h('div', { class: 'pe-row' }, h('button', { type: 'submit' }, t('save')), button(t('cancel'), () => editor.replaceChildren())),
      ));
    };
    for (const r of items) {
      list.append(h('li', { 'data-item': r.item }, h('span', {}, `${r.item} × ${r.count}`),
        button('✎', () => open(r), { 'aria-label': t('edit'), title: t('edit'), 'data-edit-requirement': r.item }),
        button('×', () => void this.act('requirement.delete', { key: m.key, item: r.item }), { 'aria-label': t('delete'), title: t('delete'), 'data-delete-requirement': r.item })));
    }
    if (!items.length) list.append(h('li', { class: 'pe-hint' }, t('none')));
    wrap.append(list, button(t('add_requirement'), () => open(), { 'data-action': 'add-requirement' }), editor);
    return wrap;
  }
}

if (root && config) {
  const editor = new Editor(root, config);
  editor.start().catch((error) => {
    root.dataset.ready = 'error';
    root.replaceChildren(h('p', { class: 'pe-error' }, t('load_failed')));
    console.error(error);
  });
}
