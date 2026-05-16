#!/usr/bin/env python3
"""Stiahne SK národné parky z OSM Overpass ako GeoJSON FeatureCollection (polygóny).
Output: /Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/sk-national-parks.geojson
"""
import json
import urllib.request
import urllib.parse

UA = "EventkvizPlugin/1.7 (https://eventkviz.sk; admin@eventkviz.sk) WP-Bot"

# Cieľové názvy + mapovanie (OSM name → slovenský label v aktuálnej šablóne)
TARGETS = {
    "Tatranský národný park": "Tatranský národný park (TANAP)",
    "Národný park Nízke Tatry": "Národný park Nízke Tatry (NAPANT)",
    "Pieninský národný park": "Pieninský národný park (PIENAP)",
    "NP Malá Fatra": "Národný park Malá Fatra",
    "Národný park Veľká Fatra": "Národný park Veľká Fatra",
    "Národný park Slovenský raj": "Národný park Slovenský raj",
    "Národný park Slovenský kras": "Národný park Slovenský kras",
    "Národný park Muránska planina": "Národný park Muránska planina",
    "Národný park Poloniny": "Národný park Poloniny",
}

QUERY = """
[out:json][timeout:120];
area["ISO3166-1"="SK"][admin_level=2]->.sk;
relation(area.sk)[boundary=national_park];
out geom;
"""

def fetch():
    data = urllib.parse.urlencode({"data": QUERY}).encode("utf-8")
    req = urllib.request.Request("https://overpass-api.de/api/interpreter", data=data, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read().decode("utf-8"))


def relation_to_polygon(rel):
    """Convert Overpass relation (with geom) → GeoJSON Polygon/MultiPolygon coords.
    Pre simplicity: zoberieme všetky outer ways, spojíme do polygonov.
    """
    outers = []
    inners = []
    for m in rel.get("members", []):
        if m.get("type") != "way" or "geometry" not in m:
            continue
        coords = [[p["lon"], p["lat"]] for p in m["geometry"]]
        if m.get("role") == "outer":
            outers.append(coords)
        elif m.get("role") == "inner":
            inners.append(coords)
        else:
            outers.append(coords)
    if not outers:
        return None

    # Spoj jednotlivé way segmenty do uzavretých ringov.
    def assemble_rings(segments):
        segments = [list(s) for s in segments]
        rings = []
        while segments:
            ring = segments.pop(0)
            extended = True
            while extended:
                extended = False
                for i, seg in enumerate(segments):
                    if ring[-1] == seg[0]:
                        ring += seg[1:]; segments.pop(i); extended = True; break
                    if ring[-1] == seg[-1]:
                        ring += list(reversed(seg))[1:]; segments.pop(i); extended = True; break
                    if ring[0] == seg[-1]:
                        ring = seg[:-1] + ring; segments.pop(i); extended = True; break
                    if ring[0] == seg[0]:
                        ring = list(reversed(seg))[:-1] + ring; segments.pop(i); extended = True; break
            if ring[0] != ring[-1]:
                ring.append(ring[0])  # force-close
            rings.append(ring)
        return rings

    outer_rings = assemble_rings(outers)
    inner_rings = assemble_rings(inners) if inners else []

    if len(outer_rings) == 1:
        return {"type": "Polygon", "coordinates": [outer_rings[0]] + inner_rings}
    return {"type": "MultiPolygon", "coordinates": [[r] for r in outer_rings]}


def simplify(coords, n=3):
    """Jednoduchý decimator — vezme každý n-tý bod (zachová prvý a posledný)."""
    if len(coords) <= 4:
        return coords
    out = [coords[i] for i in range(0, len(coords) - 1, n)]
    out.append(coords[-1])
    if out[0] != out[-1]:
        out.append(out[0])
    return out


def simplify_geom(geom, n=3):
    if geom["type"] == "Polygon":
        geom["coordinates"] = [simplify(ring, n) for ring in geom["coordinates"]]
    elif geom["type"] == "MultiPolygon":
        geom["coordinates"] = [[simplify(ring, n) for ring in poly] for poly in geom["coordinates"]]
    return geom


def main():
    print("Fetching SK national parks from Overpass…")
    data = fetch()
    print(f"  Got {len(data['elements'])} elements")

    features = []
    for rel in data["elements"]:
        name = rel.get("tags", {}).get("name", "?")
        if name not in TARGETS:
            print(f"  SKIP (foreign): {name}")
            continue
        geom = relation_to_polygon(rel)
        if not geom:
            print(f"  FAIL (no geom): {name}")
            continue
        geom = simplify_geom(geom, n=6)
        features.append({
            "type": "Feature",
            "properties": {"name": TARGETS[name], "name_osm": name},
            "geometry": geom,
        })
        print(f"  OK: {TARGETS[name]} ({geom['type']})")

    fc = {"type": "FeatureCollection", "features": features}
    out_path = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/sk-national-parks.geojson"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(fc, f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nWrote {len(features)} features → {out_path}")


if __name__ == "__main__":
    main()
