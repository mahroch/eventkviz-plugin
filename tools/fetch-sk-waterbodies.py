#!/usr/bin/env python3
"""Stiahne 11 hlavných slovenských vodných plôch (priehrady + plesá + sopečné jazerá)
z OSM Overpass podľa explicitných OSM IDs (presnejšie než name match).

Output: public/data/regions/sk-waterbodies.geojson
"""
import json
import urllib.request
import urllib.parse

UA = "EventkvizPlugin/1.11 (https://eventkviz.sk; admin@eventkviz.sk) WP-Bot"

# Whitelist: (osm_type, osm_id, slovenský label)
TARGETS = [
    ("relation", 1454833, "Oravská priehrada"),
    ("relation", 5626377, "Liptovská Mara"),
    ("relation", 5298659, "Zemplínska šírava"),
    ("way",      97011831, "Domaša"),
    ("relation", 3575299, "Sĺňava"),
    ("relation", 3379038, "Ružín"),
    ("way",      4416556, "Nosická priehrada"),
    ("relation", 3062407, "Gabčíkovo (Hrušovská zdrž)"),
    ("way",      4816134, "Štrbské pleso"),
    ("way",      33458143, "Popradské pleso"),
    ("way",      110347927, "Morské oko"),
]


def fetch_one(osm_type, osm_id):
    q = f"[out:json][timeout:120];{osm_type}({osm_id});out geom;"
    data = urllib.parse.urlencode({"data": q}).encode("utf-8")
    req = urllib.request.Request("https://overpass-api.de/api/interpreter", data=data, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read().decode("utf-8"))


def way_to_polygon(el):
    """Way → Polygon (closed ring)."""
    geom = el.get("geometry", [])
    if not geom:
        return None
    ring = [[p["lon"], p["lat"]] for p in geom]
    if ring[0] != ring[-1]:
        ring.append(ring[0])
    if len(ring) < 4:
        return None
    return {"type": "Polygon", "coordinates": [ring]}


def relation_to_polygon(el):
    """Relation s outer/inner members → Polygon alebo MultiPolygon."""
    outers, inners = [], []
    for m in el.get("members", []):
        if m.get("type") != "way" or "geometry" not in m:
            continue
        coords = [[p["lon"], p["lat"]] for p in m["geometry"]]
        if m.get("role") == "inner":
            inners.append(coords)
        else:
            outers.append(coords)
    if not outers:
        return None

    def assemble(segs):
        segs = [list(s) for s in segs]
        rings = []
        while segs:
            ring = segs.pop(0)
            ext = True
            while ext:
                ext = False
                for i, s in enumerate(segs):
                    if ring[-1] == s[0]:  ring += s[1:]; segs.pop(i); ext=True; break
                    if ring[-1] == s[-1]: ring += list(reversed(s))[1:]; segs.pop(i); ext=True; break
                    if ring[0] == s[-1]:  ring = s[:-1] + ring; segs.pop(i); ext=True; break
                    if ring[0] == s[0]:   ring = list(reversed(s))[:-1] + ring; segs.pop(i); ext=True; break
            if ring[0] != ring[-1]:
                ring.append(ring[0])
            rings.append(ring)
        return rings

    outer_rings = assemble(outers)
    inner_rings = assemble(inners) if inners else []
    if len(outer_rings) == 1:
        return {"type": "Polygon", "coordinates": [outer_rings[0]] + inner_rings}
    return {"type": "MultiPolygon", "coordinates": [[r] for r in outer_rings]}


def simplify(coords, n=4):
    if len(coords) <= 5:
        return coords
    out = [coords[i] for i in range(0, len(coords) - 1, n)]
    out.append(coords[-1])
    if out[0] != out[-1]:
        out.append(out[0])
    return out


def simplify_geom(geom, n=4):
    if geom["type"] == "Polygon":
        geom["coordinates"] = [simplify(r, n) for r in geom["coordinates"]]
    elif geom["type"] == "MultiPolygon":
        geom["coordinates"] = [[simplify(r, n) for r in p] for p in geom["coordinates"]]
    return geom


def main():
    print(f"Fetching {len(TARGETS)} SK waterbodies from OSM Overpass…")
    features = []
    for osm_type, osm_id, label in TARGETS:
        try:
            data = fetch_one(osm_type, osm_id)
            if not data.get("elements"):
                print(f"  FAIL: {label} ({osm_type} {osm_id}) — empty response")
                continue
            el = data["elements"][0]
            if osm_type == "way":
                geom = way_to_polygon(el)
            else:
                geom = relation_to_polygon(el)
            if not geom:
                print(f"  FAIL: {label} — no valid geometry")
                continue
            # Simplify pre kompaktnosť (pre malé plesá menej simplify aby ostali viditeľné)
            n = 2 if "pleso" in label.lower() or "oko" in label.lower() else 4
            geom = simplify_geom(geom, n=n)
            features.append({
                "type": "Feature",
                "properties": {"name": label, "osm_type": osm_type, "osm_id": osm_id},
                "geometry": geom,
            })
            print(f"  OK: {label:35s} ({geom['type']})")
        except Exception as e:
            print(f"  ERR: {label} — {e}")

    fc = {"type": "FeatureCollection", "features": features}
    out_path = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/sk-waterbodies.geojson"
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(fc, f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nWrote {len(features)} features → {out_path}")


if __name__ == "__main__":
    main()
