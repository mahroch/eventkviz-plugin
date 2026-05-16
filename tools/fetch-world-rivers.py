#!/usr/bin/env python3
"""Top svetové rieky z Natural Earth ne_10m_rivers_lake_centerlines."""
import json
import urllib.request

NE_URL = "https://raw.githubusercontent.com/martynafford/natural-earth-geojson/master/10m/physical/ne_10m_rivers_lake_centerlines.json"

# Top 15 svetových riek — slovenské názvy
TOP_RIVERS = {
    "Amazon": "Amazonka",
    "Amazonas": "Amazonka",
    "Nile": "Níl",
    "Yangtze": "Jang-c’-ťiang",
    "Chang Jiang": "Jang-c’-ťiang",
    "Mississippi": "Mississippi",
    "Yenisey": "Jenisej",
    "Yellow": "Žltá rieka",
    "Huang He": "Žltá rieka",
    "Huang": "Žltá rieka",
    "Ob": "Ob",
    "Paraná": "Paraná",
    "Parana": "Paraná",
    "Congo": "Kongo",
    "Amur": "Amur",
    "Lena": "Lena",
    "Mekong": "Mekong",
    "Mackenzie": "Mackenzie",
    "Niger": "Niger",
    "Volga": "Volga",
    "Danube": "Dunaj",
    "Ganges": "Ganga",
}


def main():
    print("Fetching NE world rivers…")
    req = urllib.request.Request(NE_URL, headers={"User-Agent": "EventkvizPlugin/1.8 WP-Bot"})
    with urllib.request.urlopen(req, timeout=60) as r:
        data = json.loads(r.read().decode("utf-8"))
    print(f"  Got {len(data['features'])} NE features")

    # Skupina podľa labelu — niektoré rieky majú viac segmentov
    grouped = {}
    for f in data["features"]:
        name = f["properties"].get("name", "") or f["properties"].get("name_en", "")
        if name not in TOP_RIVERS:
            continue
        label = TOP_RIVERS[name]
        grouped.setdefault(label, []).append(f["geometry"])

    def simplify(coords, n=4):
        if len(coords) <= 3: return coords
        return [coords[i] for i in range(0, len(coords), n)] + [coords[-1]]

    features = []
    for label, geoms in grouped.items():
        lines = []
        for g in geoms:
            if g["type"] == "LineString":
                lines.append(simplify(g["coordinates"]))
            elif g["type"] == "MultiLineString":
                for ls in g["coordinates"]:
                    lines.append(simplify(ls))
        if len(lines) == 1:
            features.append({"type": "Feature", "properties": {"name": label}, "geometry": {"type": "LineString", "coordinates": lines[0]}})
        else:
            features.append({"type": "Feature", "properties": {"name": label}, "geometry": {"type": "MultiLineString", "coordinates": lines}})
        print(f"  OK: {label:20s} segments={len(lines)}")

    missing = [v for v in set(TOP_RIVERS.values()) if v not in {f["properties"]["name"] for f in features}]
    if missing:
        print(f"\n  MISSING: {missing}")

    out_path = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/world-rivers.geojson"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump({"type": "FeatureCollection", "features": features}, f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nWrote {len(features)} features → {out_path}")


if __name__ == "__main__":
    main()
