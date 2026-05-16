<?php
/**
 * Seed top 15 svetových sopiek ako pin template (custom pins).
 * Wikipedia fotky cez ek_fetch_wiki_image. Idempotentné.
 *
 * Usage: /Applications/MAMP/bin/php/php8.2.0/bin/php tools/seed-world-volcanoes.php
 */
if ( php_sapi_name() !== 'cli' ) die( 'CLI only' );
define( 'WP_USE_THEMES', false );
$wp_load = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
require_once $wp_load;
require_once __DIR__ . '/_seed-helpers.php';

$volcanoes = array(
    array( 'name' => 'Vezuv',           'lat' =>  40.8210, 'lon' =>  14.4260, 'wiki_title' => 'Mount Vesuvius',
        'hint' => 'Pochoval Pompeje', 'description' => 'Stratovulkán pri Neapole. Erupcia roku 79 n. l. pochovala Pompeje a Herkulaneum.' ),
    array( 'name' => 'Etna',            'lat' =>  37.7510, 'lon' =>  14.9934, 'wiki_title' => 'Mount Etna',
        'hint' => 'Najvyššia sopka v Európe', 'description' => 'Najvyššia aktívna sopka Európy, 3357 m. Sicília, jedna z najaktívnejších na svete.' ),
    array( 'name' => 'Stromboli',       'lat' =>  38.7891, 'lon' =>  15.2130, 'wiki_title' => 'Stromboli',
        'hint' => '„Maják Stredomoria"', 'description' => 'Liparské ostrovy, Taliansko. Erupcie každých pár minút už 2000 rokov.' ),
    array( 'name' => 'Fudži',           'lat' =>  35.3606, 'lon' => 138.7274, 'wiki_title' => 'Mount Fuji',
        'hint' => 'Symbol Japonska', 'description' => 'Najvyšší vrch Japonska, 3776 m. Symetrický stratovulkán, posledná erupcia 1707.' ),
    array( 'name' => 'Krakatoa',        'lat' =>  -6.1024, 'lon' => 105.4232, 'wiki_title' => 'Krakatoa',
        'hint' => 'Erupcia 1883 počuť 4800 km', 'description' => 'Indonézia. Erupcia 1883 (VEI 6) zničila ostrov a vyvolala tsunami; výbuch počuli až v Austrálii.' ),
    array( 'name' => 'Mount St. Helens', 'lat' =>  46.1912, 'lon' => -122.1944, 'wiki_title' => 'Mount St. Helens',
        'hint' => 'Erupcia 1980', 'description' => 'Washington, USA. Erupcia 18.5.1980 odfúkla severnú stenu, 57 obetí, najznámejšia americká vulkanická katastrofa.' ),
    array( 'name' => 'Kīlauea',         'lat' =>  19.4069, 'lon' => -155.2834, 'wiki_title' => 'Kilauea',
        'hint' => 'Najaktívnejšia na svete', 'description' => 'Havaj, USA. Štítová sopka, jedna z najaktívnejších na Zemi — prakticky nepretržite chrlí lávu.' ),
    array( 'name' => 'Mauna Loa',       'lat' =>  19.4750, 'lon' => -155.6080, 'wiki_title' => 'Mauna Loa',
        'hint' => 'Najväčšia sopka sveta', 'description' => 'Havaj. Najväčšia aktívna sopka sveta podľa objemu (~75 000 km³). Štítová sopka, 4170 m.' ),
    array( 'name' => 'Yellowstone',     'lat' =>  44.4280, 'lon' => -110.5885, 'wiki_title' => 'Yellowstone Caldera',
        'hint' => 'Supervulkán pod NP', 'description' => 'Wyoming, USA. Kaldera supervulkánu pod národným parkom; posledná supererupcia pred 640 000 rokmi.' ),
    array( 'name' => 'Popocatépetl',    'lat' =>  19.0233, 'lon' => -98.6228, 'wiki_title' => 'Popocatépetl',
        'hint' => 'Pri Mexico City', 'description' => 'Mexiko, 5426 m. Aktívny stratovulkán 70 km od Mexico City; pravidelné menšie erupcie.' ),
    array( 'name' => 'Eyjafjallajökull','lat' =>  63.6300, 'lon' => -19.6300, 'wiki_title' => 'Eyjafjallajökull',
        'hint' => 'Zastavil európsku letecká dopravu 2010', 'description' => 'Island. Erupcia v apríli 2010 paralyzovala európsku letecký dopravu na 6 dní.' ),
    array( 'name' => 'Mount Pinatubo',  'lat' =>  15.1300, 'lon' => 120.3500, 'wiki_title' => 'Mount Pinatubo',
        'hint' => 'Najväčšia erupcia 20. storočia', 'description' => 'Filipíny. Erupcia 1991 (VEI 6) — druhá najväčšia erupcia 20. storočia, ochladila planétu o 0,5 °C.' ),
    array( 'name' => 'Cotopaxi',        'lat' =>  -0.6840, 'lon' => -78.4380, 'wiki_title' => 'Cotopaxi',
        'hint' => 'Najvyššia aktívna v Andách', 'description' => 'Ekvádor, 5897 m. Jedna z najvyšších aktívnych sopiek sveta, dokonalý kužeľ pokrytý ľadovcom.' ),
    array( 'name' => 'Erebus',          'lat' => -77.5300, 'lon' => 167.1700, 'wiki_title' => 'Mount Erebus',
        'hint' => 'Najjužnejšia aktívna sopka', 'description' => 'Antarktída, 3794 m. Najjužnejšia aktívna sopka sveta; perzistentné lávové jazero v krátere.' ),
    array( 'name' => 'Tambora',         'lat' =>  -8.2500, 'lon' => 118.0000, 'wiki_title' => 'Mount Tambora',
        'hint' => 'Erupcia 1815 — „rok bez leta"', 'description' => 'Indonézia. Erupcia 1815 (VEI 7) bola najsilnejšia za posledných 1300 rokov; rok 1816 = „rok bez leta" v Európe a USA.' ),
);

echo "=== Seeding world volcanoes (15) ===\n";
$id = ek_get_or_create_template( 'Sopky sveta', 'world', 'pin' );
if ( ! $id ) die( "FAIL — could not create template\n" );

update_post_meta( $id, '_mapquiz_region', 'world' );
update_post_meta( $id, '_mapquiz_quiz_type', 'pin' );
// World scale tiers — sopky sveta sú navzájom tisíce km, default SR 5/10/20/40 km
// by znamenal že každý guess je 0 b. Override na world-scale.
update_post_meta( $id, '_mapquiz_score_tiers', wp_json_encode( array(
    array( 'maxKm' =>  200, 'percent' => 100 ),
    array( 'maxKm' =>  500, 'percent' => 75 ),
    array( 'maxKm' => 1000, 'percent' => 50 ),
    array( 'maxKm' => 2000, 'percent' => 25 ),
) ) );

$result = ek_seed_pins_into_template( $id, $volcanoes );
echo "Template ID: $id, pins: {$result['count']}, photos: {$result['photos']}/{$result['count']}\n";
