#!/usr/bin/env python3
"""Vytvorí europe-mountains.geojson s ručne definovanými polygónmi pre 12 top
európskych pohorí. Predtým sme používali Natural Earth ne_10m_geography_regions_polys,
ale tieto polygony boli väčšinou schematické (Tatry = úzky obdĺžnik, Sudety = 3
nesúvislé kúsky, Kaukaz mimo viewport). Ručne definované polygony dávajú lepší
geo feel + dostatočnú plochu na klik.

Output: public/data/regions/europe-mountains.geojson
"""
import json

# Každá entry: (label, polygon coordinates ako [(lon, lat), ...]).
# Polygony sú jednoduché 5-12 bodové aproximácie reálnej rozlohy pohoria.
# Body uvedené ako (lon, lat) — GeoJSON konvencia.
MOUNTAINS = [
    ("Alpy", [
        (7.0, 43.7), (6.4, 45.8), (7.5, 47.2), (9.5, 47.7), (13.0, 47.5),
        (16.5, 47.5), (16.0, 46.5), (14.0, 45.5), (11.0, 44.5), (7.5, 44.0),
        (7.0, 43.7),
    ]),
    ("Karpaty", [
        (22.0, 44.6), (25.5, 45.2), (26.5, 46.5), (25.5, 48.0), (23.5, 49.5),
        (21.5, 49.7), (20.0, 49.5), (18.5, 49.1), (17.5, 48.5), (18.0, 47.5),
        (21.0, 45.5), (22.5, 44.5), (22.0, 44.6),
    ]),
    ("Pyreneje", [
        (-1.9, 42.2), (-1.0, 42.7), (0.5, 43.0), (2.0, 43.0), (3.2, 42.6),
        (2.5, 42.3), (0.5, 42.4), (-1.0, 42.2), (-1.9, 42.2),
    ]),
    ("Apeniny", [
        (7.7, 44.3), (9.5, 44.5), (12.0, 43.5), (13.5, 42.5), (16.0, 40.5),
        (16.5, 39.0), (15.5, 38.0), (16.0, 39.5), (14.0, 41.0), (12.5, 42.5),
        (10.5, 43.5), (8.5, 44.2), (7.7, 44.3),
    ]),
    ("Škandinávske vrchy", [
        (5.0, 58.0), (5.0, 60.5), (8.0, 63.0), (13.0, 67.0), (18.0, 68.5),
        (20.5, 69.0), (20.0, 68.0), (16.0, 66.0), (12.0, 63.0), (9.0, 60.5),
        (6.5, 58.5), (5.0, 58.0),
    ]),
    ("Tatry", [
        (19.55, 49.05), (19.65, 49.30), (20.10, 49.30), (20.40, 49.25),
        (20.45, 49.05), (20.10, 49.00), (19.65, 49.00), (19.55, 49.05),
    ]),
    ("Sudety", [
        (14.7, 50.3), (15.5, 50.8), (16.6, 50.9), (17.3, 50.5),
        (17.0, 50.1), (15.5, 50.0), (14.7, 50.1), (14.7, 50.3),
    ]),
    ("Balkán", [
        (22.5, 42.6), (23.5, 43.2), (25.5, 43.2), (27.5, 43.0), (27.7, 42.7),
        (25.0, 42.5), (22.5, 42.5), (22.5, 42.6),
    ]),
    ("Dinárske vrchy", [
        (14.2, 44.0), (14.5, 45.5), (15.5, 46.3), (17.0, 46.2), (19.0, 44.5),
        (20.5, 42.5), (19.5, 42.0), (17.0, 43.0), (14.2, 44.0),
    ]),
    ("Južné Karpaty", [
        (22.5, 45.2), (23.5, 45.8), (25.5, 45.8), (26.5, 45.5), (26.0, 45.2),
        (24.5, 45.0), (22.8, 45.0), (22.5, 45.2),
    ]),
    ("Kavkaz", [
        (39.5, 41.5), (41.0, 43.5), (43.5, 44.0), (46.0, 43.8), (48.5, 43.0),
        (49.5, 42.0), (47.0, 41.5), (44.0, 41.0), (41.0, 41.2), (39.5, 41.5),
    ]),
    ("Ural", [
        (56.5, 51.0), (57.5, 55.0), (58.5, 60.0), (60.0, 65.0), (65.0, 68.0),
        (67.0, 67.0), (62.5, 65.0), (60.0, 60.0), (59.5, 55.0), (58.5, 51.0),
        (57.5, 49.5), (56.5, 51.0),
    ]),
]


def main():
    features = []
    for label, coords in MOUNTAINS:
        # uistime sa že je polygon uzavretý
        if coords[0] != coords[-1]:
            coords = coords + [coords[0]]
        features.append({
            "type": "Feature",
            "properties": {"name": label},
            "geometry": {"type": "Polygon", "coordinates": [list(c) for c in [coords]]},
        })
        print(f"  OK: {label} ({len(coords)} points)")

    out_path = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/europe-mountains.geojson"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump({"type": "FeatureCollection", "features": features}, f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nWrote {len(features)} features → {out_path}")


if __name__ == "__main__":
    main()
