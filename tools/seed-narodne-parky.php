<?php
/**
 * Seed šablónu „Národné parky SR" — 9 národných parkov.
 * Coords = približný centroid alebo navštevovaná lokalita parku.
 */
if ( php_sapi_name() !== 'cli' ) die( 'CLI only' );
define( 'WP_USE_THEMES', false );
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
require_once __DIR__ . '/_seed-helpers.php';

$parky = array(
    array( 'name' => 'Tatranský národný park (TANAP)', 'lat' => 49.1656, 'lon' => 20.1366,
        'wiki_title' => 'Tatranský národný park',
        'hint' => 'Najstarší NP na Slovensku (1949), Vysoké Tatry',
        'description' => 'Najvyššie pohorie Karpát. Gerlachovský štít 2 655 m je najvyšší vrch SR.' ),
    array( 'name' => 'Národný park Nízke Tatry (NAPANT)', 'lat' => 48.9376, 'lon' => 19.5728,
        'wiki_title' => 'Národný park Nízke Tatry',
        'hint' => 'Najväčší NP, Chopok a Ďumbier',
        'description' => 'Druhé najvyššie pohorie SR. Najznámejšie strediská — Jasná, Donovaly, Tále.' ),
    array( 'name' => 'Pieninský národný park (PIENAP)', 'lat' => 49.4082, 'lon' => 20.4137,
        'wiki_title' => 'Pieninský národný park',
        'hint' => 'Prielom Dunajca, severná hranica s Poľskom',
        'description' => 'Cezhraničný NP. Splav Dunajca na pltiach je svetoznáma atrakcia.' ),
    array( 'name' => 'Národný park Malá Fatra', 'lat' => 49.2123, 'lon' => 19.0428,
        'wiki_title' => 'Národný park Malá Fatra',
        'hint' => 'Vrátna dolina, Veľký Rozsutec',
        'description' => 'NP pri Žiline. Vápencové bralá a tiesňavy Jánošíkovho kraja.' ),
    array( 'name' => 'Národný park Veľká Fatra', 'lat' => 48.9189, 'lon' => 19.0833,
        'wiki_title' => 'Národný park Veľká Fatra',
        'hint' => 'Borišovský chrbát, pri Turčianskych Tepliciach',
        'description' => 'Tisové porasty, krasové jaskyne. Najvyšší vrch Ostredok 1 596 m.' ),
    array( 'name' => 'Národný park Slovenský raj', 'lat' => 48.9243, 'lon' => 20.3826,
        'wiki_title' => 'Národný park Slovenský raj',
        'hint' => 'Tiesňavy a rebríky, pri Spišskej Novej Vsi',
        'description' => 'Hlbové rokliny, vodopády a kovové rebríky. Sucha Belá najznámejšia.' ),
    array( 'name' => 'Národný park Slovenský kras', 'lat' => 48.5839, 'lon' => 20.4856,
        'wiki_title' => 'Národný park Slovenský kras',
        'hint' => 'UNESCO jaskyne, juh SR pri Rožňave',
        'description' => 'Najväčšie krasové územie v Strednej Európe. Domica, Gombasecká jaskyňa.' ),
    array( 'name' => 'Národný park Muránska planina', 'lat' => 48.7553, 'lon' => 20.0719,
        'wiki_title' => 'Národný park Muránska planina',
        'hint' => 'Krasová planina, hucúlske kone',
        'description' => 'Stredoslovenský NP. Chov pôvodných hucúlskych koní v rezervácii.' ),
    array( 'name' => 'Národný park Poloniny', 'lat' => 49.0747, 'lon' => 22.4517,
        'wiki_title' => 'Národný park Poloniny',
        'hint' => 'Najvýchodnejší NP, prales Stužica (UNESCO)',
        'description' => 'Najmenej osídlený region SR. Bukové pralesy zapísané v UNESCO.' ),
);

echo "=== Národné parky SR ===\n";
$tid = ek_get_or_create_template( 'Národné parky SR', 'slovakia', 'pin' );
if ( ! $tid ) exit( 1 );
ek_seed_pins_into_template( $tid, $parky );
echo "Template ID: $tid\n";
