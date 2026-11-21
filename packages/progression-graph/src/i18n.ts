import type { Locale, Text } from './types.ts';

const STRINGS = {
  en: {
    title: 'Progression',
    stage: 'Stage :n',
    milestones: ':done / :total milestones',
    threshold_left: '{b}:left more milestones{/b} for the :pct % threshold, then deliver the module to open stage :next',
    threshold_met: ':pct % threshold reached: deliver the module to open stage :next',
    elevator: 'Space Elevator',
    elevator_phase: 'Phase :n / :total',
    module_empty: 'No module defined for this stage yet',
    module_hint: 'Delivered with the stage threshold, it opens stage :next',
    catalog: 'All milestones, without a player',
    all: 'All',
    search: 'Search a milestone or an item',
    done: 'Done',
    available: 'Available',
    locked: 'Locked',
    incoming: 'Required by the selection',
    outgoing: 'Unlocked by the selection',
    backward: 'Requires a later stage (data error)',
    zoom_in: 'Zoom in',
    zoom_out: 'Zoom out',
    fit: 'Fit to screen',
    theme_dark: 'Switch to dark theme',
    theme_light: 'Switch to light theme',
    close: 'Close',
    stage_of: 'Stage :n',
    validate_obtain: 'Obtain one of',
    validate_craft: 'Craft one of',
    validate_deliver: 'Deliver',
    validate_none: 'No validation defined yet',
    unlocks: 'Unlocks',
    requires: 'Requires',
    leads_to: 'Leads to',
    other_graph: 'other graph: :graph',
    gate_machine: 'machine recipe',
    gate_ingredient: 'Arffornia ingredient',
    gate_crafting: 'locked until the stage',
    nothing: 'Nothing',
    no_match: 'No milestone matches',
  },
  fr: {
    title: 'Progression',
    stage: 'Stage :n',
    milestones: ':done / :total milestones',
    threshold_left: 'Encore {b}:left milestones{/b} pour le seuil de :pct %, puis livre le module pour ouvrir le stage :next',
    threshold_met: 'Seuil de :pct % atteint : livre le module pour ouvrir le stage :next',
    elevator: 'Ascenseur orbital',
    elevator_phase: 'Phase :n / :total',
    module_empty: "Pas encore de module pour ce stage",
    module_hint: 'Livré avec le seuil du stage, il ouvre le stage :next',
    catalog: 'Toutes les milestones, sans joueur',
    all: 'Tout',
    search: 'Chercher une milestone ou un item',
    done: 'Fait',
    available: 'Disponible',
    locked: 'Verrouillé',
    incoming: 'Requis par la sélection',
    outgoing: 'Débloqué par la sélection',
    backward: 'Demande un stage ultérieur (erreur de données)',
    zoom_in: 'Zoomer',
    zoom_out: 'Dézoomer',
    fit: 'Tout afficher',
    theme_dark: 'Passer au thème sombre',
    theme_light: 'Passer au thème clair',
    close: 'Fermer',
    stage_of: 'Stage :n',
    validate_obtain: "Obtenir l'un de",
    validate_craft: "Fabriquer l'un de",
    validate_deliver: 'Livrer',
    validate_none: 'Validation pas encore définie',
    unlocks: 'Débloque',
    requires: 'Prérequis',
    leads_to: 'Mène à',
    other_graph: 'autre graph : :graph',
    gate_machine: 'recette machine',
    gate_ingredient: 'ingrédient Arffornia',
    gate_crafting: 'verrouillé jusqu’au stage',
    nothing: 'Rien',
    no_match: 'Aucune milestone ne correspond',
  },
} as const;

export type MessageKey = keyof (typeof STRINGS)['en'];

export function translator(locale: Locale) {
  const table = STRINGS[locale] ?? STRINGS.en;
  const fmt = new Intl.NumberFormat(locale === 'fr' ? 'fr-FR' : 'en-US');
  return {
    t(key: MessageKey, params: Record<string, string | number> = {}): string {
      return table[key].replace(/:([a-z]+)/g, (all, name: string) =>
        name in params ? (typeof params[name] === 'number' ? fmt.format(params[name] as number) : String(params[name])) : all,
      );
    },
    text(value: Text | undefined): string {
      if (!value) return '';
      return (locale === 'fr' && value.fr) || value.en;
    },
    number: (n: number) => fmt.format(n),
  };
}

export type Translator = ReturnType<typeof translator>;
