<?php
/**
 * One-time seed script — 15 SK hradov pre Hrady SR template (post 1974).
 * Stiahne fotky z Wikipedia, upload do media library, prepíše _mapquiz_pins JSON.
 *
 * Usage: cd to plugin dir, then:
 *   /opt/homebrew/bin/php tools/seed-hrady.php
 */

// WordPress bootstrap
if ( php_sapi_name() !== 'cli' ) { die( 'CLI only' ); }
define( 'WP_USE_THEMES', false );
$wp_load = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    fwrite( STDERR, "wp-load.php not found at: $wp_load\n" );
    exit( 1 );
}
require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$TEMPLATE_ID = 1974; // Hrady SR (test)

/**
 * Definícia 15 najznámejších slovenských hradov.
 * `wiki_title` = slovenský názov page na sk.wikipedia.org (URL-decode safe).
 */
$hrady = array(
    array( 'name' => 'Bratislavský hrad',  'lat' => 48.1422, 'lon' => 17.0998, 'wiki_title' => 'Bratislavský hrad',
        'hint' => 'Hlavné mesto SR',
        'description' => 'Bývalé sídlo uhorských kráľov nad Dunajom. Dnes národná pamiatka a múzeum.' ),
    array( 'name' => 'Spišský hrad',       'lat' => 48.9990, 'lon' => 20.7676, 'wiki_title' => 'Spišský hrad',
        'hint' => 'UNESCO, najväčší hrad v Strednej Európe',
        'description' => 'Ruina nad mestom Spišské Podhradie. Najväčší hradný komplex v Strednej Európe.' ),
    array( 'name' => 'Devín',              'lat' => 48.1739, 'lon' => 16.9786, 'wiki_title' => 'Hrad Devín',
        'hint' => 'Sútok Dunaja a Moravy',
        'description' => 'Strážny hrad na hraničnom kopci, dnes symbol slovenskej identity.' ),
    array( 'name' => 'Trenčiansky hrad',   'lat' => 48.8946, 'lon' => 18.0437, 'wiki_title' => 'Trenčiansky hrad',
        'hint' => 'Sídlo Matúša Čáka „pána Váhu a Tatier"',
        'description' => 'Dominanta nad Trenčínom s rímskym nápisom z roku 179 n. l.' ),
    array( 'name' => 'Oravský hrad',       'lat' => 49.2611, 'lon' => 19.3608, 'wiki_title' => 'Oravský hrad',
        'hint' => 'Severná Orava nad Oravou',
        'description' => 'Hrad postavený na bralo nad riekou Orava, slúžil pri natáčaní Nosferatu.' ),
    array( 'name' => 'Bojnický zámok',     'lat' => 48.7806, 'lon' => 18.5808, 'wiki_title' => 'Bojnický zámok',
        'hint' => 'Najnavštevovanejší zámok SR',
        'description' => 'Romantický zámok rodu Pálffyovcov, dnes Slovenské národné múzeum.' ),
    array( 'name' => 'Červený Kameň',      'lat' => 48.4140, 'lon' => 17.3128, 'wiki_title' => 'Červený Kameň',
        'hint' => 'Pohorie Malé Karpaty',
        'description' => 'Renesančný hrad rodu Pálffyovcov so zachovalými interiérmi.' ),
    array( 'name' => 'Strečno',            'lat' => 49.1750, 'lon' => 18.8636, 'wiki_title' => 'Strečno (hrad)',
        'hint' => 'Nad Váhom pri Žiline',
        'description' => 'Stredoveký hrad na strategickom mieste pri Strečnianskej tiesňave.' ),
    array( 'name' => 'Čachtický hrad',     'lat' => 48.7138, 'lon' => 17.7625, 'wiki_title' => 'Čachtický hrad',
        'image_url'   => 'https://upload.wikimedia.org/wikipedia/commons/3/32/%C4%8Cachtice%2C_hrad%2C_Slovensko.jpg',
        'hint' => 'Krvavá grófka Báthory',
        'description' => 'Ruina spojená s grófkou Alžbetou Báthoryovou, najznámejšou sériovou vrahyňou v dejinách.' ),
    array( 'name' => 'Krásna Hôrka',       'lat' => 48.6618, 'lon' => 20.5837, 'wiki_title' => 'Krásna Hôrka',
        'image_url'   => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Hrad_Krasna_Horka.jpg',
        'hint' => 'Rožňava, rod Andrássy',
        'description' => 'Pôvodne stredoveká pevnosť rodu Andrássyovcov. Vyhorel v roku 2012, obnova prebieha.' ),
    array( 'name' => 'Beckov',             'lat' => 48.7858, 'lon' => 17.8997, 'wiki_title' => 'Beckov (hrad)',
        'hint' => 'Pri Trenčíne, rod Stibor',
        'description' => 'Skalný hrad na vápencovom brale nad obcou Beckov.' ),
    array( 'name' => 'Nitriansky hrad',    'lat' => 48.3168, 'lon' => 18.0871, 'wiki_title' => 'Nitriansky hrad',
        'image_url'   => 'https://upload.wikimedia.org/wikipedia/commons/4/47/Nitriansky_hrad.jpg',
        'hint' => 'Spojené s katedrálou sv. Emeráma',
        'description' => 'Najstarší hrad v Strednej Európe so spomenutím v 9. storočí. Sídlo biskupstva.' ),
    array( 'name' => 'Tematín',            'lat' => 48.7245, 'lon' => 17.9214, 'wiki_title' => 'Tematín',
        'hint' => 'Považský Inovec nad Hôrkou',
        'description' => 'Stredoveký hrad chrániaci obchodnú cestu cez Vážske brody.' ),
    array( 'name' => 'Stará Ľubovňa',      'lat' => 49.3217, 'lon' => 20.7042, 'wiki_title' => 'Ľubovniansky hrad',
        'hint' => 'Spišský záloh, Lubovňa',
        'description' => 'Hrad nad Popradom, sídlo poľského kapitána počas spišského zálohu (1412–1772).' ),
    array( 'name' => 'Smolenický zámok',   'lat' => 48.5117, 'lon' => 17.4225, 'wiki_title' => 'Smolenický zámok',
        'hint' => 'Malé Karpaty pri Smoleniciach',
        'description' => 'Romantická obnova z 20. storočia. Dnes konferenčné centrum SAV.' ),
);

echo "=== Seeding 15 hradov to template post $TEMPLATE_ID ===\n";

/**
 * Fetch image URL from Wikipedia REST API summary endpoint.
 * Returns ['url'=>..., 'credit'=>...] alebo null.
 */
function fetch_wiki_image( $title ) {
    // 1) REST summary endpoint — fast, ale niektoré pages nemajú image v summary
    $endpoints = array(
        'https://sk.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( $title ),
        'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( $title ),
    );
    foreach ( $endpoints as $url ) {
        $resp = wp_remote_get( $url, array( 'timeout' => 15, 'user-agent' => 'EventkvizPlugin/1.5 (https://eventkviz.sk; admin@eventkviz.sk) WP-Bot' ) );
        if ( is_wp_error( $resp ) ) continue;
        if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) continue;
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $data ) ) continue;
        if ( ! empty( $data['originalimage']['source'] ) ) return array( 'url' => $data['originalimage']['source'] );
        if ( ! empty( $data['thumbnail']['source'] ) )    return array( 'url' => $data['thumbnail']['source'] );
    }
    // 2) Action API fallback — query pageimages prop, vyzobere lead image aj pre pages bez REST summary
    $actions = array(
        'https://sk.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&titles=' . rawurlencode( $title ),
        'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&titles=' . rawurlencode( $title ),
    );
    foreach ( $actions as $url ) {
        $resp = wp_remote_get( $url, array( 'timeout' => 15, 'user-agent' => 'EventkvizPlugin/1.5 (https://eventkviz.sk; admin@eventkviz.sk) WP-Bot' ) );
        if ( is_wp_error( $resp ) ) continue;
        if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) continue;
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['query']['pages'] ) ) continue;
        foreach ( $data['query']['pages'] as $page ) {
            if ( ! empty( $page['original']['source'] ) ) return array( 'url' => $page['original']['source'] );
            if ( ! empty( $page['thumbnail']['source'] ) ) return array( 'url' => $page['thumbnail']['source'] );
        }
    }
    return null;
}

$pins = array();
$processed = 0;
$photo_ok = 0;

foreach ( $hrady as $h ) {
    $processed++;
    $pin = array(
        'id'          => wp_generate_uuid4(),
        'name'        => $h['name'],
        'hint'        => $h['hint'],
        'description' => $h['description'],
        'photo_id'    => 0,
        'lat'         => $h['lat'],
        'lon'         => $h['lon'],
    );

    echo "[$processed/15] {$h['name']} … ";

    // Pri ručne zadanom image_url override (pre prípady kde Wiki API zlyháva)
    if ( ! empty( $h['image_url'] ) ) {
        $img = array( 'url' => $h['image_url'] );
    } else {
        $img = fetch_wiki_image( $h['wiki_title'] );
    }
    if ( $img && ! empty( $img['url'] ) ) {
        $url = $img['url'];
        $tmp = download_url( $url, 30 );
        if ( ! is_wp_error( $tmp ) ) {
            // Sanitize filename
            $fname = sanitize_file_name( $h['name'] ) . '.' . pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
            if ( $fname === '' || strpos( $fname, '.' ) === false ) $fname = sanitize_file_name( $h['name'] ) . '.jpg';
            $file = array( 'name' => $fname, 'tmp_name' => $tmp );

            $attach_id = media_handle_sideload( $file, 0, $h['name'] );
            if ( ! is_wp_error( $attach_id ) ) {
                $pin['photo_id'] = (int) $attach_id;
                $photo_ok++;
                echo "photo OK (id $attach_id)\n";
            } else {
                @unlink( $tmp );
                echo "photo FAIL (sideload: " . $attach_id->get_error_message() . ")\n";
            }
        } else {
            echo "photo FAIL (download: " . $tmp->get_error_message() . ")\n";
        }
    } else {
        echo "no wiki image\n";
    }

    $pins[] = $pin;
}

// Update template postmeta
// wp_slash wrap pred update_post_meta — WP unslash-uje vstup (magic-quote
// compat), takže bez wp_slash by sa stratili JSON escape backslashes.
update_post_meta( $TEMPLATE_ID, '_mapquiz_pins', wp_slash( wp_json_encode( $pins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );

echo "\n=== Done ===\n";
echo "Pins saved: " . count( $pins ) . "\n";
echo "Photos OK:  $photo_ok / $processed\n";
echo "Template:   $TEMPLATE_ID\n";
