#!/usr/bin/env python3
"""
Vytvor sk-chko.geojson — 14 chránených krajinných oblastí SR (CHKO).

Zoznam: Biele Karpaty, Cerová vrchovina, Dunajské luhy, Horná Orava,
        Kysuce, Latorica, Malé Karpaty, Poľana, Ponitrie, Strážovské vrchy,
        Štiavnické vrchy, Vihorlat, Východné Karpaty, Záhorie.

Vstup: surové OSM dáta v /tmp/ek-chko/<MenoCHKO>.json (Overpass `out geom;`
       relation s `boundary=protected_area`+`protection_title=Chránená krajinná oblasť`
       v SK area; ku každému relationu sme priradili krátky názov bez prefixu).

Pipeline (analogicky pohoria — extend-sk-mountains-tier1-2.py):
  1. assemble outer-role way members do uzavretých polygonov (greedy chain + reverz)
  2. CHKO môžu mať viacero separate polygonov → MultiPolygon
  3. simplify: round 5 dp + keep každý 3. bod
  4. write public/data/regions/sk-chko.geojson ako FeatureCollection
"""
import json, os, sys

PLUGIN_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
GEOJSON = os.path.join(PLUGIN_ROOT, 'public/data/regions/sk-chko.geojson')
SRC_DIR = '/tmp/ek-chko'
SIMPLIFY_N = 3
ROUND_DP = 5
EPS = 1e-7

CHKO_LIST = [
    'Biele Karpaty', 'Cerová vrchovina', 'Dunajské luhy', 'Horná Orava',
    'Kysuce', 'Latorica', 'Malé Karpaty', 'Poľana', 'Ponitrie', 'Strážovské vrchy',
    'Štiavnické vrchy', 'Vihorlat', 'Východné Karpaty', 'Záhorie',
]


def _close(a, b):
    return abs(a[0]-b[0]) < EPS and abs(a[1]-b[1]) < EPS


def assemble_rings(ways):
    remaining = [list(w) for w in ways if len(w) >= 2]
    rings = []
    while remaining:
        ring = remaining.pop(0)
        max_steps = len(remaining) + 5
        guard = 0
        while not _close(ring[0], ring[-1]) and remaining and guard < max_steps:
            guard += 1
            attached = False
            for i, w in enumerate(remaining):
                if _close(w[0], ring[-1]):
                    ring.extend(w[1:]); remaining.pop(i); attached = True; break
                if _close(w[-1], ring[-1]):
                    ring.extend(reversed(w[:-1])); remaining.pop(i); attached = True; break
                if _close(w[-1], ring[0]):
                    ring = w[:-1] + ring; remaining.pop(i); attached = True; break
                if _close(w[0], ring[0]):
                    ring = list(reversed(w))[:-1] + ring; remaining.pop(i); attached = True; break
            if not attached:
                break
        if _close(ring[0], ring[-1]):
            rings.append(ring)
        else:
            print(f'   ! dropped unclosed ring with {len(ring)} pts')
    return rings


def simplify_ring(ring):
    n = len(ring)
    out = []
    for i, (lon, lat) in enumerate(ring):
        if i == 0 or i == n - 1 or i % SIMPLIFY_N == 0:
            out.append([round(lon, ROUND_DP), round(lat, ROUND_DP)])
    if out[0] != out[-1]:
        out.append(out[0])
    return out


def feature_for(name, raw_osm):
    rels = [e for e in raw_osm.get('elements', []) if e.get('type') == 'relation']
    if not rels:
        return None
    rel = rels[0]
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
    features = []
    for chko in CHKO_LIST:
        src = os.path.join(SRC_DIR, f'{chko}.json')
        if not os.path.exists(src):
            print(f'  WARN: source missing {src}')
            continue
        with open(src) as f:
            osm = json.load(f)
        feat = feature_for(chko, osm)
        if not feat:
            print(f'  WARN: no polygon assembled for {chko}')
            continue
        features.append(feat)
        g = feat['geometry']
        if g['type'] == 'Polygon':
            pts = sum(len(r) for r in g['coordinates'])
            print(f'  + {chko:22s} Polygon       pts={pts}')
        else:
            pts = sum(sum(len(r) for r in poly) for poly in g['coordinates'])
            print(f'  + {chko:22s} MultiPolygon  polys={len(g["coordinates"])} pts={pts}')

    gj = {'type': 'FeatureCollection', 'features': features}
    with open(GEOJSON, 'w') as f:
        json.dump(gj, f, ensure_ascii=False, separators=(',', ':'))
    size = os.path.getsize(GEOJSON)
    print(f'\nSaved {GEOJSON} ({size} bytes, {len(features)} features)')


if __name__ == '__main__':
    main()
