#!/usr/bin/env python3
"""world-countries.geojson — podkladová vrstva (kontinenty + hranice štátov) pre
mapový kvíz v regióne Svet. Zdroj Natural Earth 110m admin_0 countries (public
domain), agresívnejšia simplifikácia (len base layer pri svetovom zoome) + bez
properties (geometria stačí).

Zdroj: curl -sL -o /tmp/ne_countries.geojson \\
  https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_admin_0_countries.geojson

Output: public/data/regions/world-countries.geojson
"""
import json, math

SRC = "/tmp/ne_countries.geojson"
OUT = "/Applications/MAMP/htdocs/eventkviz/wp-content/plugins/eventkviz/public/data/regions/world-countries.geojson"
RDP_TOL = 0.18
ROUND = 2


def _pd(p, a, b):
    (px, py), (ax, ay), (bx, by) = p, a, b
    dx, dy = bx - ax, by - ay
    if dx == 0 and dy == 0:
        return math.hypot(px - ax, py - ay)
    t = max(0, min(1, ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy)))
    return math.hypot(px - (ax + t * dx), py - (ay + t * dy))


def rdp(pts, tol):
    if len(pts) < 3:
        return pts
    dmax, idx = 0, 0
    for i in range(1, len(pts) - 1):
        d = _pd(pts[i], pts[0], pts[-1])
        if d > dmax:
            dmax, idx = d, i
    if dmax > tol:
        return rdp(pts[:idx + 1], tol)[:-1] + rdp(pts[idx:], tol)
    return [pts[0], pts[-1]]


def simp_ring(ring):
    closed = ring[0] == ring[-1]
    pts = ring[:-1] if closed else ring[:]
    s = rdp(pts, RDP_TOL)
    if len(s) < 3:
        return None                         # zahoď drobné ostrovy
    s = [[round(x, ROUND), round(y, ROUND)] for x, y in s]
    s.append(s[0])
    return s


def simp_poly(coords):
    out = []
    for r in coords:
        sr = simp_ring(r)
        if sr:
            out.append(sr)
    return out or None


def simp_geom(g):
    if g["type"] == "Polygon":
        c = simp_poly(g["coordinates"])
        return {"type": "Polygon", "coordinates": c} if c else None
    polys = []
    for p in g["coordinates"]:
        sp = simp_poly(p)
        if sp:
            polys.append(sp)
    return {"type": "MultiPolygon", "coordinates": polys} if polys else None


def vcount(g):
    n = 0
    polys = g["coordinates"] if g["type"] == "MultiPolygon" else [g["coordinates"]]
    for poly in polys:
        for ring in poly:
            n += len(ring)
    return n


def main():
    src = json.load(open(SRC))
    feats = []
    before = after = 0
    for f in src["features"]:
        g = f["geometry"]
        before += vcount(g)
        sg = simp_geom(g)
        if not sg:
            continue
        after += vcount(sg)
        feats.append({"type": "Feature", "properties": {}, "geometry": sg})
    with open(OUT, "w", encoding="utf-8") as fh:
        json.dump({"type": "FeatureCollection", "features": feats}, fh,
                  ensure_ascii=False, separators=(",", ":"))
    print(f"{len(feats)} štátov | vrcholy {before} -> {after}")
    print(f"Zapísané -> {OUT}")


if __name__ == "__main__":
    main()
