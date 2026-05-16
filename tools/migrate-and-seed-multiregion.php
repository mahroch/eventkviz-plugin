<?php
/**
 * KROK 4 multi-region refaktoru:
 *   - migruje 2 existujúce SK šablóny na nové generic quiz typy (area/line) + dataset_slug
 *   - vytvorí 2 nové EU šablóny (Štáty Európy, Rieky Európy)
 *
 * Spúšťa sa cez MAMP PHP CLI:
 *   /Applications/MAMP/bin/php/php8.2.0/bin/php tools/migrate-and-seed-multiregion.php
 *
 * Idempotentné — keď šablóna existuje (podľa title), updatuje ju, neduplikuje.
 */

if ( PHP_SAPI !== 'cli' ) die( "CLI only\n" );

// Bootstrap WP
$wp_root = realpath( __DIR__ . '/../../../..' );
require_once $wp_root . '/wp-load.php';

if ( ! function_exists( 'wp_insert_post' ) ) die( "WP not loaded\n" );

// Datasets registry musí byť načítaný (eventkviz.php to robí ale len ak hooks behia)
require_once dirname( __DIR__ ) . '/admin/class-eventkviz-mapquiz-datasets.php';

/**
 * Idempotentne nájde / vytvorí mapquiz_template podľa title.
 */
function ek_mr_get_or_create( $title ) {
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

/**
 * Nastaví template: region, quiz_type, dataset_slug, pool.
 * Default-y pre max_points / score_tiers nastaví len ak ešte nie sú.
 */
function ek_mr_configure( $id, $region, $quiz_type, $dataset_slug, $pool ) {
    update_post_meta( $id, '_mapquiz_region',       $region );
    update_post_meta( $id, '_mapquiz_quiz_type',    $quiz_type );
    update_post_meta( $id, '_mapquiz_dataset_slug', $dataset_slug );
    update_post_meta( $id, '_mapquiz_feature_pool', wp_slash( wp_json_encode( $pool, JSON_UNESCAPED_UNICODE ) ) );
    if ( ! get_post_meta( $id, '_mapquiz_max_points', true ) ) {
        update_post_meta( $id, '_mapquiz_max_points', 100 );
    }
    echo "    region=$region quiz_type=$quiz_type dataset=$dataset_slug pool=" . count( $pool ) . " features\n";
}

echo "=== KROK 4: migrate + seed multi-region templates ===\n\n";

/**
 * Migrácia legacy SK template (s pôvodným title) na nový title + nový quiz_type/dataset.
 * Order matters: najprv premenovať legacy, potom get_or_create — inak by sa vytvorila nová prázdna.
 */
function ek_mr_migrate_or_create( $old_title, $new_title, $region, $quiz_type, $dataset_slug, $default_pool ) {
    $legacy = get_posts( array(
        'post_type' => 'mapquiz_template', 'title' => $old_title,
        'posts_per_page' => 1, 'post_status' => array( 'publish', 'draft' ),
    ) );
    if ( ! empty( $legacy ) ) {
        $id = (int) $legacy[0]->ID;
        wp_update_post( array( 'ID' => $id, 'post_title' => $new_title ) );
        echo "  Renamed legacy '$old_title' → '$new_title' (ID $id)\n";
    } else {
        $id = ek_mr_get_or_create( $new_title );
        if ( ! $id ) return 0;
    }
    // Zachovaj pool ak existuje, inak default
    $pool_json = get_post_meta( $id, '_mapquiz_feature_pool', true );
    $pool = $pool_json ? json_decode( $pool_json, true ) : array();
    if ( ! is_array( $pool ) || empty( $pool ) ) $pool = $default_pool;
    ek_mr_configure( $id, $region, $quiz_type, $dataset_slug, $pool );
    return $id;
}

// 1) Pohoria SR — migrácia mountain → area + sk-mountains
echo "[1/4] Pohoria SR (migrácia 'Pohoria' → 'Pohoria SR', mountain → area)\n";
ek_mr_migrate_or_create(
    'Pohoria', 'Pohoria SR', 'slovakia', 'area', 'sk-mountains',
    array( 'Vysoké Tatry', 'Nízke Tatry', 'Malá Fatra', 'Veľká Fatra', 'Malé Karpaty' )
);

// 2) Rieky SR — migrácia river → line + sk-rivers (default pool: klasických 8 SK riek)
echo "\n[2/4] Rieky SR (migrácia 'Rieky' → 'Rieky SR', river → line)\n";
ek_mr_migrate_or_create(
    'Rieky', 'Rieky SR', 'slovakia', 'line', 'sk-rivers',
    array( 'Dunaj', 'Váh', 'Hron', 'Hornád', 'Slaná', 'Ipeľ', 'Morava', 'Dunajec' )
);

// 3) Štáty Európy — nová šablóna
echo "\n[3/4] Štáty Európy (nová)\n";
$id = ek_mr_get_or_create( 'Štáty Európy' );
if ( $id ) {
    // Selected 25 well-known European countries (geografia ZŠ + SŠ pokrývacia úroveň).
    // Vynechané: micro-states (Andorra, Malta, Luxembursko, Cyprus, Bosna), niektoré
    // bývalé juhoslovanské, Bielorusko/Moldavsko — admin si môže doklikávať.
    $eu_pool = array(
        'Francúzsko', 'Nemecko', 'Taliansko', 'Španielsko', 'Spojené kráľovstvo',
        'Poľsko', 'Maďarsko', 'Rakúsko', 'Česko', 'Slovensko',
        'Rumunsko', 'Bulharsko', 'Grécko', 'Portugalsko', 'Holandsko',
        'Belgicko', 'Švédsko', 'Nórsko', 'Fínsko', 'Dánsko',
        'Írsko', 'Švajčiarsko', 'Ukrajina', 'Turecko', 'Chorvátsko',
    );
    ek_mr_configure( $id, 'europe', 'area', 'europe-countries', $eu_pool );
}

// 4) Rieky Európy — nová šablóna
echo "\n[4/4] Rieky Európy (nová)\n";
$id = ek_mr_get_or_create( 'Rieky Európy' );
if ( $id ) {
    // Všetkých 13 európskych riek z bundleu.
    $eu_rivers = Eventkviz_MapQuiz_Datasets::load_feature_names( 'europe-rivers' );
    ek_mr_configure( $id, 'europe', 'line', 'europe-rivers', $eu_rivers );
}

echo "\n=== Hotovo ===\n";
