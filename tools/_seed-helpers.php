<?php
/**
 * Shared helpers pre seed CLI scripts.
 * Include cez require_once __DIR__ . '/_seed-helpers.php';
 *
 * Predpokladá že WP je už loaded (wp-load.php + admin includes).
 */

if ( ! defined( 'ABSPATH' ) ) die( 'WP not loaded' );
if ( ! function_exists( 'media_handle_sideload' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
}

/**
 * Načítaj obrazok URL z Wikipedia REST summary endpoint alebo MediaWiki action API.
 * Fallback chain: sk.wiki REST → en.wiki REST → sk.wiki action → en.wiki action.
 * @return string|null Image URL alebo null.
 */
function ek_fetch_wiki_image( $title ) {
    $ua = 'EventkvizPlugin/1.5 (https://eventkviz.sk; admin@eventkviz.sk) WP-Bot';
    $rest = array(
        'https://sk.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( $title ),
        'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( $title ),
    );
    foreach ( $rest as $url ) {
        $resp = wp_remote_get( $url, array( 'timeout' => 15, 'user-agent' => $ua ) );
        if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) continue;
        $d = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! empty( $d['originalimage']['source'] ) ) return $d['originalimage']['source'];
        if ( ! empty( $d['thumbnail']['source'] ) )    return $d['thumbnail']['source'];
    }
    $api = array(
        'https://sk.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&titles=' . rawurlencode( $title ),
        'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original|thumbnail&pithumbsize=800&titles=' . rawurlencode( $title ),
    );
    foreach ( $api as $url ) {
        $resp = wp_remote_get( $url, array( 'timeout' => 15, 'user-agent' => $ua ) );
        if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) continue;
        $d = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $d['query']['pages'] ) ) continue;
        foreach ( $d['query']['pages'] as $page ) {
            if ( ! empty( $page['original']['source'] ) ) return $page['original']['source'];
            if ( ! empty( $page['thumbnail']['source'] ) ) return $page['thumbnail']['source'];
        }
    }
    return null;
}

/**
 * Stiahni image URL ako WP attachment. Vráti attachment ID alebo 0.
 */
function ek_sideload_image( $image_url, $title ) {
    $tmp = download_url( $image_url, 30 );
    if ( is_wp_error( $tmp ) ) return 0;
    $ext = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'jpg';
    $fname = sanitize_file_name( $title ) . '.' . $ext;
    $file = array( 'name' => $fname, 'tmp_name' => $tmp );
    $attach_id = media_handle_sideload( $file, 0, $title );
    if ( is_wp_error( $attach_id ) ) { @unlink( $tmp ); return 0; }
    return (int) $attach_id;
}

/**
 * Vytvor alebo nájdi mapquiz_template podľa title. Vráti post ID.
 * Ak existuje, vráti ID; ak nie, vytvorí.
 */
function ek_get_or_create_template( $title, $region = 'slovakia', $quiz_type = 'pin' ) {
    $existing = get_posts( array(
        'post_type' => 'mapquiz_template',
        'title' => $title,
        'posts_per_page' => 1,
        'post_status' => array( 'publish', 'draft' ),
    ) );
    if ( ! empty( $existing ) ) {
        $id = (int) $existing[0]->ID;
        echo "  Found existing template '$title' (ID $id) — will overwrite pins\n";
    } else {
        $id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'mapquiz_template',
            'post_status' => 'publish',
        ) );
        if ( is_wp_error( $id ) ) { fwrite( STDERR, "  ERR insert template: " . $id->get_error_message() . "\n" ); return 0; }
        echo "  Created new template '$title' (ID $id)\n";
    }
    // Set defaults
    update_post_meta( $id, '_mapquiz_region', $region );
    update_post_meta( $id, '_mapquiz_quiz_type', $quiz_type );
    if ( ! get_post_meta( $id, '_mapquiz_max_points', true ) ) {
        update_post_meta( $id, '_mapquiz_max_points', 100 );
    }
    if ( ! get_post_meta( $id, '_mapquiz_score_tiers', true ) ) {
        update_post_meta( $id, '_mapquiz_score_tiers', wp_json_encode( array(
            array( 'maxKm' => 5,  'percent' => 100 ),
            array( 'maxKm' => 10, 'percent' => 75 ),
            array( 'maxKm' => 20, 'percent' => 50 ),
            array( 'maxKm' => 40, 'percent' => 25 ),
        ) ) );
    }
    return $id;
}

/**
 * Pre dané pole pin definícií fetchne fotky + uloží do template _mapquiz_pins.
 * Pin spec keys: name, lat, lon, hint, description, wiki_title, image_url (optional override).
 */
function ek_seed_pins_into_template( $template_id, $pins_def ) {
    $total = count( $pins_def );
    $photo_ok = 0;
    $pins = array();
    foreach ( $pins_def as $i => $h ) {
        $hn = $i + 1;
        echo "  [$hn/$total] {$h['name']} … ";
        $pin = array(
            'id'          => wp_generate_uuid4(),
            'name'        => $h['name'],
            'hint'        => $h['hint'] ?? '',
            'description' => $h['description'] ?? '',
            'photo_id'    => 0,
            'lat'         => (float) $h['lat'],
            'lon'         => (float) $h['lon'],
        );
        $img_url = ! empty( $h['image_url'] ) ? $h['image_url'] : ek_fetch_wiki_image( $h['wiki_title'] ?? $h['name'] );
        if ( $img_url ) {
            $aid = ek_sideload_image( $img_url, $h['name'] );
            if ( $aid > 0 ) {
                $pin['photo_id'] = $aid;
                $photo_ok++;
                echo "photo OK (id $aid)\n";
            } else {
                echo "photo FAIL\n";
            }
        } else {
            echo "no wiki image\n";
        }
        $pins[] = $pin;
    }
    // wp_slash kvôli WP magic-quote roundtrip (inak " escape backslashes sa stratia)
    update_post_meta( $template_id, '_mapquiz_pins', wp_slash( wp_json_encode( $pins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
    echo "  Saved $total pins, photos $photo_ok/$total\n";
    return array( 'count' => $total, 'photos' => $photo_ok );
}
