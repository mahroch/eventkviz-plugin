#!/usr/bin/env python3
"""
Rozšír sk-rivers.geojson o 15 ďalších riek (Tier 1+2 zo zadania 30.5.2026).

Tier 1 (7): Laborec, Latorica, Torysa, Orava, Kysuca, Bodva, Belá
Tier 2 (8): Rimava, Turiec, Žitava, Myjava, Uh, Cirocha, Rajčianka, Slatina

Vstup: surové OSM dáta v /tmp/ek-rivers/<Meno>.json (stiahnuté Overpass-om
       cez waterway=river + area.sk; pre Rajčianku stiahnuté pod menom
       "Rajčanka" a uložené ako "Rajčianka.json").

Výstup: append features do public/data/regions/sk-rivers.geojson.
Bezpečné re-run: preskočí rieky ktoré už v geojson existujú podľa `name`.

Konvenncia (zhoda s extend-rieky.php):
  - Simplify každý 5. bod (+ vždy prvý a posledný v segmente)
  - Súradnice round na 4 desatinné miesta
  - LineString ak 1 way, MultiLineString ak viac
"""
import json, os, sys

PLUGIN_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
GEOJSON = os.path.join(PLUGIN_ROOT, 'public/data/regions/sk-rivers.geojson')
SRC_DIR = '/tmp/ek-rivers'
SIMPLIFY_N = 5

# OSM name (mapping na žiadané meno v datasete kvôli pravopisnej variante)
RIVERS = [
    ('Laborec',   'Laborec'),
    ('Latorica',  'Latorica'),
    ('Torysa',    'Torysa'),
    ('Orava',     'Orava'),
    ('Kysuca',    'Kysuca'),
    ('Bodva',     'Bodva'),
    ('Belá',      'Belá'),
    ('Rimava',    'Rimava'),
    ('Turiec',    'Turiec'),
    ('Žitava',    'Žitava'),
    ('Myjava',    'Myjava'),
    ('Uh',        'Uh'),
    ('Cirocha',   'Cirocha'),
    ('Rajčianka', 'Rajčianka'),  # OSM má 'Rajčanka', súbor uložený pod SK preferovaným menom
    ('Slatina',   'Slatina'),
]


def simplify(coords):
    out = []
    n = len(coords)
    for i, c in enumerate(coords):
        if i == 0 or i == n - 1 or i % SIMPLIFY_N == 0:
            out.append([round(c[0], 4), round(c[1], 4)])
    return out


def load_geojson(path):
    with open(path) as f:
        return json.load(f)


def main():
    if not os.path.exists(GEOJSON):
        sys.exit(f'NOT FOUND: {GEOJSON}')
    gj = load_geojson(GEOJSON)
    existing = {f['properties']['name'] for f in gj['features'] if f.get('properties', {}).get('name')}
    print(f'Existing rivers: {sorted(existing)}')

    added = 0
    for save_name, _ in RIVERS:
        if save_name in existing:
            print(f'  skip {save_name} (already in geojson)')
            continue
        src = os.path.join(SRC_DIR, f'{save_name}.json')
        if not os.path.exists(src):
            print(f'  WARN: source missing {src}')
            continue
        osm = load_geojson(src)
        ways = [e for e in osm.get('elements', []) if e.get('type') == 'way' and e.get('geometry')]
        lines = []
        for w in ways:
            raw = [(g['lon'], g['lat']) for g in w['geometry']]
            simp = simplify(raw)
            if len(simp) >= 2:
                lines.append(simp)
        if not lines:
            print(f'  WARN: no ways for {save_name}')
            continue
        feat = {
            'type': 'Feature',
            'properties': {'name': save_name},
            'geometry': {
                'type': 'MultiLineString' if len(lines) > 1 else 'LineString',
                'coordinates': lines if len(lines) > 1 else lines[0],
            },
        }
        gj['features'].append(feat)
        pts = sum(len(l) for l in lines)
        print(f'  + {save_name:12s} ({len(lines)} ways, {pts} pts)')
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
