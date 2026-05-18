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
    // JSON object: {tile_*:bool, cities_*:bool, regions:bool, rivers:bool}
    const META_OVERLAYS      = '_mapquiz_overlays';
    // Typ kvízu: 'pin' (default — pin-based haversine), 'line' (čiarové features),
    // 'area' (polygon features — pohorie/štát/región).
    const META_QUIZ_TYPE     = '_mapquiz_quiz_type';
    // Pre area/line — slug datasetu z Eventkviz_MapQuiz_Datasets registry
    // (napr. 'sk-mountains', 'europe-countries', 'sk-rivers').
    const META_DATASET_SLUG  = '_mapquiz_dataset_slug';
    // JSON array of feature names (pre area/line — admin si vyberie subset
    // z dataset poolu; hráč dostane N náhodných z výberu).
    const META_FEATURE_POOL  = '_mapquiz_feature_pool';

    // Pre area/line — zdroj features: 'bundle' (z registry GeoJSON file) alebo 'custom'
    // (vlastné polygony/línie nakreslené adminom cez Leaflet.draw v editore).
    const META_FEATURES_SOURCE = '_mapquiz_features_source';
    // Custom GeoJSON FeatureCollection (JSON string) — uložené per template ak
    // features_source = 'custom'. Každá feature má properties.name + geometry
    // (Polygon pre area, LineString pre line).
    const META_CUSTOM_FEATURES = '_mapquiz_custom_features';

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

        // Leaflet.draw plugin — pre area/line vlastné kreslenie polygónov/línií.
        // Local bundle v public/vendor/leaflet.draw/ (MIT licensed, v1.0.4).
        $vendor_url = plugin_dir_url( dirname( __FILE__ ) ) . 'public/vendor/leaflet.draw/';
        wp_enqueue_style(
            'leaflet-draw',
            $vendor_url . 'leaflet.draw.css',
            array( 'leaflet' ),
            '1.0.4'
        );
        wp_enqueue_script(
            'leaflet-draw',
            $vendor_url . 'leaflet.draw.js',
            array( 'leaflet' ),
            '1.0.4',
            true
        );

        wp_enqueue_style(
            'eventkviz-mapquiz-editor',
            plugin_dir_url( __FILE__ ) . 'css/mapquiz-editor.css',
            array( 'leaflet', 'leaflet-draw' ),
            $version
        );
        wp_enqueue_script(
            'eventkviz-mapquiz-editor',
            plugin_dir_url( __FILE__ ) . 'js/mapquiz-editor.js',
            array( 'leaflet', 'leaflet-draw', 'jquery' ),
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

        $quiz_type = get_post_meta( $post->ID, self::META_QUIZ_TYPE, true ) ?: 'pin';
        $feature_pool_json = get_post_meta( $post->ID, self::META_FEATURE_POOL, true );
        $feature_pool = is_string( $feature_pool_json ) && $feature_pool_json !== '' ? json_decode( $feature_pool_json, true ) : array();
        if ( ! is_array( $feature_pool ) ) $feature_pool = array();

        $regions = self::get_region_presets();
        $details = self::get_player_detail_presets();
        $maptiler_set = ( class_exists( 'Eventkviz_Settings' ) && Eventkviz_Settings::get_maptiler_key() !== '' );

        // Pool pre area/line mode sa load-uje dynamicky z dataset registry
        // podľa quiz_type + región. Datasety s matching geometry + región
        // figurujú v dataset dropdowne; pre vybraný dataset sa vyrenderuje
        // checkbox list features (admin si vyberie pool).
        $available_datasets = array();
        if ( in_array( $quiz_type, array( 'area', 'line' ), true ) ) {
            $available_datasets = Eventkviz_MapQuiz_Datasets::for_mode_and_region( $quiz_type, $region );
        }

        // Aktuálne vybraný dataset — len ak match-uje aktuálny quiz_type + region.
        // Ak admin zmení quiz_type (area→line) alebo region (slovakia→europe),
        // postmeta môže obsahovať „stale" slug z predchádzajúcej konfigurácie
        // (napr. „europe-countries" zostal pri prepnutí na line mode).
        // Defaultneme na prvý dataset z available_datasets aby UI a save handler
        // vždy operovali na konzistentnom stave.
        $stored_dataset_slug = get_post_meta( $post->ID, '_mapquiz_dataset_slug', true );
        $current_dataset_slug = ( $stored_dataset_slug && isset( $available_datasets[ $stored_dataset_slug ] ) )
            ? $stored_dataset_slug
            : ( ! empty( $available_datasets ) ? array_key_first( $available_datasets ) : '' );
        // Features pool pre vybraný dataset (z bundle súboru cez registry)
        $available_features = $current_dataset_slug
            ? Eventkviz_MapQuiz_Datasets::load_feature_names( $current_dataset_slug )
            : array();

        // Vlastné features (custom draw): zdroj features per template — 'bundle' (default,
        // z registry) alebo 'custom' (admin si nakreslí polygony/línie cez Leaflet.draw).
        $features_source = get_post_meta( $post->ID, self::META_FEATURES_SOURCE, true ) ?: 'bundle';
        if ( ! in_array( $features_source, array( 'bundle', 'custom' ), true ) ) $features_source = 'bundle';
        $custom_features_json = get_post_meta( $post->ID, self::META_CUSTOM_FEATURES, true );
        if ( empty( $custom_features_json ) ) $custom_features_json = '{"type":"FeatureCollection","features":[]}';
        ?>

        <div class="ekm-editor-toolbar" style="margin-bottom:14px; padding:10px; background:#eef4fa; border-left:3px solid #2271b1; border-radius:0 4px 4px 0">
            <label>
                <strong>Typ kvízu:</strong>
                <select name="<?php echo esc_attr( self::META_QUIZ_TYPE ); ?>" id="ekm-quiz-type" onchange="document.querySelectorAll('.ekm-mode').forEach(function(el){el.style.display='none'});var m=document.getElementById('ekm-mode-'+this.value);if(m)m.style.display='';">
                    <option value="pin"  <?php selected( $quiz_type, 'pin' ); ?>>Hľadanie miest na mape (klik kdekoľvek, scoring podľa vzdialenosti)</option>
                    <option value="line" <?php selected( $quiz_type, 'line' ); ?>>Označenie čiarového objektu (rieka, železnica, cyklotrasa…)</option>
                    <option value="area" <?php selected( $quiz_type, 'area' ); ?>>Označenie územia / oblasti (pohorie, štát, národný park, región…)</option>
                </select>
            </label>
            <p class="description" style="margin:6px 0 0">
                <strong>Hľadanie miest (pin):</strong> admin definuje konkrétne body (lat/lon), hráč klikne kdekoľvek a body sú podľa vzdialenosti.<br>
                <strong>Čiara / Územie:</strong> hráč dostane úlohu „nájdi <em>X</em>", musí kliknúť priamo na danú feature. Admin si v dataset dropdowne vyberie zo zoznamu bundleovaných datasetov (pre vybraný región). Hodnotenie je „buď trafil — plné body, alebo netrafil — 0 bodov".
            </p>
        </div>

        <div class="ekm-editor-toolbar">
            <label>
                <strong>Región:</strong>
                <select name="<?php echo esc_attr( self::META_REGION ); ?>" id="ekm-region">
                    <?php foreach ( $regions as $key => $cfg ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $region, $key ); ?>><?php echo esc_html( $cfg['label'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

        </div>

        <fieldset style="margin:10px 0; padding:10px; border:1px solid #dcdcde; border-radius:4px; background:#f9f9f9">
            <legend style="font-weight:600; padding:0 6px">Mapové podklady pre hráča</legend>
            <p class="description" style="margin:0 0 8px">
                Default je <strong>iba obrys</strong> regiónu na bielom pozadí (najťažšia úroveň pre hráča, žiadny MapTiler tile cost).
                Zaškrtni jednu alebo viac tile vrstiev — hráč ich potom môže prepínať tlačidlom v rohu mapy.
                Napr. pre žiakov povoľ Streets + Outdoor; pre pokročilých nechaj iba obrys.
            </p>
            <label style="margin-right:18px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[tile_streets]" value="1" <?php checked( ! empty( $overlays['tile_streets'] ) ); ?> />
                Streets (uličná mapa s názvami)
            </label>
            <label style="margin-right:18px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[tile_satellite]" value="1" <?php checked( ! empty( $overlays['tile_satellite'] ) ); ?> />
                Satelit (letecké zábery)
            </label>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[tile_outdoor]" value="1" <?php checked( ! empty( $overlays['tile_outdoor'] ) ); ?> />
                Outdoor (turistická / topografická)
            </label>
        </fieldset>

        <?php
        // Per-region overlay registry — admin uvidí len overlays relevantné pre selected región.
        // Vrámci jedného regiónu sa môže pridať nový overlay len pridaním do registra,
        // bez zmien v editor.php.
        $region_overlays = Eventkviz_MapQuiz_Datasets::overlays_for_region( $region );
        if ( ! empty( $region_overlays ) ) :
            $region_label = $regions[ $region ]['label'] ?? $region;
        ?>
        <fieldset style="margin:10px 0; padding:10px; border:1px solid #dcdcde; border-radius:4px; background:#f9f9f9">
            <legend style="font-weight:600; padding:0 6px">Geografické vodítka (overlay nad mapou)</legend>
            <p class="description" style="margin:0 0 8px">
                Zaškrtnuté vrstvy sa zobrazia ako pomôcka pre hráča. Overlays sú špecifické pre región: <strong><?php echo esc_html( $region_label ); ?></strong>.
            </p>
            <?php foreach ( $region_overlays as $slug => $def ) : ?>
                <label style="margin-right:18px">
                    <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( ! empty( $overlays[ $slug ] ) ); ?> />
                    <?php echo esc_html( $def['label'] ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <?php // Pomôcky relevantné iba pre area/line quiz typy — skryté pre pin ?>
        <?php $hide_features_helpers = ! in_array( $quiz_type, array( 'area', 'line' ), true ); ?>
        <fieldset class="ekm-mode" id="ekm-mode-feature-labels" <?php if ( $hide_features_helpers ) echo 'style="display:none"'; ?>
            style="margin:10px 0; padding:10px; border:1px solid #dcdcde; border-radius:4px; background:#f9f9f9">
            <legend style="font-weight:600; padding:0 6px">Pomôcky pre hráča (oblasti / čiarové features)</legend>

            <label style="display:block; padding:4px 0">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[feature_labels]" value="1" <?php checked( ! empty( $overlays['feature_labels'] ) ); ?> />
                <strong>Zobraziť názvy pri hover myšou</strong>
                <p class="description" style="margin:4px 0 0 24px">
                    <strong>Vypnuté</strong> (default): hráč nevidí názov features pri hover — musí ich vedieť rozpoznať podľa polohy.<br>
                    <strong>Zapnuté:</strong> hráč pri hover uvidí názov v tooltip. Vhodné pre žiakov / vzdelávacie účely.
                </p>
            </label>

            <label style="display:block; padding:4px 0; margin-top:6px; border-top:1px dashed #ccd0d4; padding-top:10px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[feature_labels_permanent]" value="1" <?php checked( ! empty( $overlays['feature_labels_permanent'] ) ); ?> />
                <strong>Zobraziť názov priamo na polygone / čiare</strong> (vždy viditeľné, aj bez hover)
                <p class="description" style="margin:4px 0 0 24px">
                    <strong>Vypnuté</strong> (default): názov sa zobrazí len pri hover (ak je „názvy pri hover" zapnuté).<br>
                    <strong>Zapnuté:</strong> názov features je vždy viditeľný — extrémne uľahčenie, vhodné len pre úvodné lekcie / deti. Pre súťažné kvízy nechaj vypnuté.
                </p>
            </label>

            <label style="display:block; padding:4px 0; margin-top:6px; border-top:1px dashed #ccd0d4; padding-top:10px">
                <input type="checkbox" name="<?php echo esc_attr( self::META_OVERLAYS ); ?>[feature_only_set]" value="1" <?php checked( ! empty( $overlays['feature_only_set'] ) ); ?> />
                <strong>Zobraziť na mape iba features ktoré hráč háda</strong> (skry rozptyľovače)
                <p class="description" style="margin:4px 0 0 24px">
                    <strong>Vypnuté</strong> (default): hráč vidí <em>všetky</em> features z poolu a musí ich rozlíšiť podľa polohy. Ťažšie, viac výberu.<br>
                    <strong>Zapnuté:</strong> hráč vidí iba tie features ktoré práve <em>háda</em> (napr. admin nastaví pool 10, hráč háda 3 → vidí len tie 3 zvýraznené). Eliminuje rozptyľovače — vhodné pre žiakov.
                </p>
            </label>
        </fieldset>

        <?php if ( ! $maptiler_set ) : ?>
            <div class="notice notice-error inline" style="margin:10px 0">
                <p><strong>MapTiler API kľúč nie je nastavený.</strong> Otvor <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=eventkviz_event&page=eventkviz-settings' ) ); ?>">EventKviz → Nastavenia</a> a zadaj ho. Bez kľúča sa mapa nezobrazí.</p>
            </div>
        <?php endif; ?>

        <!-- Sekcia pre quiz_type = pin (pin editor + mapa) -->
        <div class="ekm-mode" id="ekm-mode-pin" <?php if ( $quiz_type !== 'pin' ) echo 'style="display:none"'; ?>>
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
        </div><!-- /ekm-mode-pin -->

        <!-- Sekcia pre quiz_type = area | line (zdieľaná — len label sa líši) -->
        <?php foreach ( array( 'line' => 'Čiarové objekty', 'area' => 'Územia / oblasti' ) as $mode => $mode_label ) : ?>
        <div class="ekm-mode" id="ekm-mode-<?php echo esc_attr( $mode ); ?>" <?php if ( $quiz_type !== $mode ) echo 'style="display:none"'; ?>>
            <div style="margin:14px 0; padding:14px; background:#fff; border:1px solid #ccd0d4; border-radius:4px">
                <h3 style="margin-top:0"><?php echo esc_html( $mode_label ); ?> — zdroj features</h3>

                <p>
                    <label style="margin-right:18px">
                        <input type="radio" name="<?php echo esc_attr( self::META_FEATURES_SOURCE ); ?>" value="bundle" class="ekm-source-radio" data-mode="<?php echo esc_attr( $mode ); ?>" <?php checked( $features_source, 'bundle' ); ?> />
                        <strong>Bundle dataset</strong> (preddefinovaný v plugine — pohoria SR, štáty EU, atď.)
                    </label>
                    <label>
                        <input type="radio" name="<?php echo esc_attr( self::META_FEATURES_SOURCE ); ?>" value="custom" class="ekm-source-radio" data-mode="<?php echo esc_attr( $mode ); ?>" <?php checked( $features_source, 'custom' ); ?> />
                        <strong>Vlastné kreslenie</strong> (nakresli polygony/línie na mape sám)
                    </label>
                </p>

                <!-- Sekcia BUNDLE — dropdown + checkbox pool -->
                <div class="ekm-source-section ekm-source-bundle" data-mode="<?php echo esc_attr( $mode ); ?>" <?php if ( $features_source !== 'bundle' ) echo 'style="display:none"'; ?>>
                    <p>
                        <label><strong>Dataset:</strong>
                            <select name="<?php echo esc_attr( self::META_DATASET_SLUG ); ?>" class="ekm-dataset-select" data-mode="<?php echo esc_attr( $mode ); ?>">
                                <?php
                                $mode_datasets = ( $quiz_type === $mode ) ? $available_datasets : Eventkviz_MapQuiz_Datasets::for_mode_and_region( $mode, $region );
                                if ( empty( $mode_datasets ) ) {
                                    echo '<option value="">— Žiadne datasety pre vybraný región —</option>';
                                } else {
                                    foreach ( $mode_datasets as $ds_slug => $ds ) {
                                        printf(
                                            '<option value="%s"%s>%s</option>',
                                            esc_attr( $ds_slug ),
                                            selected( $current_dataset_slug, $ds_slug, false ),
                                            esc_html( $ds['label'] )
                                        );
                                    }
                                }
                                ?>
                            </select>
                        </label>
                        <span class="description" style="margin-left:8px">Datasety filtrované podľa <strong>regiónu</strong> + <strong>typu kvízu</strong>. Zmeň región vyššie ak chceš iné.</span>
                    </p>

                    <?php if ( ! empty( $available_features ) && $quiz_type === $mode ) : ?>
                    <p><strong>Vyber features do poolu:</strong></p>
                    <div style="columns:3; max-width:760px; column-gap:24px">
                        <?php foreach ( $available_features as $fname ) : ?>
                            <label style="display:block; padding:3px 0">
                                <input type="checkbox" name="<?php echo esc_attr( self::META_FEATURE_POOL ); ?>[]" value="<?php echo esc_attr( $fname ); ?>" <?php checked( in_array( $fname, $feature_pool, true ) ); ?> />
                                <?php echo esc_html( $fname ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top:10px">Vyberte aspoň toľko features ako je <em>Počet otázok v sete</em> v evente, inak sa pool capne na zaškrtnuté. Pre uloženie checkboxov treba <strong>Update</strong>; po zmene datasetu/regiónu treba uložiť a feature list sa nahrá.</p>
                    <?php elseif ( $quiz_type === $mode ) : ?>
                    <p class="description" style="margin-top:10px">Po uložení vybraného datasetu sa tu zobrazí zoznam features na zaškrtnutie.</p>
                    <?php endif; ?>
                </div>

                <!-- Sekcia CUSTOM DRAW — Leaflet.draw mapa + nakreslené features list -->
                <div class="ekm-source-section ekm-source-custom" data-mode="<?php echo esc_attr( $mode ); ?>" <?php if ( $features_source !== 'custom' ) echo 'style="display:none"'; ?>>
                    <p class="description" style="margin:0 0 10px">
                        Nakresli vlastné <strong><?php echo $mode === 'line' ? 'línie' : 'polygony'; ?></strong> priamo na mape. Použi tlačidlá v ľavom hornom rohu: <strong>nakresliť</strong>, <strong>upraviť</strong> (drag vertexov), <strong>vymazať</strong>. Po nakreslení sa otvorí dialóg na pomenovanie feature.
                    </p>
                    <div id="ekm-draw-map-<?php echo esc_attr( $mode ); ?>" class="ekm-draw-map" data-mode="<?php echo esc_attr( $mode ); ?>" style="height:480px; width:100%; max-width:1100px; border:1px solid #ccd0d4; border-radius:4px;"></div>
                    <h4 style="margin:18px 0 6px">Nakreslené features (<span class="ekm-draw-count" data-mode="<?php echo esc_attr( $mode ); ?>">0</span>)</h4>
                    <ul class="ekm-draw-list" data-mode="<?php echo esc_attr( $mode ); ?>" style="margin:0;padding:0;list-style:none;max-width:540px"></ul>
                    <p class="description" style="margin-top:8px">Klik na názov vo zozname → fokus na feature na mape. Premenovať: ⏵ ikona. Vymazať: ✕ ikona (alebo cez delete tool v ľavej toolbar).</p>
                </div>
            </div>
        </div><!-- /ekm-mode-<?php echo esc_attr( $mode ); ?> -->
        <?php endforeach; ?>

        <!-- Hidden inputy pre custom features — JS ich plní pri nakreslení/editovaní -->
        <input type="hidden" name="<?php echo esc_attr( self::META_CUSTOM_FEATURES ); ?>" id="ekm-custom-features-json" value="<?php echo esc_attr( $custom_features_json ); ?>" />

        <?php
    }

    public static function render_scoring_meta_box( $post ) {
        $max_points = (int) ( get_post_meta( $post->ID, self::META_MAX_POINTS, true ) ?: 100 );
        $tiers_json = get_post_meta( $post->ID, self::META_SCORE_TIERS, true );
        if ( empty( $tiers_json ) ) $tiers_json = self::DEFAULT_TIERS;
        $quiz_type  = get_post_meta( $post->ID, self::META_QUIZ_TYPE, true ) ?: 'pin';
        $is_pin     = ( $quiz_type === 'pin' );
        ?>
        <p>
            <label>
                <strong><?php echo $is_pin ? 'Max body za pin (pri plnom tieri):' : 'Max body za úlohu (správna feature):'; ?></strong>
                <input type="number" name="<?php echo esc_attr( self::META_MAX_POINTS ); ?>" value="<?php echo esc_attr( $max_points ); ?>" min="1" max="9999" class="small-text" />
            </label>
        </p>

        <?php if ( $is_pin ) : ?>
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
        <?php else : ?>
            <div style="padding:10px 12px; background:#f0f6fc; border-left:3px solid #2271b1; color:#1d2327; font-size:13px; max-width:520px">
                ℹ Pre šablóny typu „<strong><?php echo esc_html( $quiz_type === 'line' ? 'Označenie čiarového objektu' : 'Označenie územia / oblasti' ); ?></strong>" je hodnotenie jednoduché: hráč buď klikne na správnu feature (= <strong>plné body</strong> za úlohu), alebo nie (= <strong>0 bodov</strong>). Stupne podľa vzdialenosti sa neuplatňujú.
            </div>
        <?php endif; ?>

        <input type="hidden" name="<?php echo esc_attr( self::META_SCORE_TIERS ); ?>" id="ekm-tiers-json" value="<?php echo esc_attr( $tiers_json ); ?>" />
        <?php
    }

    /**
     * Validuje + sanitizuje custom features GeoJSON (z editor draw mapy).
     * Vráti JSON string FeatureCollection — buď čistý vstup alebo „prázdny".
     *
     * Validácia:
     *   - musí byť FeatureCollection
     *   - každá feature musí mať properties.name + valid geometry
     *   - Polygon: min 3 vertexy (4 vrátane closing); MultiPolygon povolené
     *   - LineString: min 2 vertexy; MultiLineString povolené
     *   - quiz_type='area' → akceptuje len Polygon/MultiPolygon
     *   - quiz_type='line' → akceptuje len LineString/MultiLineString
     *
     * Nevalid features sa preskočia (silently), výsledok môže mať menej features
     * ako vstup. Pre quiz_type='pin' vrátime prázdnu collection (custom irrelevant).
     */
    public static function sanitize_custom_features( $raw_json, $quiz_type ) {
        $empty = '{"type":"FeatureCollection","features":[]}';
        if ( $quiz_type === 'pin' || $raw_json === '' ) return $empty;
        $data = json_decode( $raw_json, true );
        if ( ! is_array( $data ) || ( $data['type'] ?? '' ) !== 'FeatureCollection' || ! is_array( $data['features'] ?? null ) ) {
            return $empty;
        }
        $allowed_geom = ( $quiz_type === 'area' )
            ? array( 'Polygon', 'MultiPolygon' )
            : array( 'LineString', 'MultiLineString' );

        $clean_features = array();
        foreach ( $data['features'] as $feat ) {
            if ( ! is_array( $feat ) ) continue;
            $geom = $feat['geometry'] ?? null;
            $name = isset( $feat['properties']['name'] ) ? sanitize_text_field( (string) $feat['properties']['name'] ) : '';
            if ( $name === '' || ! is_array( $geom ) ) continue;
            $gtype = $geom['type'] ?? '';
            if ( ! in_array( $gtype, $allowed_geom, true ) ) continue;
            $coords = $geom['coordinates'] ?? null;
            if ( ! is_array( $coords ) ) continue;

            // Min vertex counts
            $valid = false;
            if ( $gtype === 'Polygon' ) {
                $valid = is_array( $coords[0] ?? null ) && count( $coords[0] ) >= 4; // closed ring = 4 (3 unique + dup)
            } elseif ( $gtype === 'MultiPolygon' ) {
                foreach ( $coords as $poly ) {
                    if ( is_array( $poly[0] ?? null ) && count( $poly[0] ) >= 4 ) { $valid = true; break; }
                }
            } elseif ( $gtype === 'LineString' ) {
                $valid = count( $coords ) >= 2;
            } elseif ( $gtype === 'MultiLineString' ) {
                foreach ( $coords as $ls ) {
                    if ( is_array( $ls ) && count( $ls ) >= 2 ) { $valid = true; break; }
                }
            }
            if ( ! $valid ) continue;

            $clean_features[] = array(
                'type'       => 'Feature',
                'properties' => array( 'name' => $name ),
                'geometry'   => array( 'type' => $gtype, 'coordinates' => $coords ),
            );
        }

        return wp_json_encode( array(
            'type'     => 'FeatureCollection',
            'features' => $clean_features,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
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

        // Quiz type — nové generic: pin / area / line
        $quiz_type = isset( $_POST[ self::META_QUIZ_TYPE ] ) ? sanitize_key( $_POST[ self::META_QUIZ_TYPE ] ) : 'pin';
        if ( ! in_array( $quiz_type, array( 'pin', 'area', 'line' ), true ) ) $quiz_type = 'pin';
        update_post_meta( $post_id, self::META_QUIZ_TYPE, $quiz_type );

        // Dataset slug (pre area/line) — referenc do Eventkviz_MapQuiz_Datasets registry.
        // Validujeme:
        //   1. dataset existuje v registry
        //   2. geometry zodpovedá quiz_type (area=polygon, line=line, pin=point)
        //   3. region zodpovedá template regionu
        // Browsery posielajú HTML form values aj pre skryté `display:none` sekcie
        // (area dropdown ostane v $_POST aj keď admin prepol na line) — guard
        // proti tomu aby sa „stale" slug uložil pri zmene quiz_type alebo regionu.
        $dataset_slug = isset( $_POST[ self::META_DATASET_SLUG ] ) ? sanitize_key( $_POST[ self::META_DATASET_SLUG ] ) : '';
        if ( $dataset_slug !== '' && in_array( $quiz_type, array( 'area', 'line' ), true ) ) {
            $allowed = Eventkviz_MapQuiz_Datasets::for_mode_and_region( $quiz_type, $region );
            if ( ! isset( $allowed[ $dataset_slug ] ) ) {
                // Mismatch: dataset nie je platný pre tento quiz_type+region.
                // Fallback: prvý platný dataset (alebo prázdno ak žiadny).
                $dataset_slug = ! empty( $allowed ) ? array_key_first( $allowed ) : '';
            }
        } else {
            // Pin mode — clearuj akýkoľvek leftover dataset_slug (relevantný len pre area/line).
            $dataset_slug = '';
        }
        update_post_meta( $post_id, self::META_DATASET_SLUG, $dataset_slug );

        // Features source — 'bundle' alebo 'custom'. Default 'bundle' (legacy compat).
        $features_source = isset( $_POST[ self::META_FEATURES_SOURCE ] ) ? sanitize_key( $_POST[ self::META_FEATURES_SOURCE ] ) : 'bundle';
        if ( ! in_array( $features_source, array( 'bundle', 'custom' ), true ) ) $features_source = 'bundle';
        // Pre pin mode features source nemá zmysel — clearuj na bundle.
        if ( $quiz_type === 'pin' ) $features_source = 'bundle';
        update_post_meta( $post_id, self::META_FEATURES_SOURCE, $features_source );

        // Custom GeoJSON FeatureCollection — validuj štruktúru + sanitizuj názvy.
        $custom_features_raw = isset( $_POST[ self::META_CUSTOM_FEATURES ] ) ? wp_unslash( (string) $_POST[ self::META_CUSTOM_FEATURES ] ) : '';
        $custom_features_clean = self::sanitize_custom_features( $custom_features_raw, $quiz_type );
        update_post_meta( $post_id, self::META_CUSTOM_FEATURES, wp_slash( $custom_features_clean ) );

        // Feature pool — pre bundle source = array of feature names z dataset bundleu;
        // pre custom source = array všetkých custom feature names (admin vyberá v UI
        // checkboxom kvôli konzistencii — alebo pri custom default = všetky nakreslené).
        if ( $features_source === 'custom' ) {
            // Pri custom source pool = názvy všetkých nakreslených features (autopool).
            $custom_obj = json_decode( $custom_features_clean, true );
            $pool_clean = array();
            if ( is_array( $custom_obj ) && ! empty( $custom_obj['features'] ) ) {
                foreach ( $custom_obj['features'] as $feat ) {
                    if ( ! empty( $feat['properties']['name'] ) ) $pool_clean[] = (string) $feat['properties']['name'];
                }
            }
            update_post_meta( $post_id, self::META_FEATURE_POOL, wp_json_encode( $pool_clean, JSON_UNESCAPED_UNICODE ) );
        } else {
            // Bundle source — checkbox pool z $_POST
            $pool_raw = isset( $_POST[ self::META_FEATURE_POOL ] ) && is_array( $_POST[ self::META_FEATURE_POOL ] ) ? wp_unslash( $_POST[ self::META_FEATURE_POOL ] ) : array();
            $pool_clean = array();
            foreach ( $pool_raw as $name ) {
                $name = sanitize_text_field( (string) $name );
                if ( $name !== '' ) $pool_clean[] = $name;
            }
            update_post_meta( $post_id, self::META_FEATURE_POOL, wp_json_encode( $pool_clean, JSON_UNESCAPED_UNICODE ) );
        }

        // Player detail
        $detail = isset( $_POST[ self::META_PLAYER_DETAIL ] ) ? sanitize_key( $_POST[ self::META_PLAYER_DETAIL ] ) : 'outline-only';
        if ( ! array_key_exists( $detail, self::get_player_detail_presets() ) ) $detail = 'outline-only';
        update_post_meta( $post_id, self::META_PLAYER_DETAIL, $detail );

        // Overlay vodítka + tile vrstvy — checkboxes; unchecked sa v $_POST vôbec nezjavia.
        $overlays_raw = isset( $_POST[ self::META_OVERLAYS ] ) && is_array( $_POST[ self::META_OVERLAYS ] ) ? $_POST[ self::META_OVERLAYS ] : array();
        $overlays_clean = array(
            // Tile base layers — ak ≥1 zvolená, hráč dostane MapTiler tile + L.control.layers prepinač.
            'tile_streets'    => ! empty( $overlays_raw['tile_streets'] ),
            'tile_satellite'  => ! empty( $overlays_raw['tile_satellite'] ),
            'tile_outdoor'    => ! empty( $overlays_raw['tile_outdoor'] ),
            // Feature pomôcky (area/line): hover tooltip s názvom. Default OFF — anti-cheat.
            'feature_labels'  => ! empty( $overlays_raw['feature_labels'] ),
            // Vždy viditeľný label priamo na polygone/čiare — silnejšia pomôcka.
            'feature_labels_permanent' => ! empty( $overlays_raw['feature_labels_permanent'] ),
            // Skry rozptyľovače — hráč vidí iba features ktoré práve háda.
            'feature_only_set' => ! empty( $overlays_raw['feature_only_set'] ),
        );
        // Per-región geografické overlays — iterujeme registry pre práve uložený región
        // ($region je sanitizovaná hodnota z $_POST vyššie v tomto handleri),
        // aby admin nemohol uložiť overlay z iného regiónu (čisté postmeta).
        $region_overlays = Eventkviz_MapQuiz_Datasets::overlays_for_region( $region );
        foreach ( array_keys( $region_overlays ) as $slug ) {
            $overlays_clean[ $slug ] = ! empty( $overlays_raw[ $slug ] );
        }
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
