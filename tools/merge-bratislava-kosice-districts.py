#!/usr/bin/env python3
"""
Zlúč mestské okresy Bratislavy a Košíc v sk-districts.geojson.

Dôvod: kvíz Okresy SR s 79 položkami bol zbytočne podrobný — mestské
okresy BA I-V a KE I-IV sú geograficky jeden urban area. Hráč ich
nerozlišuje. Maroš (30.5.2026) povedal: zlúčiť do jedného „Bratislava"
a jedného „Košice" okresu.

Zlúčenie:
  - Bratislava I, II, III, IV, V → 1 feature „Bratislava" (MultiPolygon)
  - Košice I, II, III, IV → 1 feature „Košice" (MultiPolygon)

POZN.: Košice-okolie je samostatný vidiecky okres, NEZLUČUJE sa.

Výsledok: 79 → 72 features.

Bezpečné re-run: ak už feature „Bratislava" alebo „Košice" v súbore je,
neopakuje merge.
"""
import json, os, sys

PLUGIN_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
GEOJSON = os.path.join(PLUGIN_ROOT, 'public/data/regions/sk-districts.geojson')

BA_DISTRICTS = {'Bratislava I', 'Bratislava II', 'Bratislava III', 'Bratislava IV', 'Bratislava V'}
KE_DISTRICTS = {'Košice I', 'Košice II', 'Košice III', 'Košice IV'}


def feature_to_polygons(feat):
    """Vráti list polygónov (každý polygon = list of rings) z Polygon/MultiPolygon feature."""
    g = feat.get('geometry', {})
    t = g.get('type')
    c = g.get('coordinates', [])
    if t == 'Polygon':
        return [c]
    if t == 'MultiPolygon':
        return list(c)
    return []


def main():
    if not os.path.exists(GEOJSON):
        sys.exit(f'NOT FOUND: {GEOJSON}')
    with open(GEOJSON) as f:
        gj = json.load(f)
    names = [f['properties'].get('name') for f in gj['features']]

    if 'Bratislava' in names and 'Košice' in names:
        print('Already merged (found "Bratislava" + "Košice" in features). Nothing to do.')
        return

    new_features = []
    ba_polys = []
    ke_polys = []
    for feat in gj['features']:
        name = feat.get('properties', {}).get('name')
        if name in BA_DISTRICTS:
            ba_polys.extend(feature_to_polygons(feat))
            continue
        if name in KE_DISTRICTS:
            ke_polys.extend(feature_to_polygons(feat))
            continue
        new_features.append(feat)

    if not ba_polys:
        print('WARN: no Bratislava I-V districts found.')
    else:
        new_features.append({
            'type': 'Feature',
            'properties': {'name': 'Bratislava'},
            'geometry': {'type': 'MultiPolygon', 'coordinates': ba_polys},
        })
        print(f'  + Bratislava (MultiPolygon, {len(ba_polys)} sub-polygons)')

    if not ke_polys:
        print('WARN: no Košice I-IV districts found.')
    else:
        new_features.append({
            'type': 'Feature',
            'properties': {'name': 'Košice'},
            'geometry': {'type': 'MultiPolygon', 'coordinates': ke_polys},
        })
        print(f'  + Košice (MultiPolygon, {len(ke_polys)} sub-polygons)')

    gj['features'] = new_features
    with open(GEOJSON, 'w') as f:
        json.dump(gj, f, ensure_ascii=False, separators=(',', ':'))
    size = os.path.getsize(GEOJSON)
    print(f'Saved {GEOJSON} ({size} bytes, {len(gj["features"])} features total)')


if __name__ == '__main__':
    main()
