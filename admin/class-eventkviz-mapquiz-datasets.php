<?php
/**
 * Mapquiz dataset & overlay registry.
 *
 * Central place kde sú definované všetky bundleované polygon/line/pin datasety
 * a per-region overlay vrstvy. Pridanie nového kvíz typu (železnice, cyklotrasy,
 * jazerá, sopky sveta, …) = pridať záznam do tohto registry + bundle GeoJSON
 * file. Žiadna ďalšia PHP/JS zmena.
 *
 * Conventions:
 * - slug = unique kebab-case identifier
 * - region = 'slovakia' | 'europe' | 'world' (priesečník s _mapquiz_region)
 * - file = relative path v public/data/regions/
 * - geometry = 'polygon' | 'line' | 'point' (== quiz mód)
 * - singular = pre sidebar label „Nájdi <singular>: X"
 */

if ( ! defined( 'WPINC' ) ) die;

class Eventkviz_MapQuiz_Datasets {

    /**
     * Polygon + line + point datasety. Quiz typ 'area' použije polygon datasety,
     * 'line' použije line, 'pin' MOMENTÁLNE používa per-template defined pinov
     * (admin definuje v editore) — ale do budúcnosti môže use point datasety.
     */
    public static function all() {
        return apply_filters( 'eventkviz_mapquiz_datasets', array(

            // ===== Slovakia =====
            'sk-mountains' => array(
                'label'    => 'Pohoria SR',
                'region'   => 'slovakia',
                'file'     => 'sk-mountains.geojson',
                'geometry' => 'polygon',
                'singular' => 'pohorie',
            ),
            'sk-rivers' => array(
                'label'    => 'Rieky SR',
                'region'   => 'slovakia',
                'file'     => 'sk-rivers.geojson',
                'geometry' => 'line',
                'singular' => 'rieku',
                'style'    => array( 'color' => '#3aa6f0', 'weight' => 4 ),
            ),

            // ===== Europe =====
            'europe-countries' => array(
                'label'    => 'Štáty Európy',
                'region'   => 'europe',
                'file'     => 'europe-countries.geojson',
                'geometry' => 'polygon',
                'singular' => 'štát',
            ),
            'europe-rivers' => array(
                'label'    => 'Rieky Európy',
                'region'   => 'europe',
                'file'     => 'europe-rivers.geojson',
                'geometry' => 'line',
                'singular' => 'rieku',
                'style'    => array( 'color' => '#3aa6f0', 'weight' => 3 ),
            ),

            // ===== Future examples (commented out) — easy to add: =====
            // 'sk-national-parks'   => [polygon, slovakia, 'Národné parky SR']
            // 'sk-regions'          => [polygon, slovakia, 'Kraje SR']
            // 'world-countries'     => [polygon, world,    'Štáty sveta']
            // 'germany-states'      => [polygon, europe,   'Spolkové krajiny Nemecka']
            // 'eu-railways-historic'=> [line,    europe,   'Historické železnice (Orient Express, …)']
            // 'tour-de-france'      => [line,    europe,   'Tour de France trasa']
            // 'world-volcanoes'     => [point,   world,    'Sopky sveta']
        ) );
    }

    /** Returns single dataset def by slug, alebo null. */
    public static function get( $slug ) {
        $all = self::all();
        return isset( $all[ $slug ] ) ? array_merge( array( 'slug' => $slug ), $all[ $slug ] ) : null;
    }

    /**
     * Filter datasetov podľa quiz mode (area/line/pin) + region.
     * Použité pre admin dataset dropdown v sub-kvíz editore.
     */
    public static function for_mode_and_region( $quiz_type, $region ) {
        $geom_map = array( 'area' => 'polygon', 'line' => 'line', 'pin' => 'point' );
        $want_geom = $geom_map[ $quiz_type ] ?? null;
        if ( ! $want_geom ) return array();
        $out = array();
        foreach ( self::all() as $slug => $d ) {
            if ( $d['geometry'] !== $want_geom ) continue;
            if ( ! empty( $region ) && $d['region'] !== $region ) continue;
            $out[ $slug ] = array_merge( array( 'slug' => $slug ), $d );
        }
        return $out;
    }

    /**
     * Resolve absolute filesystem path to dataset bundle.
     */
    public static function path( $slug ) {
        $d = self::get( $slug );
        if ( ! $d ) return null;
        return plugin_dir_path( dirname( __FILE__ ) ) . 'public/data/regions/' . $d['file'];
    }

    /**
     * Load features (name list) z bundled dataset súboru.
     * Vráti pole { 'name' => string } pre admin checkbox pool list.
     */
    public static function load_feature_names( $slug ) {
        $path = self::path( $slug );
        if ( ! $path || ! file_exists( $path ) ) return array();
        $gj = json_decode( file_get_contents( $path ), true );
        if ( ! is_array( $gj ) || empty( $gj['features'] ) ) return array();
        $names = array();
        foreach ( $gj['features'] as $f ) {
            if ( ! empty( $f['properties']['name'] ) ) $names[] = $f['properties']['name'];
        }
        sort( $names );
        return $names;
    }

    /**
     * Overlay vrstvy per region. Admin v šablóne uvidí len relevantné options.
     * Každý overlay flag má slug (postmeta key), label (UI text), dataset_file.
     */
    public static function overlays_for_region( $region ) {
        $registry = array(
            'slovakia' => array(
                'cities_main'     => array( 'label' => 'Krajské mestá SR (8)',          'file' => 'sk-cities.geojson',  'tier_filter' => 1 ),
                'cities_regional' => array( 'label' => 'Okresné mestá SR (26)',         'file' => 'sk-cities.geojson',  'tier_filter' => 2 ),
                'regions'         => array( 'label' => 'Kraje SR (8 administratívnych)','file' => 'sk-regions.geojson' ),
                'rivers'          => array( 'label' => 'Rieky SR',                       'file' => 'sk-rivers.geojson' ),
            ),
            'europe' => array(
                'eu_capitals'     => array( 'label' => 'Hlavné mestá Európy (44)',      'file' => 'europe-capitals.geojson' ),
                'eu_borders'      => array( 'label' => 'Hranice štátov',                'file' => 'europe-countries.geojson', 'style' => 'border' ),
                'eu_major_rivers' => array( 'label' => 'Top európske rieky (15)',       'file' => 'europe-rivers.geojson' ),
            ),
            // 'world' — pridať keď stiahneme dáta
        );
        return $registry[ $region ] ?? array();
    }
}
