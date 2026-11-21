# Item icons

3D inventory renders of every item of the pack, named by registry id, served at `/icons/` on the
website host from the `item-icons` Garage bucket (route: TheGostsniperfr/infra,
`k8s/config/garage-s3/values-secure-route.yaml`). Used by the progression graph viewer, the site and
the in-game web screens.

## Contract

| URL | What | Cache |
|---|---|---|
| `/icons/{size}/{namespace}/{path}.png?v={hash}` | Icon of item `namespace:path`, `size` 36 or 128, transparent PNG. `path` may hold `/` | 7 days; `?v` busts it |
| `/icons/{size}/_fallback.png` | Neutral placeholder for ids missing from the manifest | 7 days |
| `/icons/manifest.json` | `{format: 1, generated_at, sizes, count, items: {"ns:path": hash}, failed: [ids]}` | 5 minutes |

`hash` is the first 12 hex characters of the SHA-256 of the 128 px PNG: it changes only when the icon
changes, so clients cache by `(id, hash)`. An id that is neither in `items` nor exported (`failed`:
renderers that need a world, about 0.25 % of the pack) gets the fallback. The manifest is about 1.3 MB:
read it once per page or once per game session, not per icon.

## Refresh after a mod change

```bash
scripts/icons/export.sh <pack-client-dir> <out-dir>   # ~1 min of rendering on a real display
scripts/icons/publish.sh <out-dir>                      # PNGs, then manifest, then deletions
```

- `export.sh` reads a launcher install of the pack (`mods/`, `config/`...) and runs the client on a
  throwaway copy with `-Darffornia.exportItemIcons` (Arffornia_Mods `client/icon/ItemIconExport`).
  The pack's `arffornia.jar` must include the exporter, or pass `ARFFORNIA_JAR=<jar>`. Needs a display,
  Java 21 and Nix. It uses 8 GB of heap.
- `publish.sh` needs `kubectl` on the cluster (context `main-k8s-admin@kubernetes`, or `KUBE_CONTEXT`):
  it reads the upload key `item-icons-key` from Garage at run time and never stores it.
