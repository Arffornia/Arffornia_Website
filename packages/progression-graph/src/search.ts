/** Lower case, accents removed, so "creer" finds "Créer". */
export function fold(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function subsequence(q: string, t: string): number | null {
  let score = 0;
  let ti = 0;
  let run = 0;
  for (const ch of q) {
    let found = -1;
    while (ti < t.length) {
      if (t[ti] === ch) {
        found = ti;
        break;
      }
      ti++;
      run = 0;
    }
    if (found < 0) return null;
    const wordStart = found === 0 || /[\s:_/\-.(]/.test(t[found - 1]);
    run++;
    score += 1 + (wordStart ? 4 : 0) + (run > 1 ? 2 : 0);
    ti = found + 1;
  }
  return score;
}

/**
 * Score of `text` for `query`, higher is better, null when it does not match. Each word of the query
 * must match: whole at a word start scores most, then inside a word, then as scattered letters.
 */
export function fuzzyScore(query: string, text: string): number | null {
  const words = fold(query).split(/\s+/).filter(Boolean);
  if (words.length === 0) return 0;
  const t = fold(text);
  let score = 0;
  for (const w of words) {
    const at = new RegExp(`(^|[\\s:_/\\-.(])${w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`).exec(t);
    if (at) score += 30 + w.length * 3 + (at.index === 0 ? 4 : 0);
    else if (t.includes(w)) score += 15 + w.length * 2;
    else {
      const loose = subsequence(w, t);
      if (loose === null) return null;
      score += loose;
    }
  }
  return score - t.length * 0.05;
}

export interface Ranked<T> {
  entry: T;
  score: number;
}

export function rank<T>(query: string, entries: T[], text: (entry: T) => string, limit = 12): Ranked<T>[] {
  const out: Ranked<T>[] = [];
  for (const entry of entries) {
    const score = fuzzyScore(query, text(entry));
    if (score !== null) out.push({ entry, score });
  }
  return out.sort((a, b) => b.score - a.score).slice(0, limit);
}
