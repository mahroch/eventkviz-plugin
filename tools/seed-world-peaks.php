<?php
/**
 * Seed top 15 svetových vrcholov ako pin template (custom pins).
 * Wikipedia fotky cez ek_fetch_wiki_image. Idempotentné — pri opätovnom
 * spustení nájde existujúci template podľa title a prepíše pins.
 *
 * Usage: /Applications/MAMP/bin/php/php8.2.0/bin/php tools/seed-world-peaks.php
 */
if ( php_sapi_name() !== 'cli' ) die( 'CLI only' );
define( 'WP_USE_THEMES', false );
$wp_load = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
require_once $wp_load;
require_once __DIR__ . '/_seed-helpers.php';

$peaks = array(
    array( 'name' => 'Mount Everest',     'lat' => 27.9881, 'lon' =>  86.9250, 'wiki_title' => 'Mount Everest',
        'hint' => 'Najvyšší vrch sveta', 'description' => 'Najvyšší vrch sveta, 8848 m. Himaláje, hranica Nepál/Čína.' ),
    array( 'name' => 'K2',                'lat' => 35.8825, 'lon' =>  76.5133, 'wiki_title' => 'K2',
        'hint' => 'Druhý najvyšší', 'description' => 'Druhý najvyšší vrch sveta, 8611 m. Karakoram, hranica Pakistan/Čína.' ),
    array( 'name' => 'Kančendžonga',      'lat' => 27.7025, 'lon' =>  88.1475, 'wiki_title' => 'Kangchenjunga',
        'hint' => 'Tretí najvyšší', 'description' => 'Tretí najvyšší vrch sveta, 8586 m. Himaláje, hranica Nepál/India.' ),
    array( 'name' => 'Aconcagua',         'lat' => -32.6531, 'lon' => -70.0109, 'wiki_title' => 'Aconcagua',
        'hint' => 'Najvyšší v Amerikách', 'description' => 'Najvyšší vrch Južnej a Severnej Ameriky, 6961 m. Andy, Argentína.' ),
    array( 'name' => 'Denali',            'lat' =>  63.0692, 'lon' => -151.0070, 'wiki_title' => 'Denali',
        'hint' => 'Najvyšší v Severnej Amerike', 'description' => 'Najvyšší vrch Severnej Ameriky, 6190 m. Aljaška, USA.' ),
    array( 'name' => 'Kilimandžáro',      'lat' => -3.0674, 'lon' =>  37.3556, 'wiki_title' => 'Mount Kilimanjaro',
        'hint' => 'Najvyšší v Afrike', 'description' => 'Najvyšší vrch Afriky, 5895 m. Stratovulkán, Tanzánia.' ),
    array( 'name' => 'Elbrus',            'lat' =>  43.3550, 'lon' =>  42.4392, 'wiki_title' => 'Mount Elbrus',
        'hint' => 'Najvyšší v Európe', 'description' => 'Najvyšší vrch Európy, 5642 m. Kaukaz, Rusko.' ),
    array( 'name' => 'Vinson',            'lat' => -78.5254, 'lon' => -85.6171, 'wiki_title' => 'Vinson Massif',
        'hint' => 'Najvyšší v Antarktíde', 'description' => 'Najvyšší vrch Antarktídy, 4892 m. Vinsonov masív.' ),
    array( 'name' => 'Puncak Jaya',       'lat' => -4.0784, 'lon' => 137.1583, 'wiki_title' => 'Puncak Jaya',
        'hint' => 'Najvyšší v Oceánii', 'description' => 'Najvyšší vrch Oceánie, 4884 m. Nová Guinea, Indonézia.' ),
    array( 'name' => 'Mont Blanc',        'lat' =>  45.8326, 'lon' =>   6.8652, 'wiki_title' => 'Mont Blanc',
        'hint' => 'Najvyšší v Alpách', 'description' => 'Najvyšší vrch Álp a západnej Európy, 4810 m. Hranica Francúzsko/Taliansko.' ),
    array( 'name' => 'Matterhorn',        'lat' =>  45.9763, 'lon' =>   7.6586, 'wiki_title' => 'Matterhorn',
        'hint' => 'Ikona Álp', 'description' => 'Charakteristický pyramídový vrch 4478 m. Pennské Alpy, Švajčiarsko/Taliansko.' ),
    array( 'name' => 'Mount Fuji',        'lat' =>  35.3606, 'lon' => 138.7274, 'wiki_title' => 'Mount Fuji',
        'hint' => 'Najvyšší v Japonsku', 'description' => 'Najvyšší vrch Japonska, 3776 m. Aktívny stratovulkán pri Tokyu.' ),
    array( 'name' => 'Mount Kenya',       'lat' =>  -0.1521, 'lon' =>  37.3084, 'wiki_title' => 'Mount Kenya',
        'hint' => 'Druhý najvyšší v Afrike', 'description' => 'Druhý najvyšší vrch Afriky, 5199 m. Stratovulkán, Keňa.' ),
    array( 'name' => 'Mount Cook',        'lat' => -43.5950, 'lon' => 170.1417, 'wiki_title' => 'Aoraki / Mount Cook',
        'hint' => 'Najvyšší na Novom Zélande', 'description' => 'Aoraki / Mount Cook, najvyšší vrch Nového Zélandu, 3724 m. Južný ostrov.' ),
    array( 'name' => 'Pico de Orizaba',   'lat' =>  19.0306, 'lon' => -97.2691, 'wiki_title' => 'Pico de Orizaba',
        'hint' => 'Najvyšší v Mexiku', 'description' => 'Najvyšší vrch Mexika, 5636 m. Stratovulkán, Sierra Madre.' ),
);

echo "=== Seeding world peaks ===\n";
$id = ek_get_or_create_template( 'Top vrcholy sveta', 'world', 'pin' );
if ( ! $id ) die( "FAIL — could not create template\n" );

// Pin mode — neclearujeme dataset_slug ani feature_pool (pin mode ich nepoužíva)
update_post_meta( $id, '_mapquiz_region', 'world' );
update_post_meta( $id, '_mapquiz_quiz_type', 'pin' );
// World scale tiers — vrcholy sveta sú navzájom tisíce km
update_post_meta( $id, '_mapquiz_score_tiers', wp_json_encode( array(
    array( 'maxKm' =>  200, 'percent' => 100 ),
    array( 'maxKm' =>  500, 'percent' => 75 ),
    array( 'maxKm' => 1000, 'percent' => 50 ),
    array( 'maxKm' => 2000, 'percent' => 25 ),
) ) );

$result = ek_seed_pins_into_template( $id, $peaks );
echo "Template ID: $id, pins: {$result['count']}, photos: {$result['photos']}/{$result['count']}\n";
