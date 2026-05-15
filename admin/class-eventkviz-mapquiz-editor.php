<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Admin editor for `mapquiz_template` CPT — meta boxes for map+pins,
 * region/detail selectors, scoring tiers. Enqueues Leaflet + MapTiler
 * + custom JS/CSS only on edit screen for this CPT.
 *
 * Data model (saved as postmeta):
 *   _mapquiz_region        — string (slovakia / czechia / europe / world)
 *   _mapquiz_player_detail — string (outline-only / +regions)
 *   _mapquiz_max_points    — int
 *   _mapquiz_score_tiers   — JSON array [{maxKm, percent}, ...]
 *   _mapquiz_pins          — JSON array [{id, name, hint, photo_id, description, lat, lon}, ...]
 */
class Eventkviz_MapQuiz_Editor {

    const NONCE_NAME   = 'eventkviz_mapquiz_nonce';
    const NONCE_ACTION = 'eventkviz_mapquiz_save';

    const META_REGION        = '_mapquiz_region';
    const META_PLAYER_DETAIL = '_mapquiz_player_detail';
    const META_MAX_POINTS    = '_mapquiz_max_points';
    const META_SCORE_TIERS   = '_mapquiz_score_tiers';
    const META_PINS          = '_mapquiz_pins';
    // JSON object: {"cities":bool,"regions":bool,"rivers":bool}
    // Overlay vodítka pre hráča — ktoré pomocné vrstvy renderovať na blanket mape
    const META_OVERLAYS      = '_mapquiz_overlays';

    const DEFAULT_TIERS = '[{"maxKm":5,"percent":100},{"maxKm":10,"percent":75},{"maxKm":20,"percent":50},{"maxKm":40,"percent":25}]';

    public static function init() {
        add_action( 'add_meta_boxes_' . Eventkviz_MapQuiz_CPT::POST_TYPE, array( __CLASS__, 'register_meta_boxes' ) );
        add_action( 'save_post_' . Eventkviz_MapQuiz_CPT::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== Eventkviz_MapQuiz_CPT::POST_TYPE ) return;

        // WP media library popup (photo picker per pin)
        wp_enqueue_media();

        // Leaflet via CDN
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // Plugin admin editor
        $version = defined( 'EVENKVIZ_VERSION' ) ? EVENKVIZ_VERSION : '1.4.x';
        wp_enqueue_style(
            'eventkviz-mapquiz-editor',
            plugin_dir_url( __FILE__ ) . 'css/mapquiz-editor.css',
            array( 'leaflet' ),
            $version
        );
        wp_enqueue_script(
            'eventkviz-mapquiz-editor',
            plugin_dir_url( __FILE__ ) . 'js/mapquiz-editor.js',
            array( 'leaflet', 'jquery' ),
            $version,
            true
        );

        $maptiler_key = '';
        if ( class_exists( 'Eventkviz_Settings' ) ) {
            $maptiler_key = Eventkviz_Settings::get_maptiler_key();
        }

        wp_localize_script(
            'eventkviz-mapquiz-editor',
            'ekMapquizCfg',
            array(
                'maptilerKey' => $maptiler_key,
                'regions'     => self::get_region_presets(),
                'i18n'        => array(
                    'addPinHint'      => 'Klikni na mapu pre pridanie nového pinu.',
                    'pinNamePlace'    => 'Názov miesta (napr. Tematín)',
                    'pinHintPlace'    => 'Hint (voliteľný)',
                    'pinDescPlace'    => 'Učebný popis miesta (voliteľný)',
                    'photoSelect'     => 'Vybrať fotku',
                    'photoChange'     => 'Zmeniť fotku',
                    'photoRemove'     => 'Odstrániť fotku',
                    'photoModalTitle' => 'Vyber fotku miesta',
                    'photoModalBtn'   => 'Použiť',
                    'deletePin'       => 'Odstrániť pin',
                    'noPins'          => 'Žiadne piny — klikni na mapu pre prvý.',
                    'savedAt'         => 'Uložené:',
                    'noMaptiler'      => 'MapTiler API kľúč nie je nastavený. Otvor EventKviz výsledky → Nastavenia.',
                ),
            )
        );
    }

    public static function get_region_presets() {
        return array(
            'slovakia' => array( 'label' => 'Slovensko',         'center' => array( 48.7,  19.5 ), 'zoom' => 7 ),
            'czechia'  => array( 'label' => 'Česká republika',   'center' => array( 49.8,  15.5 ), 'zoom' => 7 ),
            'europe'   => array( 'label' => 'Európa',            'center' => array( 50.0,  15.0 ), 'zoom' => 4 ),
            'world'    => array( 'label' => 'Svet',              'center' => array( 20.0,   0.0 ), 'zoom' => 2 ),
        );
    }

    public static function get_player_detail_presets() {
        // Pozn.: kľúče musia prejsť sanitize_key() — žiadny `+` ani iné špec. znaky,
        // inak save handler vyhodí value do fallbacku 'outline-only'.
        return array(
            'outline-only'     => 'Iba obrys regiónu',
            'outline-regions'  => 'Obrys + administratívne hranice',
        );
    }

    public static function register_meta_boxes() {
        add_meta_box(
            'eventkviz-mapquiz-meta',
            '🗺️ Mapa + piny',
            array( __CLASS__, 'render_map_meta_box' ),
            Eventkviz_MapQuiz_CPT::POST_TYPE,
            'normal',
            'high'
        );
        add_meta_box(
            'eventkviz-mapquiz-scoring',
            '🏆 Bodovanie',
            array( __CLASS__, 'render_scoring_meta_box' ),
            Eventkviz_MapQuiz_CPT::POST_TYPE,
            'normal',
            'default'
        );
    }

    public static function render_map_meta_box( $post ) {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

        $region        = get_post_meta( $post->ID, self::META_REGION, true ) ?: 'slovakia';
        $player_detail = get_post_meta( $post->ID, self::META_PLAYER_DETAIL, true ) ?: 'outline-only';
        $pins_json     = get_post_meta( $post->ID, self::META_PINS, true );
        if ( empty( $pins_json ) ) $pins_json = '[]';

        $overlays_json = get_post_meta( $post->ID, self::META_OVERLAYS, true );
        $overlays      = is_string( $overlays_json ) && $overlays_json !== '' ? json_decode( $overlays_json, true ) : array();
        if ( ! is_array( $overlays ) ) $overlays = array();

        $regions = self::get_region_presets();
        $details = self::get_player_detail_presets();
        $maptiler_set = ( class_exists( 'Eventkviz_Settings' ) && Eventkviz_Settings::get_maptiler_key() !== '' );

        ?>
        <div class="ekm-editor-toolbar">
            <label>
                <strong>Región:</strong>
                <select name="<?php echo esc_attr( self::META_REGION ); ?>" id="ekm-region">
                    <?php foreach ( $regions as $key => $cfg ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $region, $key ); ?>><?php echo esc_html( $cfg['label'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label style="margin-left:18px">
                <strong>Detail pre hráča:</strong>
                <select name="<?php echo esc_attr( self::META_PLAYER_DETAIL ); ?>" id="ekm-player-detail">
                    <?php foreach ( $details as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $player_detail, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <fieldset style="margin:10px 0; padding:10px; border:1px solid #dcdcde; border-radius:4px; background:#f9f9f9">
            <legend style="font-weight:600; padding:0 6px">Vodítka pre hráča (overlay vrstvy nad blanket mapou)</legend>
            <p class="description" style="margin:0 0 8px">Zaškrtnuté vrstvy sa zobrazia na hráčskej mape ako pomôcka pri hľadaní lokácií. Funguje len pre región <strong>Slovensko</strong> v tejto verzii — dáta sú napevno bundleované v plugine, žiadne online sťahovanie.</p>
            <label style="margin-right:18px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[cities_main]" value="1" <?php checked( ! empty( $overlays['cities_main'] ) ); ?> />
                Krajské mestá (8 — BA, TT, TN, NR, ZA, BB, PO, KE)
            </label>
            <label style="margin-right:18px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[cities_regional]" value="1" <?php checked( ! empty( $overlays['cities_regional'] ) ); ?> />
                Významné okresné mestá (26 — Martin, Poprad, Lučenec, …)
            </label>
            <label style="margin-right:18px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[regions]" value="1" <?php checked( ! empty( $overlays['regions'] ) ); ?> />
                Kraje (8 administratívnych krajov)
            </label>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[rivers]" value="1" <?php checked( ! empty( $overlays['rivers'] ) ); ?> />
                Rieky (Dunaj, Váh, Hron, Hornád, Slaná, Ipeľ, Morava, Dunajec)
            </label>
        </fieldset>

        <?php if ( ! $maptiler_set ) : ?>
            <div class="notice notice-error inline" style="margin:10px 0">
                <p><strong>MapTiler API kľúč nie je nastavený.</strong> Otvor <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=eventkviz_event&page=eventkviz-settings' ) ); ?>">EventKviz → Nastavenia</a> a zadaj ho. Bez kľúča sa mapa nezobrazí.</p>
            </div>
        <?php endif; ?>

        <div id="ekm-map" style="height:480px; margin:14px 0; border:1px solid #ccd0d4; border-radius:4px; background:#f0f0f1;"></div>

        <input type="hidden" name="<?php echo esc_attr( self::META_PINS ); ?>" id="ekm-pins-json" value="<?php echo esc_attr( $pins_json ); ?>" />

        <div class="ekm-pin-editor">
            <h3>Editor pinu</h3>
            <p class="ekm-no-pin-hint">Vyber pin v zozname nižšie (klikni naň) alebo klikni na mapu pre nový.</p>
            <div class="ekm-pin-fields" style="display:none">
                <p><label>Názov: <input type="text" id="ekm-pin-name" class="regular-text" /></label></p>
                <p><label>Hint: <input type="text" id="ekm-pin-hint" class="large-text" /></label></p>
                <p><label>Popis miesta: <textarea id="ekm-pin-description" rows="3" class="large-text"></textarea></label></p>
                <p>
                    <strong>Fotka:</strong>
                    <span id="ekm-pin-photo-preview" class="ekm-photo-preview"></span>
                    <button type="button" class="button" id="ekm-pin-photo-pick">Vybrať fotku</button>
                    <button type="button" class="button" id="ekm-pin-photo-remove" style="display:none">Odstrániť fotku</button>
                </p>
                <p>
                    <strong>Koordináty:</strong>
                    <span class="ekm-coords-display">
                        lat: <span id="ekm-pin-lat-display">—</span>,
                        lon: <span id="ekm-pin-lon-display">—</span>
                    </span>
                    <em style="color:#666"> (drag pin na mape pre úpravu)</em>
                </p>
                <p>
                    <button type="button" class="button button-link-delete" id="ekm-pin-delete">🗑️ Odstrániť tento pin</button>
                </p>
            </div>
        </div>

        <h3 style="margin-top:24px">Piny v tejto šablóne</h3>
        <ol id="ekm-pin-list" class="ekm-pin-list">
            <!-- populated by JS -->
        </ol>
        <p class="description">Klikni na pin v zozname pre editáciu, alebo na mapu pre pridanie nového.</p>
        <?php
    }

    public static function render_scoring_meta_box( $post ) {
        $max_points = (int) ( get_post_meta( $post->ID, self::META_MAX_POINTS, true ) ?: 100 );
        $tiers_json = get_post_meta( $post->ID, self::META_SCORE_TIERS, true );
        if ( empty( $tiers_json ) ) $tiers_json = self::DEFAULT_TIERS;
        ?>
        <p>
            <label>
                <strong>Max body na pin (pri plnom tieri):</strong>
                <input type="number" name="<?php echo esc_attr( self::META_MAX_POINTS ); ?>" value="<?php echo esc_attr( $max_points ); ?>" min="1" max="9999" class="small-text" />
            </label>
        </p>

        <p><strong>Stupne (klesajúce podľa vzdialenosti):</strong></p>
        <table id="ekm-tiers-table" class="widefat" style="max-width:520px">
            <thead>
                <tr>
                    <th style="width:120px">Do vzdialenosti</th>
                    <th style="width:120px">% z max bodov</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody id="ekm-tiers-tbody">
                <!-- populated by JS from tiers_json -->
            </tbody>
        </table>
        <p>
            <button type="button" class="button" id="ekm-tier-add">+ Pridať stupeň</button>
        </p>
        <p class="description">Pri vzdialenosti väčšej než posledný stupeň hráč dostane 0 bodov. Príklad: 0–5 km = 100 %, 5–10 km = 75 %, atď.</p>

        <input type="hidden" name="<?php echo esc_attr( self::META_SCORE_TIERS ); ?>" id="ekm-tiers-json" value="<?php echo esc_attr( $tiers_json ); ?>" />
        <?php
    }

    public static function save_post( $post_id, $post ) {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) return;
        if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Region
        $region = isset( $_POST[ self::META_REGION ] ) ? sanitize_key( $_POST[ self::META_REGION ] ) : 'slovakia';
        if ( ! array_key_exists( $region, self::get_region_presets() ) ) $region = 'slovakia';
        update_post_meta( $post_id, self::META_REGION, $region );

        // Player detail
        $detail = isset( $_POST[ self::META_PLAYER_DETAIL ] ) ? sanitize_key( $_POST[ self::META_PLAYER_DETAIL ] ) : 'outline-only';
        if ( ! array_key_exists( $detail, self::get_player_detail_presets() ) ) $detail = 'outline-only';
        update_post_meta( $post_id, self::META_PLAYER_DETAIL, $detail );

        // Overlay vodítka — checkboxes; unchecked sa v $_POST vôbec nezjavia.
        $overlays_raw = isset( $_POST[ self::META_OVERLAYS ] ) && is_array( $_POST[ self::META_OVERLAYS ] ) ? $_POST[ self::META_OVERLAYS ] : array();
        $overlays_clean = array(
            'cities_main'     => ! empty( $overlays_raw['cities_main'] ),
            'cities_regional' => ! empty( $overlays_raw['cities_regional'] ),
            'regions'         => ! empty( $overlays_raw['regions'] ),
            'rivers'          => ! empty( $overlays_raw['rivers'] ),
        );
        update_post_meta( $post_id, self::META_OVERLAYS, wp_json_encode( $overlays_clean ) );

        // Max points
        $max_points = isset( $_POST[ self::META_MAX_POINTS ] ) ? (int) $_POST[ self::META_MAX_POINTS ] : 100;
        $max_points = max( 1, min( 9999, $max_points ) );
        update_post_meta( $post_id, self::META_MAX_POINTS, $max_points );

        // Pins JSON — validate structure
        $pins_raw = isset( $_POST[ self::META_PINS ] ) ? wp_unslash( $_POST[ self::META_PINS ] ) : '[]';
        $pins_decoded = json_decode( $pins_raw, true );
        $pins_clean = array();
        if ( is_array( $pins_decoded ) ) {
            foreach ( $pins_decoded as $pin ) {
                if ( ! is_array( $pin ) ) continue;
                if ( ! isset( $pin['lat'] ) || ! isset( $pin['lon'] ) ) continue;
                $pins_clean[] = array(
                    'id'          => isset( $pin['id'] ) ? sanitize_text_field( (string) $pin['id'] ) : wp_generate_uuid4(),
                    'name'        => isset( $pin['name'] ) ? sanitize_text_field( (string) $pin['name'] ) : '',
                    'hint'        => isset( $pin['hint'] ) ? sanitize_text_field( (string) $pin['hint'] ) : '',
                    'description' => isset( $pin['description'] ) ? sanitize_textarea_field( (string) $pin['description'] ) : '',
                    'photo_id'    => isset( $pin['photo_id'] ) ? (int) $pin['photo_id'] : 0,
                    'lat'         => (float) $pin['lat'],
                    'lon'         => (float) $pin['lon'],
                );
            }
        }
        // JSON_UNESCAPED_UNICODE: avoid \uXXXX escapes that get mangled by
        // WP's magic-quote roundtrip — keep diacritics as raw UTF-8 in the DB.
        update_post_meta( $post_id, self::META_PINS, wp_json_encode( $pins_clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

        // Score tiers JSON
        $tiers_raw = isset( $_POST[ self::META_SCORE_TIERS ] ) ? wp_unslash( $_POST[ self::META_SCORE_TIERS ] ) : self::DEFAULT_TIERS;
        $tiers_decoded = json_decode( $tiers_raw, true );
        $tiers_clean = array();
        if ( is_array( $tiers_decoded ) ) {
            foreach ( $tiers_decoded as $tier ) {
                if ( ! is_array( $tier ) ) continue;
                if ( ! isset( $tier['maxKm'] ) || ! isset( $tier['percent'] ) ) continue;
                $tiers_clean[] = array(
                    'maxKm'   => max( 0, (float) $tier['maxKm'] ),
                    'percent' => max( 0, min( 100, (float) $tier['percent'] ) ),
                );
            }
            // Sort by maxKm ascending
            usort( $tiers_clean, function ( $a, $b ) {
                return $a['maxKm'] <=> $b['maxKm'];
            } );
        }
        if ( empty( $tiers_clean ) ) {
            $tiers_clean = json_decode( self::DEFAULT_TIERS, true );
        }
        update_post_meta( $post_id, self::META_SCORE_TIERS, wp_json_encode( $tiers_clean ) );
    }
}
