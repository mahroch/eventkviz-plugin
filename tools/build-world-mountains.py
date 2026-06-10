#!/usr/bin/env python3
"""Vytvorí world-mountains.geojson (Pohoria sveta) z Natural Earth 10m
ne_10m_geography_regions_polys (public domain) — reálne obrysy, rovnaký prístup
ako europe-mountains rework (v1.20.2). 16 pohorí vybraných Marošom.

Sierra Madre = zlúčené 3 NE časti (Occidental + Oriental + del Sur) do MultiPolygonu.

Zdroj (stiahni pred spustením):
  curl -sL -o /tmp/ne_geo_regions.geojson \\
    https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_10m_geography_regions_polys.geojson

Output: public/data/regions/world-mountains.geojson
"""
import json, math

SRC = "/tmp/ne_geo_regions.geojson"
OUT = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/world-mountains.geojson"

# NE NAME -> Slovak label.  (Sierra Madre rieši MERGE nižšie.)
NAME_MAP = {
    "HIMALAYAS":        "Himálaj",
    "KARAKORAM RA.":    "Karakoram",
    "HINDU KUSH":       "Hindúkuš",
    "PAMIRS":           "Pamír",
    "TIAN SHAN":        "Ťan-šan",
    "ALTAY MOUNTAINS":  "Altaj",
    "ANDES":            "Andy",
    "ROCKY MOUNTAINS":  "Skalnaté vrchy",
    "APPALACHIAN MTS.": "Apalačské pohorie",
    "SIERRA NEVADA":    "Sierra Nevada",
    "ALASKA RANGE":     "Aljašské pohorie",
    "ATLAS MOUNTAINS":  "Atlas",
    "SOUTHERN ALPS":    "Južné Alpy",
    "ALPS":             "Alpy",
    "CAUCASUS MTS.":    "Kaukaz",
}
# časti ktoré sa zlúčia do jedného pohoria
MERGE = {
    "Sierra Madre": {"SIERRA MADRE OCCIDENTAL", "SIERRA MADRE ORIENTAL", "Sierra Madre del Sur"},
}

RDP_TOL = 0.02   # stupne — jemná simplifikácia (zachová tvar, odstráni redundanciu)
ROUND   = 4      # desatinné miesta (~11 m)


def _perp_dist(p, a, b):
    (px, py), (ax, ay), (bx, by) = p, a, b
    dx, dy = bx - ax, by - ay
    if dx == 0 and dy == 0:
        return math.hypot(px - ax, py - ay)
    t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy)
    t = max(0, min(1, t))
    return math.hypot(px - (ax + t * dx), py - (ay + t * dy))


def rdp(pts, tol):
    if len(pts) < 3:
        return pts
    dmax, idx = 0, 0
    for i in range(1, len(pts) - 1):
        d = _perp_dist(pts[i], pts[0], pts[-1])
        if d > dmax:
            dmax, idx = d, i
    if dmax > tol:
        left = rdp(pts[:idx + 1], tol)
        right = rdp(pts[idx:], tol)
        return left[:-1] + right
    return [pts[0], pts[-1]]


def simplify_ring(ring):
    closed = ring[0] == ring[-1]
    pts = ring[:-1] if closed else ring[:]
    s = rdp(pts, RDP_TOL)
    if len(s) < 3:          # poistka — neprekresli na úsečku
        s = pts
    s = [[round(x, ROUND), round(y, ROUND)] for x, y in s]
    s.append(s[0])          # zatvor ring
    return s


def simplify_polygon(coords):       # coords = [ring, hole?, ...]
    return [simplify_ring(r) for r in coords]


def simplify_geom(g):
    if g["type"] == "Polygon":
        return {"type": "Polygon", "coordinates": simplify_polygon(g["coordinates"])}
    if g["type"] == "MultiPolygon":
        return {"type": "MultiPolygon", "coordinates": [simplify_polygon(p) for p in g["coordinates"]]}
    raise ValueError(g["type"])


def as_multi(g):                    # vráti list polygónov (pre merge)
    return g["coordinates"] if g["type"] == "MultiPolygon" else [g["coordinates"]]


def vcount(g):
    n = 0
    polys = g["coordinates"] if g["type"] == "MultiPolygon" else [g["coordinates"]]
    for poly in polys:
        for ring in poly:
            n += len(ring)
    return n


def main():
    src = json.load(open(SRC))
    by_name = {}
    for f in src["features"]:
        nm = f["properties"].get("NAME")
        if nm:
            by_name[nm] = f["geometry"]

    features = []
    total_before = total_after = 0

    # jednoduché pohoria
    for ne_name, sk in NAME_MAP.items():
        if ne_name not in by_name:
            print(f"  CHÝBA: {ne_name}")
            continue
        g = by_name[ne_name]
        total_before += vcount(g)
        sg = simplify_geom(g)
        total_after += vcount(sg)
        features.append({"type": "Feature", "properties": {"name": sk},
                         "geometry": sg})
        print(f"  OK: {sk:20s} ({vcount(g)} -> {vcount(sg)} vrch)")

    # zlúčené pohoria (Sierra Madre)
    for sk, parts in MERGE.items():
        polys = []
        before = 0
        for p in parts:
            if p not in by_name:
                print(f"  CHÝBA časť: {p}")
                continue
            g = by_name[p]
            before += vcount(g)
            polys.extend(as_multi(g))
        mg = {"type": "MultiPolygon", "coordinates": polys}
        total_before += before
        sg = simplify_geom(mg)
        total_after += vcount(sg)
        features.append({"type": "Feature", "properties": {"name": sk},
                         "geometry": sg})
        print(f"  OK: {sk:20s} ({before} -> {vcount(sg)} vrch, {len(parts)} častí)")

    with open(OUT, "w", encoding="utf-8") as f:
        json.dump({"type": "FeatureCollection", "features": features},
                  f, ensure_ascii=False, separators=(",", ":"))
    print(f"\nSpolu {len(features)} pohorí | vrcholy {total_before} -> {total_after}")
    print(f"Zapísané -> {OUT}")


if __name__ == "__main__":
    main()
