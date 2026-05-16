<?php
/**
 * Rozšír sk-rivers.geojson o 5 ďalších riek (Nitra, Topľa, Ondava, Bodrog, Poprad).
 * Stiahne ich z OSM Overpass (waterway=river v SK area) + zlúči ways do
 * MultiLineString features. Uloží naspäť do public/data/regions/sk-rivers.geojson.
 *
 * Bezpečné re-run: preskočí rieky ktoré už v geojson existujú podľa name.
 */
if ( php_sapi_name() !== 'cli' ) die( 'CLI only' );

$GEOJSON = dirname( __DIR__ ) . '/public/data/regions/sk-rivers.geojson';
if ( ! file_exists( $GEOJSON ) ) { fwrite( STDERR, "sk-rivers.geojson not found\n" ); exit( 1 ); }

$NEW_RIVERS = array( 'Nitra', 'Topľa', 'Ondava', 'Bodrog', 'Poprad' );

// Load existing geojson
$gj = json_decode( file_get_contents( $GEOJSON ), true );
if ( ! is_array( $gj ) || ! isset( $gj['features'] ) ) { fwrite( STDERR, "bad geojson\n" ); exit( 1 ); }
$existing_names = array();
foreach ( $gj['features'] as $f ) {
    if ( ! empty( $f['properties']['name'] ) ) $existing_names[] = $f['properties']['name'];
}
echo "Existing rivers in geojson: " . implode( ', ', $existing_names ) . "\n";

// Build Overpass query
$q_parts = array();
foreach ( $NEW_RIVERS as $r ) {
    if ( in_array( $r, $existing_names, true ) ) {
        echo "Skipping '$r' (already in geojson)\n";
        continue;
    }
    $q_parts[] = '  way["waterway"="river"]["name"="' . $r . '"](area.sk);';
}
if ( empty( $q_parts ) ) { echo "Nothing to add.\n"; exit( 0 ); }

$query = "[out:json][timeout:90];\narea[\"ISO3166-1\"=\"SK\"]->.sk;\n(\n" . implode( "\n", $q_parts ) . "\n);\nout geom;";

echo "Querying Overpass for " . count( $q_parts ) . " rivers…\n";

$ch = curl_init( 'https://overpass-api.de/api/interpreter' );
curl_setopt_array( $ch, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'data=' . urlencode( $query ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 180,
    CURLOPT_USERAGENT => 'EventkvizPlugin/1.5 (https://eventkviz.sk) WP-Bot',
) );
$response = curl_exec( $ch );
$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
curl_close( $ch );

if ( $status !== 200 ) { fwrite( STDERR, "Overpass HTTP $status\n" ); exit( 1 ); }
$osm = json_decode( $response, true );
if ( ! is_array( $osm ) || empty( $osm['elements'] ) ) { fwrite( STDERR, "Overpass empty/invalid response\n" ); exit( 1 ); }

echo "Got " . count( $osm['elements'] ) . " way elements\n";

// Simplify: keep every Nth point (Douglas-Peucker by lazyness, every-N is fine pre quiz)
$SIMPLIFY_N = 5;

// Group ways by river name
$by_name = array();
foreach ( $osm['elements'] as $el ) {
    if ( $el['type'] !== 'way' || empty( $el['geometry'] ) ) continue;
    $name = $el['tags']['name'] ?? '';
    if ( ! in_array( $name, $NEW_RIVERS, true ) ) continue;
    $line = array();
    foreach ( $el['geometry'] as $i => $node ) {
        // Always keep first + last; in between, keep every Nth
        if ( $i === 0 || $i === count( $el['geometry'] ) - 1 || $i % $SIMPLIFY_N === 0 ) {
            $line[] = array( round( $node['lon'], 4 ), round( $node['lat'], 4 ) );
        }
    }
    if ( count( $line ) >= 2 ) $by_name[ $name ][] = $line;
}

// Append nové rieky do geojson
foreach ( $by_name as $name => $lines ) {
    if ( in_array( $name, $existing_names, true ) ) continue;
    $gj['features'][] = array(
        'type' => 'Feature',
        'properties' => array( 'name' => $name ),
        'geometry' => array(
            'type' => count( $lines ) > 1 ? 'MultiLineString' : 'LineString',
            'coordinates' => count( $lines ) > 1 ? $lines : $lines[0],
        ),
    );
    $pts = array_sum( array_map( 'count', $lines ) );
    echo "  + $name (" . count( $lines ) . " ways, $pts pts)\n";
}

// Save
file_put_contents( $GEOJSON, json_encode( $gj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
echo "Saved $GEOJSON (" . filesize( $GEOJSON ) . " bytes, " . count( $gj['features'] ) . " features total)\n";
