<?php
/**
 * Seed/create 2 area-style templates: Pohoria Európy + Top rieky sveta.
 * Idempotentné — pri opätovnom spustení nájde existujúci template podľa title.
 *
 * Usage: /Applications/MAMP/bin/php/php8.2.0/bin/php tools/seed-eu-mountains-world-rivers.php
 */
if ( PHP_SAPI !== 'cli' ) die( "CLI only\n" );

$wp_root = realpath( __DIR__ . '/../../../..' );
require_once $wp_root . '/wp-load.php';

require_once dirname( __DIR__ ) . '/admin/class-eventkviz-mapquiz-datasets.php';

function ek_get_or_create( $title ) {
    $existing = get_posts( array(
        'post_type'      => 'mapquiz_template',
        'title'          => $title,
        'posts_per_page' => 1,
        'post_status'    => array( 'publish', 'draft' ),
    ) );
    if ( ! empty( $existing ) ) {
        $id = (int) $existing[0]->ID;
        echo "  Found existing '$title' (ID $id)\n";
        return $id;
    }
    $id = wp_insert_post( array(
        'post_title'  => $title,
        'post_type'   => 'mapquiz_template',
        'post_status' => 'publish',
    ) );
    if ( is_wp_error( $id ) ) { fwrite( STDERR, "  ERR: " . $id->get_error_message() . "\n" ); return 0; }
    echo "  Created '$title' (ID $id)\n";
    return (int) $id;
}

function ek_configure( $id, $region, $quiz_type, $dataset_slug, $pool ) {
    update_post_meta( $id, '_mapquiz_region',       $region );
    update_post_meta( $id, '_mapquiz_quiz_type',    $quiz_type );
    update_post_meta( $id, '_mapquiz_dataset_slug', $dataset_slug );
    update_post_meta( $id, '_mapquiz_feature_pool', wp_slash( wp_json_encode( $pool, JSON_UNESCAPED_UNICODE ) ) );
    if ( ! get_post_meta( $id, '_mapquiz_max_points', true ) ) {
        update_post_meta( $id, '_mapquiz_max_points', 100 );
    }
    echo "    region=$region quiz_type=$quiz_type dataset=$dataset_slug pool=" . count( $pool ) . " features\n";
}

echo "=== Seed Pohoria Európy + Top rieky sveta ===\n\n";

// 1) Pohoria Európy
echo "[1/2] Pohoria Európy\n";
$id = ek_get_or_create( 'Pohoria Európy' );
if ( $id ) {
    $pool = Eventkviz_MapQuiz_Datasets::load_feature_names( 'europe-mountains' );
    if ( empty( $pool ) ) {
        echo "  WARN: empty features list — možno chýba europe-mountains.geojson?\n";
    } else {
        // Zachovaj prevadzkový pool ak admin si tweakol — inak default = všetkých 12 pohorí.
        $existing_pool = json_decode( (string) get_post_meta( $id, '_mapquiz_feature_pool', true ), true );
        $final_pool = ( is_array( $existing_pool ) && ! empty( $existing_pool ) ) ? $existing_pool : $pool;
        ek_configure( $id, 'europe', 'area', 'europe-mountains', $final_pool );
    }
}

// 2) Top rieky sveta
echo "\n[2/2] Top rieky sveta\n";
$id = ek_get_or_create( 'Top rieky sveta' );
if ( $id ) {
    $pool = Eventkviz_MapQuiz_Datasets::load_feature_names( 'world-rivers' );
    if ( empty( $pool ) ) {
        echo "  WARN: empty features list — možno chýba world-rivers.geojson?\n";
    } else {
        $existing_pool = json_decode( (string) get_post_meta( $id, '_mapquiz_feature_pool', true ), true );
        $final_pool = ( is_array( $existing_pool ) && ! empty( $existing_pool ) ) ? $existing_pool : $pool;
        ek_configure( $id, 'world', 'line', 'world-rivers', $final_pool );
    }
}

echo "\n=== Hotovo ===\n";
