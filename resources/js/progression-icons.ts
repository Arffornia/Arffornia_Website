/**
 * Two icon indexes: the id-keyed manifest of the item-icons bucket (`{format, items: {"ns:path": hash}}`,
 * scripts/icons/README.md) in api mode, and the fixture's map of item id to a file path under iconBase.
 */
export type IconIndex = { format: number; items: Record<string, string> } | Record<string, string>;

export function iconResolver(index: IconIndex, base: string): (item: string) => string | null {
  if (typeof index.format === 'number' && typeof index.items === 'object') {
    const items = (index as { items: Record<string, string> }).items;
    return (item) => {
      const hash = items[item];
      if (!hash) return null;
      const [ns, path] = item.split(':', 2);
      return `${base}${encodeURIComponent(ns)}/${path.split('/').map(encodeURIComponent).join('/')}.png?v=${hash}`;
    };
  }
  const files = index as Record<string, string>;
  return (item) => (files[item] ? base + files[item].split('/').map(encodeURIComponent).join('/') : null);
}
