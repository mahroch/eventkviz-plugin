<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Eventkviz_Rest_Search {

    const NAMESPACE_ROUTE = 'eventkviz/v1';
    const CACHE_TTL       = 3600;

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        register_rest_route(
            self::NAMESPACE_ROUTE,
            '/search',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'search' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'type' => array(
                        'required' => true,
                        'type'     => 'string',
                        'enum'     => array( 'movies', 'songs', 'artists' ),
                    ),
                    'q' => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    'limit' => array(
                        'default' => 15,
                        'type'    => 'integer',
                    ),
                ),
            )
        );

        // /link-token — vygeneruje tokenized URL pre quiz formulár.
        // JS volá pri „Start" tlačidle namiesto skladania plain QS URL.
        register_rest_route(
            self::NAMESPACE_ROUTE,
            '/link-token',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'link_token' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'quiz_slug' => array( 'required' => true, 'type' => 'string' ),
                    'akcia'     => array( 'required' => true, 'type' => 'string' ),
                    'team'      => array( 'default' => '',    'type' => 'string' ),
                    'user'      => array( 'default' => '',    'type' => 'string' ),
                    'mq'        => array( 'default' => '',    'type' => 'string' ),
                ),
            )
        );

        // /export/<typ> — read-only export kvízových dát pre GeoChallenge
        // headless CMS port (Fáza 1). Recyklovateľný handler: zdieľaný auth
        // (X-Eventkviz-Api-Key) + jednotná response obálka pre všetky typy
        // (music/movies/knowledge/sudoku/mapquiz). Per-typ sa pridáva len
        // data builder do self::export_builders() registry.
        register_rest_route(
            self::NAMESPACE_ROUTE,
            '/export/(?P<type>[a-z0-9_-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'export' ),
                'permission_callback' => array( __CLASS__, 'export_auth' ),
                'args'                => array(
                    'type' => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    // Voliteľný production filter (per-typ semantics; aktuálne
                    // používa len 'movies'). Hodnota = slug taxonómie `production`
                    // (sk / cz / zahranicne / rozpravky). Bez parametra → full
                    // export (žiadny filter). Builder ignoruje neznámu hodnotu.
                    'production' => array(
                        'required' => false,
                        'type'     => 'string',
                    ),
                ),
            )
        );
    }

    /**
     * API key option name. Key sa drží ako WP option; vygeneruje sa lazy
     * pri prvom prístupe (alebo cez get_or_create_export_key()).
     */
    const EXPORT_KEY_OPTION = 'eventkviz_export_api_key';

    /**
     * Vráti existujúci export API key, alebo vygeneruje nový (a uloží) ak chýba.
     */
    public static function get_or_create_export_key() {
        $key = get_option( self::EXPORT_KEY_OPTION );
        if ( ! is_string( $key ) || strlen( $key ) < 32 ) {
            $key = wp_generate_password( 48, false, false );
            update_option( self::EXPORT_KEY_OPTION, $key, false );
        }
        return $key;
    }

    /**
     * Auth pre export endpoint — porovná hlavičku X-Eventkviz-Api-Key proti
     * uloženému kľúču (hash_equals, timing-safe). Zlý/chýbajúci key → 401.
     */
    public static function export_auth( WP_REST_Request $req ) {
        $provided = (string) $req->get_header( 'x_eventkviz_api_key' );
        if ( $provided === '' ) {
            // Fallback pre query param (debug/manuálny test); hlavička je preferovaná.
            $provided = (string) $req->get_param( 'api_key' );
        }
        $expected = self::get_or_create_export_key();
        if ( $provided === '' || ! hash_equals( $expected, $provided ) ) {
            return new WP_Error(
                'ek_export_unauthorized',
                'Invalid or missing API key.',
                array( 'status' => 401 )
            );
        }
        return true;
    }

    /**
     * Registry per-typ data builderov. Každý builder vracia pole:
     *   array( 'questions' => [...], 'scoring' => [...], 'lookup_db' => [...] )
     * Pridanie ďalšieho typu = pridať záznam sem + statickú metódu builder.
     */
    private static function export_builders() {
        return array(
            'music'  => array( __CLASS__, 'build_music_export' ),
            'movies' => array( __CLASS__, 'build_movies_export' ),
            'knowledge' => array( __CLASS__, 'build_knowledge_export' ),
            'mapquiz'   => array( __CLASS__, 'build_mapquiz_export' ),
            // 'sudoku'    => array( __CLASS__, 'build_sudoku_export' ),
        );
    }

    /**
     * Shared export handler. Auth už prebehol v permission_callback.
     * Vyrieši typ → zavolá per-typ builder → zabalí do jednotnej obálky.
     */
    public static function export( WP_REST_Request $req ) {
        $type     = sanitize_key( $req->get_param( 'type' ) );
        $builders = self::export_builders();

        if ( ! isset( $builders[ $type ] ) ) {
            return new WP_Error(
                'ek_export_unknown_type',
                sprintf( 'Unknown export type "%s".', $type ),
                array( 'status' => 404 )
            );
        }

        // Voliteľný production filter (aktuálne len movies). Builder dostáva
        // sanitized slug alebo null. Neznámy slug = null (full export, žiadny
        // 400 — back-compat pre staré klientov volajúcich bez param).
        $production_raw = (string) $req->get_param( 'production' );
        $production     = $production_raw !== '' ? sanitize_key( $production_raw ) : null;

        $data = call_user_func( $builders[ $type ], $production );

        // Jednotná obálka zdieľaná všetkými typmi. partial_subset je vyplnené
        // len keď builder reálne aplikoval filter (GC ho ukladá do
        // ek_quiz_library.partial_subset pre indikáciu subset stavu).
        $envelope = array(
            'quiz_type'      => $type,
            'generated_at'   => gmdate( 'c' ),
            'questions'      => isset( $data['questions'] ) ? $data['questions'] : array(),
            'scoring'        => isset( $data['scoring'] ) ? $data['scoring'] : new stdClass(),
            'lookup_db'      => isset( $data['lookup_db'] ) ? $data['lookup_db'] : new stdClass(),
            'partial_subset' => isset( $data['partial_subset'] ) ? $data['partial_subset'] : null,
        );

        return rest_ensure_response( $envelope );
    }

    /**
     * Music quiz data builder.
     *  - questions: celý pool CPT questions-audio (audio URL + correct artist/song + production tag)
     *  - scoring:   default bodové hodnoty z music scoring configu
     *  - lookup_db: celý obsah CCT artists + songs (pre GC autocomplete)
     */
    public static function build_music_export() {
        $questions = self::music_questions();
        $scoring   = self::music_scoring_defaults();
        $lookup_db = array(
            'artists'     => self::cct_lookup( 'jet_cct_artists', 'artist' ),
            'songs'       => self::cct_lookup( 'jet_cct_songs', 'song' ),
            // Fáza 3: dostupné kategórie (taxonómia `production`) pre GC admin
            // multi-select. Dynamické — vychádza z reálnych termov, nie z hardcoded
            // enumu. Maroš pridá SK/CZ/Zahraničné termy neskôr → automaticky sa
            // objavia bez zmeny kódu.
            'productions' => self::taxonomy_terms( 'production' ),
        );

        return array(
            'questions' => $questions,
            'scoring'   => $scoring,
            'lookup_db' => $lookup_db,
        );
    }

    /**
     * Default music scoring. Hodnoty zhodné s render_music_tab() defaultmi
     * (admin/class-eventkviz-admin.php). Secondary = „na nesprávnej pozícii"
     * (corr_*_in_array) — default 0, nie sú vystavené v admin UI.
     */
    private static function music_scoring_defaults() {
        return array(
            'both_correct'     => 100, // corr_art_corr_pos_corr_song_corr_pos
            'artist_only'      => 50,  // corr_art_corr_pos_incorr_song
            'song_only'        => 50,  // incorr_art_corr_song_corr_pos
            'secondary_artist' => 0,   // corr_art_in_array (správny interpret, zlá pozícia)
            'secondary_song'   => 0,   // corr_song_in_array (správna pieseň, zlá pozícia)
        );
    }

    /**
     * Všetky questions-audio CPT (celý pool) s audio URL + správnymi
     * odpoveďami cez JetEngine relations (14=song, 15=artist).
     */
    private static function music_questions() {
        $posts = get_posts( array(
            'post_type'   => 'questions-audio',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );

        $out = array();
        foreach ( $posts as $post ) {
            $qid       = (int) $post->ID;
            $media_id  = get_post_meta( $qid, 'media', true );
            $audio_url = $media_id ? wp_get_attachment_url( $media_id ) : '';

            $artist_id = self::music_related_id( $qid, 15 );
            $song_id   = self::music_related_id( $qid, 14 );

            $out[] = array(
                'id'             => $qid,
                'audio_url'      => $audio_url ? $audio_url : null,
                'correct_artist' => self::lookup_entry( 'jet_cct_artists', 'artist', $artist_id ),
                'correct_song'   => self::lookup_entry( 'jet_cct_songs', 'song', $song_id ),
                'production'     => self::music_production( $qid ),
            );
        }
        return $out;
    }

    /**
     * Movies quiz data builder.
     *  - questions: celý pool CPT questions-movies (video URL + correct_movie
     *               cez JetEngine reláciu 17 + choices/correct_choice + production tag)
     *  - scoring:   default bodová hodnota za správny film
     *  - lookup_db: celý obsah CCT movies (original_title) pre GC autocomplete (full režim)
     *
     * Export vždy obsahuje OBA tvary (full aj choices), aby GC vedel renderovať
     * ľubovoľný režim podľa svojej admin konfigurácie — verná parita s EK
     * movies_quiz_type (full / choices).
     */
    public static function build_movies_export( $production = null ) {
        // Validuj filter — len známe slugy z taxonómie. Neznáme → ignoruje
        // (full export). Pole platných slugov sa berie zo živej taxonómie,
        // takže keď Maroš pridá ďalší term (napr. „dokument"), automaticky
        // sa stane validným filtrom bez zmeny kódu.
        $valid_slugs = array();
        foreach ( self::taxonomy_terms( 'production' ) as $term ) {
            if ( isset( $term['slug'] ) ) {
                $valid_slugs[] = (string) $term['slug'];
            }
        }
        $filter = ( is_string( $production ) && $production !== '' && in_array( $production, $valid_slugs, true ) )
            ? $production
            : null;

        $questions = self::movies_questions( $filter );
        $scoring   = self::movies_scoring_defaults();

        // Pri selective sync zúž `lookup_db.movies` na CCT záznamy odkazované
        // ako correct_movie z filtrovaných otázok — autocomplete v GC potom
        // ponúka len relevantné filmy (a šetrí prenos / DB veľkosť).
        if ( $filter !== null ) {
            $allowed_ids = array();
            foreach ( $questions as $q ) {
                if ( isset( $q['correct_movie']['id'] ) ) {
                    $allowed_ids[ (int) $q['correct_movie']['id'] ] = true;
                }
            }
            $full_movies = self::cct_lookup( 'jet_cct_movies', 'original_title' );
            $movies_lookup = array();
            foreach ( $full_movies as $entry ) {
                if ( isset( $entry['id'] ) && isset( $allowed_ids[ (int) $entry['id'] ] ) ) {
                    $movies_lookup[] = $entry;
                }
            }
        } else {
            $movies_lookup = self::cct_lookup( 'jet_cct_movies', 'original_title' );
        }

        $lookup_db = array(
            'movies'      => $movies_lookup,
            // Productions zostáva PLNÁ lista (informačná — GC admin vidí všetky
            // kategórie aj keď syncoval len subset, vie potom samostatne syncnúť
            // ďalšie).
            'productions' => self::taxonomy_terms( 'production' ),
        );

        return array(
            'questions'      => $questions,
            'scoring'        => $scoring,
            'lookup_db'      => $lookup_db,
            // partial_subset = aplikovaný slug alebo null (full). GC ho ukladá
            // do ek_quiz_library.partial_subset pre UI indikáciu stavu.
            'partial_subset' => $filter,
        );
    }

    /**
     * Default movies scoring. Hodnota zhodná s render_movies_tab() defaultom
     * (admin/class-eventkviz-admin.php: event_movies_credits_corr_movie = 100).
     */
    private static function movies_scoring_defaults() {
        return array(
            'movie_correct' => 100, // corr_movie — správne určený film
        );
    }

    /**
     * Všetky questions-movies CPT (celý pool) s video URL + správnymi odpoveďami
     * v oboch tvaroch:
     *  - correct_movie: { id, name } z CCT jet_cct_movies (original_title) cez
     *    JetEngine reláciu 17 (parent = film, child = otázka) — full režim.
     *  - choices + correct_choice: meta choices_for_answer (newline-split) +
     *    correct_answer_for_choices (string) — choices režim.
     */
    private static function movies_questions( $production_filter = null ) {
        $args = array(
            'post_type'   => 'questions-movies',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        );
        // Selective sync: obmedzí query na otázky priradené k danému production
        // termu (slug). Šetrí egress aj DB záťaž — žiadne post_meta walking
        // pre vyfiltrované otázky, robí to MySQL cez tax_query JOIN.
        if ( is_string( $production_filter ) && $production_filter !== '' ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'production',
                    'field'    => 'slug',
                    'terms'    => array( $production_filter ),
                ),
            );
        }
        $posts = get_posts( $args );

        $out = array();
        foreach ( $posts as $post ) {
            $qid       = (int) $post->ID;
            $media_id  = get_post_meta( $qid, 'media', true );
            $video_url = $media_id ? wp_get_attachment_url( $media_id ) : '';

            // full: film cez reláciu 17 (rovnaké get_parents ako music_related_id)
            $movie_id = self::music_related_id( $qid, 17 );

            // choices: zoznam možností (newline-split, ako print_form_question)
            // a správna možnosť (presný string match v evaluate_movie).
            $choices_raw   = (string) get_post_meta( $qid, 'choices_for_answer', true );
            $choices       = self::split_choices( $choices_raw );
            $correct_choice = get_post_meta( $qid, 'correct_answer_for_choices', true );

            $out[] = array(
                'id'             => $qid,
                'video_url'      => $video_url ? $video_url : null,
                'correct_movie'  => self::lookup_entry( 'jet_cct_movies', 'original_title', $movie_id ),
                'choices'        => $choices,
                'correct_choice' => ( $correct_choice === '' ? null : (string) $correct_choice ),
                'production'     => self::movies_production( $qid ),
            );
        }
        return $out;
    }

    /**
     * Rozdelí choices_for_answer (textarea, jedna možnosť na riadok) na pole
     * orezaných reťazcov. Mirror logiky print_form_question() v moviesquiz —
     * apostrofy sa neodstraňujú (GC renderuje plný label), prázdne riadky out.
     */
    private static function split_choices( $raw ) {
        $raw = trim( (string) $raw );
        if ( $raw === '' ) {
            return array();
        }
        $parts = preg_split( '/\R/', $raw );
        $out   = array();
        foreach ( $parts as $p ) {
            $p = trim( $p );
            if ( $p !== '' ) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * Production tag otázky questions-movies (taxonómia `production`).
     * Slug prvého priradeného termu (skcz / zahranicne / rozpravky) alebo null.
     */
    private static function movies_production( $question_id ) {
        $slugs = wp_get_post_terms( (int) $question_id, 'production', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $slugs ) || empty( $slugs ) ) {
            return null;
        }
        return (string) $slugs[0];
    }

    /**
     * Knowledge quiz data builder.
     *  - questions: celý pool CPT questions-knowledge (prompt = title,
     *               prompt_html = content, image_url = featured image, hint,
     *               choices / correct_variants / explanation / topic)
     *  - scoring:   { answer_correct: 100 } — body za správnu odpoveď
     *  - lookup_db: prázdny objekt — knowledge nemá autocomplete lookup
     *
     * Pozn.: correct_variants (server-side správne odpovede) a explanation sa
     * exportujú do GC library (server-only) — GC ich NIKDY neposiela cez
     * /questions, len cez /score reveal. choices sú verejné možnosti (select).
     */
    /**
     * Mapquiz data builder (Fáza 1 — pinové šablóny).
     *
     * - questions: array `mapquiz_template` post-ov (id, name, region, player_detail,
     *              max_points, score_tiers, pins[]). 1 question = 1 šablóna (NIE
     *              jedna otázka v sete — to si vyberie GC pri play time z `pins`).
     * - scoring:   prázdne (per-template tiers sú v každom question objekte)
     * - lookup_db.region_geojsons: GeoJSON FeatureCollection per region (slovakia,
     *              czechia, europe-countries, world-rivers) — bundled v EK plugin
     *              `public/data/regions/`. GC sync ich uloží do mapquiz_library
     *              ako blob/jsonb a renderer ich loadne pri player view.
     *
     * Photo URL per pin sa resolve-uje z `photo_attachment_id` cez `wp_get_attachment_url`
     * (plne kvalifikovaný URL). GC sync ich pri import downloadne a uloží do
     * Supabase Storage `task-images` bucket → URL sa prepíše pred uloženim do
     * mapquiz_library. Tým je GC offline-robust voči EK downtime.
     */
    public static function build_mapquiz_export() {
        $posts = get_posts( array(
            'post_type'   => 'mapquiz_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );

        $templates    = array();
        $needed_regions = array();
        $needed_overlays = array(); // [region][overlay_key] => true
        foreach ( $posts as $post ) {
            $tid    = (int) $post->ID;
            $quiz_type = (string) get_post_meta( $tid, '_mapquiz_quiz_type', true );
            $quiz_type = $quiz_type !== '' ? $quiz_type : 'pin';
            // Fáza 1c: export VŠETKY typy (pin + area + line). GC strana filter na pin
            // pre Fáza 1 player UI, area/line ostanú v mapquiz_library pre Fáza 3.

            $region = (string) get_post_meta( $tid, '_mapquiz_region', true );
            $region = $region !== '' ? $region : 'slovakia';
            $needed_regions[ $region ] = true;

            $detail        = (string) get_post_meta( $tid, '_mapquiz_player_detail', true );
            $detail        = $detail !== '' ? $detail : 'outline-only';
            $max_points    = (int) get_post_meta( $tid, '_mapquiz_max_points', true );
            $max_points    = $max_points > 0 ? $max_points : 100;

            $tiers_raw = get_post_meta( $tid, '_mapquiz_score_tiers', true );
            $tiers     = is_array( $tiers_raw ) ? $tiers_raw : json_decode( (string) $tiers_raw, true );
            if ( ! is_array( $tiers ) || empty( $tiers ) ) {
                $tiers = array(
                    array( 'maxKm' => 5,  'percent' => 100 ),
                    array( 'maxKm' => 10, 'percent' => 75 ),
                    array( 'maxKm' => 20, 'percent' => 50 ),
                    array( 'maxKm' => 40, 'percent' => 25 ),
                );
            }

            $pins_raw = get_post_meta( $tid, '_mapquiz_pins', true );
            $pins_in  = is_array( $pins_raw ) ? $pins_raw : json_decode( (string) $pins_raw, true );
            $pins     = array();
            if ( is_array( $pins_in ) ) {
                foreach ( $pins_in as $pin ) {
                    if ( ! is_array( $pin ) ) continue;
                    $pid = isset( $pin['id'] ) ? (string) $pin['id'] : '';
                    if ( $pid === '' ) continue;
                    $photo_id  = isset( $pin['photo_attachment_id'] ) ? (int) $pin['photo_attachment_id'] : 0;
                    $photo_url = $photo_id > 0 ? wp_get_attachment_url( $photo_id ) : null;
                    $pins[] = array(
                        'id'          => $pid,
                        'name'        => isset( $pin['name'] ) ? (string) $pin['name'] : '',
                        'hint'        => isset( $pin['hint'] ) ? (string) $pin['hint'] : '',
                        'description' => isset( $pin['description'] ) ? (string) $pin['description'] : '',
                        'photo_url'   => ( $photo_url && $photo_url !== '' ? $photo_url : null ),
                        'lat'         => isset( $pin['lat'] ) ? (float) $pin['lat'] : 0,
                        'lon'         => isset( $pin['lon'] ) ? (float) $pin['lon'] : 0,
                    );
                }
            }

            // Fáza 1c: overlays config + dataset slug + feature pool.
            $overlays_raw = get_post_meta( $tid, '_mapquiz_overlays', true );
            $overlays = is_array( $overlays_raw ) ? $overlays_raw : json_decode( (string) $overlays_raw, true );
            $overlays = is_array( $overlays ) ? $overlays : array();
            // Track ktoré overlay GeoJSON files treba bundle-nuť (per region).
            foreach ( $overlays as $okey => $oval ) {
                if ( $oval && ! isset( $needed_overlays[ $region ][ $okey ] ) ) {
                    $needed_overlays[ $region ][ $okey ] = true;
                }
            }

            $dataset_slug = (string) get_post_meta( $tid, '_mapquiz_dataset_slug', true );
            $feature_pool_raw = get_post_meta( $tid, '_mapquiz_feature_pool', true );
            $feature_pool = is_array( $feature_pool_raw ) ? $feature_pool_raw : json_decode( (string) $feature_pool_raw, true );
            $feature_pool = is_array( $feature_pool ) ? $feature_pool : array();
            $features_source = (string) get_post_meta( $tid, '_mapquiz_features_source', true );
            $features_source = $features_source !== '' ? $features_source : 'bundle';
            $custom_features_raw = get_post_meta( $tid, '_mapquiz_custom_features', true );
            $custom_features = is_array( $custom_features_raw ) ? $custom_features_raw : json_decode( (string) $custom_features_raw, true );

            $templates[] = array(
                'id'              => $tid,
                'name'            => get_the_title( $tid ),
                'quiz_type'       => $quiz_type,
                'region'          => $region,
                'player_detail'   => $detail,
                'max_points'      => $max_points,
                'score_tiers'     => array_values( $tiers ),
                'pins'            => $pins,
                'overlays'        => (object) $overlays,
                'dataset_slug'    => $dataset_slug,
                'features_source' => $features_source,
                'feature_pool'    => array_values( $feature_pool ),
                'custom_features' => ( is_array( $custom_features ) ? $custom_features : null ),
                'modified_at'     => mysql2date( 'c', $post->post_modified_gmt, false ),
            );
        }

        $regions_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'public/data/regions/';

        // Region outline GeoJSON (slovakia.geojson / czechia.geojson — pre europe/world fallback).
        $region_geojsons = array();
        foreach ( array_keys( $needed_regions ) as $region ) {
            $candidates = array( $region . '.geojson', $region . '-countries.geojson' );
            foreach ( $candidates as $fname ) {
                $path = $regions_dir . $fname;
                if ( file_exists( $path ) && is_readable( $path ) ) {
                    $contents = file_get_contents( $path );
                    if ( $contents !== false ) {
                        $decoded = json_decode( $contents, true );
                        if ( is_array( $decoded ) ) {
                            $region_geojsons[ $region ] = $decoded;
                            break;
                        }
                    }
                }
            }
        }

        // Overlay GeoJSON files (sk-cities, sk-regions, sk-rivers, europe-capitals, atď.)
        // bundle per región podľa registry.
        //
        // 2026-05-31: vždy exportujeme VŠETKY dostupné overlay files per
        // potrebný región, NIE LEN tie ktoré sú template-default enabled. GC
        // admin má per-task override toggle — ak template má default rivers=false
        // ale admin v GC zapne, klient potrebuje sk-rivers.geojson dáta k
        // dispozícii. Predtým sa exportovali len defaultne enabled → admin
        // override v GC nezobrazoval prvky (Maros, „rieky sa nezobrazujú").
        $overlay_geojsons = array();
        $overlay_registry = array(
            'slovakia' => array(
                'cities_main'     => 'sk-cities.geojson',
                'cities_regional' => 'sk-cities.geojson',
                'regions'         => 'sk-regions.geojson',
                'rivers'          => 'sk-rivers.geojson',
            ),
            'europe' => array(
                'eu_capitals'     => 'europe-capitals.geojson',
                'eu_borders'      => 'europe-countries.geojson',
                'eu_major_rivers' => 'europe-rivers.geojson',
            ),
        );
        foreach ( array_keys( $needed_regions ) as $region ) {
            if ( ! isset( $overlay_registry[ $region ] ) ) continue;
            $overlay_geojsons[ $region ] = array();
            $files_seen = array();
            foreach ( $overlay_registry[ $region ] as $fname ) {
                if ( isset( $files_seen[ $fname ] ) ) continue;
                $files_seen[ $fname ] = true;
                $path = $regions_dir . $fname;
                if ( file_exists( $path ) && is_readable( $path ) ) {
                    $contents = file_get_contents( $path );
                    if ( $contents !== false ) {
                        $decoded = json_decode( $contents, true );
                        if ( is_array( $decoded ) ) {
                            $overlay_geojsons[ $region ][ $fname ] = $decoded;
                        }
                    }
                }
            }
        }

        // MapTiler API key (z plugin settings) — voliteľný; GC ho použije iba ak je
        // overlays.tile_streets / tile_satellite / tile_outdoor = true.
        $maptiler_key = '';
        if ( class_exists( 'Eventkviz_Settings' ) && method_exists( 'Eventkviz_Settings', 'get_maptiler_key' ) ) {
            $maptiler_key = (string) Eventkviz_Settings::get_maptiler_key();
        }

        return array(
            'questions' => $templates,
            'scoring'   => new stdClass(),
            'lookup_db' => array(
                'region_geojsons'  => $region_geojsons,
                'overlay_geojsons' => $overlay_geojsons,
                'maptiler_key'     => $maptiler_key,
            ),
        );
    }

    public static function build_knowledge_export() {
        $questions = self::knowledge_questions();
        $scoring   = self::knowledge_scoring_defaults();
        // Knowledge nemá autocomplete lookup, ale Fáza 3 dodá dostupné kategórie
        // (taxonómia `topic`) pre GC admin multi-select. Dynamické — z reálnych
        // termov, nie hardcoded enum.
        $lookup_db = array(
            'topics' => self::taxonomy_terms( 'topic' ),
        );

        return array(
            'questions' => $questions,
            'scoring'   => $scoring,
            'lookup_db' => $lookup_db,
        );
    }

    /**
     * Default knowledge scoring. Hodnota zhodná s knowledge credits['corr_answer']
     * default (admin/class-eventkviz-admin.php render_knowledge_tab) = 100.
     */
    private static function knowledge_scoring_defaults() {
        return array(
            'answer_correct' => 100, // corr_answer — správna odpoveď
        );
    }

    /**
     * Všetky questions-knowledge CPT (celý pool, status publish) s prompt,
     * obrázkom, hintom a správnymi odpoveďami:
     *  - prompt:           post title (zobrazí GC ako otázku)
     *  - prompt_html:      post content (HTML — GC sanitizuje pred renderom)
     *  - image_url:        featured image URL (po syncu prepísané na GC storage)
     *  - hint:             meta `hint` (voliteľná nápoveda)
     *  - choices:          meta `choices-for-correct-answer` split na ';' (fallback
     *                      ',') — ako print_form_question v knowledgequiz triede;
     *                      null ak prázdne (= voľný text input v GC)
     *  - correct_variants: zlúčené pipe-splity meta `correct-answer-1` +
     *                      `correct-answer-2` (server-side správne odpovede)
     *  - explanation:      meta `explanation-of-correct-answer` (reveal)
     *  - topic:            slug prvého termu taxonómie `topic`
     *
     * Meta kľúče overené v Eventkviz_KnowledgeEval_Quiz_Class::
     * get_correct_knowledge_answers() / show_knowledge_answer() /
     * print_form_question().
     */
    private static function knowledge_questions() {
        $posts = get_posts( array(
            'post_type'   => 'questions-knowledge',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
        ) );

        $out = array();
        foreach ( $posts as $post ) {
            $qid       = (int) $post->ID;
            $image_url = get_the_post_thumbnail_url( $qid, 'large' );

            // choices: ';' najprv, ',' fallback (mirror print_form_question).
            $choices_raw = (string) get_post_meta( $qid, 'choices-for-correct-answer', true );
            $choices     = self::split_knowledge_choices( $choices_raw );

            // correct_variants: pipe-split correct-answer-1 + correct-answer-2,
            // zlúčené (mirror get_correct_knowledge_answers).
            $variants = array_merge(
                self::split_answer_variants( get_post_meta( $qid, 'correct-answer-1', true ) ),
                self::split_answer_variants( get_post_meta( $qid, 'correct-answer-2', true ) )
            );

            $explanation = get_post_meta( $qid, 'explanation-of-correct-answer', true );
            $hint        = get_post_meta( $qid, 'hint', true );

            $out[] = array(
                'id'               => $qid,
                'prompt'           => get_the_title( $qid ),
                'prompt_html'      => get_post_field( 'post_content', $qid ),
                'image_url'        => $image_url ? $image_url : null,
                'hint'             => ( $hint === '' ? null : (string) $hint ),
                'choices'          => ( empty( $choices ) ? null : $choices ),
                'correct_variants' => array_values( $variants ),
                'explanation'      => ( $explanation === '' ? null : (string) $explanation ),
                'topic'            => self::knowledge_topic( $qid ),
            );
        }
        return $out;
    }

    /**
     * Rozdelí choices-for-correct-answer na pole orezaných možností. Mirror
     * print_form_question() v knowledgequiz: split na ';', ak vyjde 1 prvok →
     * fallback split na ','. Prázdne prvky vyhodené.
     */
    private static function split_knowledge_choices( $raw ) {
        $raw = trim( (string) $raw );
        if ( $raw === '' ) {
            return array();
        }
        $parts = explode( ';', $raw );
        if ( count( $parts ) === 1 ) {
            $parts = explode( ',', $raw );
        }
        $out = array();
        foreach ( $parts as $p ) {
            $p = trim( $p );
            if ( $p !== '' ) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * Pipe-split jedného meta poľa na synonymá (Bratislava|BA|hlavné mesto).
     * Mirror Eventkviz_KnowledgeEval_Quiz_Class::split_answer_variants().
     */
    private static function split_answer_variants( $s ) {
        $s = (string) $s;
        if ( $s === '' ) {
            return array();
        }
        $parts = array_map( 'trim', explode( '|', $s ) );
        return array_values( array_filter( $parts, 'strlen' ) );
    }

    /**
     * Topic tag otázky questions-knowledge (taxonómia `topic`). Slug prvého
     * priradeného termu, alebo null.
     */
    private static function knowledge_topic( $question_id ) {
        $slugs = wp_get_post_terms( (int) $question_id, 'topic', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $slugs ) || empty( $slugs ) ) {
            return null;
        }
        return (string) $slugs[0];
    }

    /**
     * Fáza 3: zoznam dostupných termov taxonómie (`production` / `topic`) pre
     * GC admin multi-select. Vracia pole { slug, name } všetkých termov vrátane
     * tých bez priradených príspevkov (hide_empty=false), zoradené podľa názvu.
     * DYNAMICKÉ — GC admin nemá žiadny hardcoded enum kategórií. Ak taxonómia
     * neexistuje alebo nemá termy → prázdne pole (graceful).
     */
    private static function taxonomy_terms( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }
        $out = array();
        foreach ( $terms as $term ) {
            $out[] = array(
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
            );
        }
        return $out;
    }

    /**
     * Production tag otázky z taxonómie `production` na CPT questions-audio.
     * Zdroj pravdy = rovnaká taxonómia, podľa ktorej music quiz filtruje pool
     * (Eventkviz_MusicForm_Quiz_Class::eventkviz_music_form, tax_query 'production').
     * Vracia slug prvého priradeného termu (napr. "skcz" / "zahranicne" /
     * "rozpravky"), alebo null ak otázka nemá priradenú produkciu.
     */
    private static function music_production( $question_id ) {
        $slugs = wp_get_post_terms( (int) $question_id, 'production', array( 'fields' => 'slugs' ) );
        if ( is_wp_error( $slugs ) || empty( $slugs ) ) {
            return null;
        }
        return (string) $slugs[0];
    }

    /**
     * Vráti prvé related CCT id pre danú JetEngine reláciu (parents), alebo 0.
     * Read-only mirror Eventkviz_Quiz_Class::get_related_ids() — vlastná kópia
     * aby export nezávisel na inštancii quiz triedy.
     */
    private static function music_related_id( $question_id, $rel_id ) {
        if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->relations ) {
            return 0;
        }
        $relation = jet_engine()->relations->get_active_relations( $rel_id );
        if ( ! $relation ) {
            return 0;
        }
        $ids = $relation->get_parents( $question_id, 'ids' );
        return ( is_array( $ids ) && ! empty( $ids ) ) ? (int) $ids[0] : 0;
    }

    /**
     * Vráti { id, name } pre jeden CCT záznam, alebo null ak id chýba/neexistuje.
     */
    private static function lookup_entry( $table, $col, $id ) {
        $id = (int) $id;
        if ( $id <= 0 ) {
            return null;
        }
        global $wpdb;
        $tbl  = $wpdb->prefix . sanitize_key( $table );
        $col  = sanitize_key( $col );
        $name = $wpdb->get_var( $wpdb->prepare( "SELECT {$col} FROM {$tbl} WHERE _ID = %d", $id ) );
        if ( $name === null ) {
            return null;
        }
        return array( 'id' => $id, 'name' => (string) $name );
    }

    /**
     * Celý obsah CCT tabuľky ako pole { id, name } (pre GC autocomplete).
     */
    private static function cct_lookup( $table, $col ) {
        global $wpdb;
        $tbl  = $wpdb->prefix . sanitize_key( $table );
        $col  = sanitize_key( $col );
        $rows = $wpdb->get_results( "SELECT _ID, {$col} AS name FROM {$tbl} ORDER BY _ID ASC", ARRAY_A );

        $out = array();
        foreach ( $rows as $r ) {
            if ( ! isset( $r['name'] ) || $r['name'] === '' ) {
                continue;
            }
            $out[] = array( 'id' => (int) $r['_ID'], 'name' => (string) $r['name'] );
        }
        return $out;
    }

    public static function link_token( WP_REST_Request $req ) {
        if ( ! class_exists( 'Eventkviz_Link_Token' ) ) {
            return new WP_Error( 'no_helper', 'Link token helper missing', array( 'status' => 500 ) );
        }
        $slug = sanitize_title( (string) $req->get_param( 'quiz_slug' ) );
        if ( $slug === '' ) {
            return new WP_Error( 'bad_slug', 'quiz_slug required', array( 'status' => 400 ) );
        }
        $params = array(
            'akcia' => sanitize_text_field( (string) $req->get_param( 'akcia' ) ),
            'team'  => sanitize_text_field( (string) $req->get_param( 'team' ) ),
            'user'  => sanitize_text_field( (string) $req->get_param( 'user' ) ),
            'mq'    => sanitize_text_field( (string) $req->get_param( 'mq' ) ),
        );
        $base = untrailingslashit( home_url() ) . '/' . $slug . '/';
        $url  = Eventkviz_Link_Token::build_url( $base, $params );
        return rest_ensure_response( array( 'url' => $url ) );
    }

    public static function search( WP_REST_Request $req ) {
        $type  = $req->get_param( 'type' );
        $q     = self::normalize( $req->get_param( 'q' ) );
        $limit = max( 1, min( 50, (int) $req->get_param( 'limit' ) ) );

        if ( $q === '' ) {
            return array();
        }

        $index = self::get_dataset( $type );
        return self::rank_matches( $q, $index, $limit );
    }

    private static function rank_matches( $q, $index, $limit ) {
        $q_len     = strlen( $q );
        $has_space = strpos( $q, ' ' ) !== false;
        // Levenshtein scales O(m*n); skip for very short or very long queries
        $do_fuzzy  = $q_len >= 3 && $q_len <= 60;
        // Allow up to ~33% character distance — catches single-letter typos in 3+ char words
        $threshold = 0.34;

        $matches = array();
        foreach ( $index as $row ) {
            $n = $row['n'];
            if ( $n === $q ) {
                $rank = 0.0;
            } elseif ( strncmp( $n, $q, $q_len ) === 0 ) {
                $rank = 1.0;
            } elseif ( strpos( $n, $q ) !== false ) {
                $rank = 2.0;
            } elseif ( $do_fuzzy ) {
                $rel = self::fuzzy_distance( $q, $n, $has_space );
                if ( $rel === null || $rel >= $threshold ) {
                    continue;
                }
                $rank = 10.0 + $rel;
            } else {
                continue;
            }
            $matches[] = array(
                'rank'  => $rank,
                'id'    => (int) $row['id'],
                'label' => $row['l'],
            );
        }

        usort( $matches, function ( $a, $b ) {
            if ( $a['rank'] === $b['rank'] ) {
                return strcmp( $a['label'], $b['label'] );
            }
            return $a['rank'] < $b['rank'] ? -1 : 1;
        } );

        $matches = array_slice( $matches, 0, $limit );
        return array_map( function ( $m ) {
            return array( 'id' => $m['id'], 'label' => $m['label'] );
        }, $matches );
    }

    private static function fuzzy_distance( $q, $n, $q_has_space ) {
        $q_len = strlen( $q );
        $best  = null;

        // Whole-string distance (best for multi-word labels with multi-word query, or short labels)
        $n_len = strlen( $n );
        if ( $n_len <= 80 ) {
            $d   = levenshtein( $q, $n );
            $rel = $d / max( $q_len, $n_len );
            if ( $best === null || $rel < $best ) {
                $best = $rel;
            }
        }

        // Per-token distance (catches single-word typo in long labels)
        if ( ! $q_has_space ) {
            foreach ( preg_split( '/\s+/', $n ) as $tok ) {
                if ( strlen( $tok ) < 2 ) {
                    continue;
                }
                $d   = levenshtein( $q, $tok );
                $rel = $d / max( $q_len, strlen( $tok ) );
                if ( $best === null || $rel < $best ) {
                    $best = $rel;
                }
            }
        }

        return $best;
    }

    public static function get_dataset( $type ) {
        $key    = 'ek_idx_' . $type;
        $cached = get_transient( $key );
        if ( $cached !== false ) {
            return $cached;
        }

        global $wpdb;
        $config = array(
            'movies'  => array( 'table' => 'jet_cct_movies',  'col' => 'original_title' ),
            'songs'   => array( 'table' => 'jet_cct_songs',   'col' => 'song' ),
            'artists' => array( 'table' => 'jet_cct_artists', 'col' => 'artist' ),
        );
        if ( ! isset( $config[ $type ] ) ) {
            return array();
        }

        $cfg   = $config[ $type ];
        $table = $wpdb->prefix . $cfg['table'];
        $col   = sanitize_key( $cfg['col'] );
        $rows  = $wpdb->get_results( "SELECT _ID, {$col} AS lbl FROM {$table}", ARRAY_A );

        $index = array();
        foreach ( $rows as $r ) {
            if ( empty( $r['lbl'] ) ) {
                continue;
            }
            $index[] = array(
                'id' => $r['_ID'],
                'l'  => $r['lbl'],
                'n'  => self::normalize( $r['lbl'] ),
            );
        }

        set_transient( $key, $index, self::CACHE_TTL );
        return $index;
    }

    public static function normalize( $s ) {
        $s = trim( (string) $s );
        if ( $s === '' ) {
            return '';
        }
        $s   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
        $map = array(
            'á' => 'a', 'ä' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'ě' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ô' => 'o', 'ò' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ů' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'č' => 'c', 'ç' => 'c', 'ć' => 'c',
            'ď' => 'd',
            'ľ' => 'l', 'ĺ' => 'l', 'ł' => 'l',
            'ň' => 'n', 'ñ' => 'n',
            'ŕ' => 'r',
            'š' => 's', 'ś' => 's',
            'ť' => 't',
            'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
        );
        return strtr( $s, $map );
    }

    public static function find_id_by_exact_name( $type, $text ) {
        $needle = self::normalize( $text );
        if ( $needle === '' ) {
            return 0;
        }
        $index = self::get_dataset( $type );
        foreach ( $index as $row ) {
            if ( $row['n'] === $needle ) {
                return (int) $row['id'];
            }
        }
        return 0;
    }

    public static function flush_cache() {
        delete_transient( 'ek_idx_movies' );
        delete_transient( 'ek_idx_songs' );
        delete_transient( 'ek_idx_artists' );
    }
}
