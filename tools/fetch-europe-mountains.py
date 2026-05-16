#!/usr/bin/env python3
"""Vyextrahuje top európske pohoria z Natural Earth ne_10m_geography_regions_polys.
Source: https://github.com/martynafford/natural-earth-geojson (martynafford fork — same NE data ako shapefiles, ale priamo GeoJSON).
"""
import json
import urllib.request

NE_URL = "https://raw.githubusercontent.com/martynafford/natural-earth-geojson/master/10m/physical/ne_10m_geography_regions_polys.json"
UA = "EventkvizPlugin/1.8 WP-Bot"

# OSM name (NE) → slovenský label
NAME_MAP = {
    "ALPS": "Alpy",
    "CARPATHIAN MOUNTAINS": "Karpaty",
    "PYRENEES": "Pyreneje",
    "Tatra Mts.": "Tatry",
    "Vosges": "Vogézy",
    "Sudetes Mts.": "Sudety",
    "Balkan Mts.": "Balkán",
    "Dinaric Alps": "Dinárske vrchy",
    "Transylvanian Alps": "Južné Karpaty",
    "URAL MOUNTAINS": "Ural",
    "CAUCASUS MTS.": "Kavkaz",
    "Lesser Caucasus": "Malý Kaukaz",
}


def simplify(coords, n=3):
    if len(coords) <= 4: return coords
    out = [coords[i] for i in range(0, len(coords) - 1, n)]
    out.append(coords[-1])
    if out[0] != out[-1]: out.append(out[0])
    return out


def simplify_geom(geom, n=3):
    if geom["type"] == "Polygon":
        geom["coordinates"] = [simplify(r, n) for r in geom["coordinates"]]
    elif geom["type"] == "MultiPolygon":
        geom["coordinates"] = [[simplify(r, n) for r in p] for p in geom["coordinates"]]
    return geom


def main():
    print("Fetching Natural Earth regions…")
    req = urllib.request.Request(NE_URL, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=60) as r:
        data = json.loads(r.read().decode("utf-8"))
    print(f"  Got {len(data['features'])} NE features")

    features = []
    for f in data["features"]:
        name = f["properties"].get("name", "")
        if name not in NAME_MAP:
            continue
        geom = simplify_geom(f["geometry"], n=3)
        features.append({
            "type": "Feature",
            "properties": {"name": NAME_MAP[name], "name_ne": name},
            "geometry": geom,
        })
        print(f"  OK: {NAME_MAP[name]:25s} ← NE '{name}'")

    missing = [v for k, v in NAME_MAP.items() if v not in {feat["properties"]["name"] for feat in features}]
    if missing:
        print(f"\n  MISSING: {missing}")

    out_path = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/europe-mountains.geojson"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump({"type": "FeatureCollection", "features": features}, f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nWrote {len(features)} features → {out_path}")


if __name__ == "__main__":
    main()
