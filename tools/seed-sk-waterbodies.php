<?php
/**
 * Seed mapquiz_template „Vodné nádrže a jazerá SR" — quiz_type=area,
 * dataset_slug=sk-waterbodies, pool=všetkých 11 features.
 * Idempotentné — pri opätovnom spustení update-uje existujúci template.
 *
 * Usage: /Applications/MAMP/bin/php/php8.2.0/bin/php tools/seed-sk-waterbodies.php
 */
if ( PHP_SAPI !== 'cli' ) die( "CLI only\n" );

$wp_root = realpath( __DIR__ . '/../../../..' );
require_once $wp_root . '/wp-load.php';
require_once dirname( __DIR__ ) . '/admin/class-eventkviz-mapquiz-datasets.php';

$title = 'Vodné nádrže a jazerá SR';

$existing = get_posts( array(
    'post_type'      => 'mapquiz_template',
    'title'          => $title,
    'posts_per_page' => 1,
    'post_status'    => array( 'publish', 'draft' ),
) );
if ( ! empty( $existing ) ) {
    $id = (int) $existing[0]->ID;
    echo "Found existing '$title' (ID $id)\n";
} else {
    $id = wp_insert_post( array(
        'post_title'  => $title,
        'post_type'   => 'mapquiz_template',
        'post_status' => 'publish',
    ) );
    if ( is_wp_error( $id ) ) die( "Insert failed: " . $id->get_error_message() . "\n" );
    echo "Created '$title' (ID $id)\n";
}

$pool = Eventkviz_MapQuiz_Datasets::load_feature_names( 'sk-waterbodies' );
if ( empty( $pool ) ) die( "FAIL: sk-waterbodies.geojson nie je dostupný / prázdny\n" );

update_post_meta( $id, '_mapquiz_region', 'slovakia' );
update_post_meta( $id, '_mapquiz_quiz_type', 'area' );
update_post_meta( $id, '_mapquiz_dataset_slug', 'sk-waterbodies' );
update_post_meta( $id, '_mapquiz_features_source', 'bundle' );

// Pool — zachovaj existujúci ak admin si tweakol, inak všetkých 11.
$existing_pool = json_decode( (string) get_post_meta( $id, '_mapquiz_feature_pool', true ), true );
$final_pool = ( is_array( $existing_pool ) && ! empty( $existing_pool ) ) ? $existing_pool : $pool;
update_post_meta( $id, '_mapquiz_feature_pool', wp_slash( wp_json_encode( $final_pool, JSON_UNESCAPED_UNICODE ) ) );

if ( ! get_post_meta( $id, '_mapquiz_max_points', true ) ) {
    update_post_meta( $id, '_mapquiz_max_points', 100 );
}

echo "  region=slovakia quiz_type=area dataset=sk-waterbodies pool=" . count( $final_pool ) . " features\n";
echo "  Pool: " . implode( ', ', $final_pool ) . "\n";
echo "Hotovo.\n";
