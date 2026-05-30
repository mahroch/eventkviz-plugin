#!/usr/bin/env python3
"""
Rozšír sk-mountains.geojson o 15 ďalších pohorí (Tier 1+2 zo zadania 30.5.2026).

Tier 1 (7): Považský Inovec, Vtáčnik, Kremnické vrchy, Volovské vrchy, Pieniny, Čergov, Javorníky
Tier 2 (8): Bukovské vrchy, Vihorlatské vrchy, Spišská Magura, Krupinská planina,
            Cerová vrchovina, Čierna hora, Kysucké Beskydy, Žiar

Vstup: surové OSM dáta v /tmp/ek-mountains/<Meno>.json (Overpass `out geom;`
       na relation s `natural=mountain_range` alebo `boundary=geomorphological-unit`).

Pipeline:
  1. assemble outer-role members do uzavretých polygon ringov
  2. simplify: round 5 dp + keep každý 3. bod (+ first/last)
  3. append do public/data/regions/sk-mountains.geojson ako Polygon/MultiPolygon

Bezpečné re-run: preskočí pohoria ktoré už v geojson existujú podľa name.
"""
import json, os, sys

PLUGIN_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
GEOJSON = os.path.join(PLUGIN_ROOT, 'public/data/regions/sk-mountains.geojson')
SRC_DIR = '/tmp/ek-mountains'
SIMPLIFY_N = 3
ROUND_DP = 5
EPS = 1e-7  # node match tolerance

MOUNTAINS = [
    'Považský Inovec','Vtáčnik','Kremnické vrchy','Volovské vrchy','Pieniny','Čergov','Javorníky',
    'Bukovské vrchy','Vihorlatské vrchy','Spišská Magura','Krupinská planina','Cerová vrchovina',
    'Čierna hora','Kysucké Beskydy','Žiar',
]


def _close(a, b):
    return abs(a[0]-b[0]) < EPS and abs(a[1]-b[1]) < EPS


def assemble_rings(ways):
    """ways: list of [(lon,lat), ...] coords from OSM outer members.
    Returns list of closed rings (each a list of (lon,lat)).
    Greedily chains ways by shared endpoints, reversing as needed."""
    remaining = [list(w) for w in ways if len(w) >= 2]
    rings = []
    while remaining:
        ring = remaining.pop(0)
        # Static guard: bounded by initial remaining count (each step removes one).
        # Bug fix: dynamic `10 * len(remaining)` exits prematurely when only 1-2
        # ways are left but guard counter has grown — `len(remaining)` shrinks
        # faster than guard grows.
        max_steps = len(remaining) + 5
        guard = 0
        while not _close(ring[0], ring[-1]) and remaining and guard < max_steps:
            guard += 1
            attached = False
            for i, w in enumerate(remaining):
                if _close(w[0], ring[-1]):
                    ring.extend(w[1:])
                    remaining.pop(i); attached = True; break
                if _close(w[-1], ring[-1]):
                    ring.extend(reversed(w[:-1]))
                    remaining.pop(i); attached = True; break
                if _close(w[-1], ring[0]):
                    ring = w[:-1] + ring
                    remaining.pop(i); attached = True; break
                if _close(w[0], ring[0]):
                    ring = list(reversed(w))[:-1] + ring
                    remaining.pop(i); attached = True; break
            if not attached:
                break
        if _close(ring[0], ring[-1]):
            rings.append(ring)
        else:
            # unclosed — drop (data inconsistency)
            print(f'   ! dropped unclosed ring with {len(ring)} pts')
    return rings


def simplify_ring(ring):
    """Round + keep every Nth point (+ always first/last for closure)."""
    n = len(ring)
    out = []
    for i, (lon, lat) in enumerate(ring):
        if i == 0 or i == n - 1 or i % SIMPLIFY_N == 0:
            out.append([round(lon, ROUND_DP), round(lat, ROUND_DP)])
    # Ensure closed
    if out[0] != out[-1]:
        out.append(out[0])
    return out


def feature_for(name, raw_osm):
    rels = [e for e in raw_osm.get('elements', []) if e.get('type') == 'relation']
    if not rels:
        return None
    rel = rels[0]  # first relation
    outer_ways = []
    for mem in rel.get('members', []):
        if mem.get('role') != 'outer' or mem.get('type') != 'way':
            continue
        coords = [(g['lon'], g['lat']) for g in mem.get('geometry', []) or []]
        if len(coords) >= 2:
            outer_ways.append(coords)
    if not outer_ways:
        return None
    rings = assemble_rings(outer_ways)
    if not rings:
        return None
    simplified = [simplify_ring(r) for r in rings]
    if len(simplified) == 1:
        geom = {'type': 'Polygon', 'coordinates': simplified}
    else:
        geom = {'type': 'MultiPolygon', 'coordinates': [[r] for r in simplified]}
    return {
        'type': 'Feature',
        'properties': {'name': name},
        'geometry': geom,
    }


def main():
    if not os.path.exists(GEOJSON):
        sys.exit(f'NOT FOUND: {GEOJSON}')
    with open(GEOJSON) as f:
        gj = json.load(f)
    existing = {f['properties']['name'] for f in gj['features'] if f.get('properties', {}).get('name')}
    print(f'Existing mountains: {sorted(existing, key=lambda s: s.lower())}')

    added = 0
    for m in MOUNTAINS:
        if m in existing:
            print(f'  skip {m} (already in geojson)')
            continue
        src = os.path.join(SRC_DIR, f'{m}.json')
        if not os.path.exists(src):
            print(f'  WARN: source missing {src}')
            continue
        with open(src) as f:
            osm = json.load(f)
        feat = feature_for(m, osm)
        if not feat:
            print(f'  WARN: no polygon assembled for {m}')
            continue
        gj['features'].append(feat)
        g = feat['geometry']
        if g['type'] == 'Polygon':
            pts = sum(len(r) for r in g['coordinates'])
            rings = len(g['coordinates'])
        else:
            pts = sum(sum(len(r) for r in poly) for poly in g['coordinates'])
            rings = len(g['coordinates'])
        print(f'  + {m:22s} {g["type"]:12s} rings={rings} pts={pts}')
        added += 1

    if added == 0:
        print('Nothing to add.')
        return
    with open(GEOJSON, 'w') as f:
        json.dump(gj, f, ensure_ascii=False, separators=(',', ':'))
    size = os.path.getsize(GEOJSON)
    print(f'Saved {GEOJSON} ({size} bytes, {len(gj["features"])} features total)')


if __name__ == '__main__':
    main()
