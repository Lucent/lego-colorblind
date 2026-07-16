# color.brick.design

Colorblindness makes it hard to tell whether LEGO parts in a set are actually different colors. Enter a set name or number and it lists every part that comes in colors similar enough to confuse, for a chosen color vision deficiency, ordered most confusing first.

Each part's colors are simulated as the selected vision type would see them — Viénot 1999 for protanopia and deuteranopia, Brettel 1997 for tritanopia (ported from public-domain [libDaltonLens](https://daltonlens.org)), and a luminance projection for achromatopsia. A severity slider interpolates from normal vision to full dichromacy and regroups the parts in real time; swatches next to each part show the simulated colors. Colors whose CIEDE2000 distance falls under a threshold are grouped as confusable, with a dim-light option that lowers lightness before comparison. Transparent and solid parts are never considered confusable with each other.

## How it works

The browser does all the color math and rendering with vanilla ES modules. No framework, no build step. Part figures are created once per set; moving the controls only recomputes groups and moves nodes.

A small Node server (`server/server.mjs`, no dependencies) serves the static files, proxies two Rebrickable API endpoints so the API key stays private (following pagination, caching responses on disk for a month), and regenerates the color table and set autocomplete list from Rebrickable's published CSV dumps on boot and daily. Everything derived lives in `cache/`; all writes are atomic, so the container converges to a working state from any starting point, including an empty cache.

## Running

```
cp .env.example .env    # add a Rebrickable API key and Cloudflare tunnel token
docker compose up -d
```

Stock `node:alpine` with the repo bind-mounted read-only — no image to build. Traffic arrives through the Cloudflare tunnel; nothing is exposed on the host.

For local development:

```
REBRICKABLE_API_KEY=... node server/server.mjs
node --test test/
```

The tests verify CIEDE2000 against the Sharma reference pairs and the simulation and grouping pipeline against known confusable LEGO color pairs.
