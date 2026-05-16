<?php
/**
 * Seed šablónu „Krajské mestá SR" — 8 sídiel slovenských krajov.
 *
 * Usage:
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php tools/seed-mesta-sr.php
 */
if ( php_sapi_name() !== 'cli' ) die( 'CLI only' );
define( 'WP_USE_THEMES', false );
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
require_once __DIR__ . '/_seed-helpers.php';

$mesta = array(
    array( 'name' => 'Bratislava',       'lat' => 48.1486, 'lon' => 17.1077, 'wiki_title' => 'Bratislava',
        'hint' => 'Hlavné mesto SR, sídlo NR a vlády',
        'description' => 'Najväčšie a najľudnatejšie mesto Slovenska, leží na Dunaji na hraniciach s Rakúskom a Maďarskom.' ),
    array( 'name' => 'Košice',           'lat' => 48.7164, 'lon' => 21.2611, 'wiki_title' => 'Košice',
        'hint' => 'Druhé najväčšie mesto, východ SR',
        'description' => 'Metropola východného Slovenska, sídlo Košického kraja. Bola Európske hlavné mesto kultúry 2013.' ),
    array( 'name' => 'Prešov',           'lat' => 49.0014, 'lon' => 21.2393, 'wiki_title' => 'Prešov',
        'hint' => 'Sídlo Prešovského kraja, severovýchod',
        'description' => 'Tretie najväčšie mesto SR, kultúrne a vzdelávacie centrum východného Slovenska.' ),
    array( 'name' => 'Žilina',           'lat' => 49.2238, 'lon' => 18.7401, 'wiki_title' => 'Žilina',
        'hint' => 'Sídlo Žilinského kraja, severozápad',
        'description' => 'Križovatka cestnej a železničnej dopravy severného Slovenska, leží na Váhu.' ),
    array( 'name' => 'Banská Bystrica',  'lat' => 48.7395, 'lon' => 19.1535, 'wiki_title' => 'Banská Bystrica',
        'hint' => 'Stredoslovenské centrum, SNP',
        'description' => 'Sídlo Banskobystrického kraja, centrum Slovenského národného povstania (1944).' ),
    array( 'name' => 'Nitra',            'lat' => 48.3082, 'lon' => 18.0876, 'wiki_title' => 'Nitra',
        'hint' => 'Najstaršie mesto SR, sídlo kniežaťa Pribinu',
        'description' => 'Jedno z najstarších slovanských osídlení v Strednej Európe. Sídlo Nitrianskeho kraja.' ),
    array( 'name' => 'Trnava',           'lat' => 48.3774, 'lon' => 17.5887, 'wiki_title' => 'Trnava',
        'hint' => 'Slovenský Rím — najviac kostolov v SR',
        'description' => 'Sídlo Trnavského kraja. Prvé slobodné kráľovské mesto Uhorska (1238).' ),
    array( 'name' => 'Trenčín',          'lat' => 48.8946, 'lon' => 18.0431, 'wiki_title' => 'Trenčín',
        'hint' => 'Sídlo Trenčianskeho kraja, mesto módy',
        'description' => 'Mesto pod Trenčianskym hradom s rímskym nápisom z roku 179 n. l. — Laugaricio.' ),
);

echo "=== Krajské mestá SR ===\n";
$tid = ek_get_or_create_template( 'Krajské mestá SR', 'slovakia', 'pin' );
if ( ! $tid ) exit( 1 );
ek_seed_pins_into_template( $tid, $mesta );
echo "Template ID: $tid\n";
