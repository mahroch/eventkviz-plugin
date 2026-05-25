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
            'music' => array( __CLASS__, 'build_music_export' ),
            // 'movies'    => array( __CLASS__, 'build_movies_export' ),
            // 'knowledge' => array( __CLASS__, 'build_knowledge_export' ),
            // 'sudoku'    => array( __CLASS__, 'build_sudoku_export' ),
            // 'mapquiz'   => array( __CLASS__, 'build_mapquiz_export' ),
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

        $data = call_user_func( $builders[ $type ] );

        // Jednotná obálka zdieľaná všetkými typmi.
        $envelope = array(
            'quiz_type'    => $type,
            'generated_at' => gmdate( 'c' ),
            'questions'    => isset( $data['questions'] ) ? $data['questions'] : array(),
            'scoring'      => isset( $data['scoring'] ) ? $data['scoring'] : new stdClass(),
            'lookup_db'    => isset( $data['lookup_db'] ) ? $data['lookup_db'] : new stdClass(),
        );

        return rest_ensure_response( $envelope );
    }

    /**
     * Music quiz data builder.
     *  - questions: celý pool CPT questions-audio (audio URL + correct artist/song)
     *  - scoring:   default bodové hodnoty z music scoring configu
     *  - lookup_db: celý obsah CCT artists + songs (pre GC autocomplete)
     */
    public static function build_music_export() {
        $questions = self::music_questions();
        $scoring   = self::music_scoring_defaults();
        $lookup_db = array(
            'artists' => self::cct_lookup( 'jet_cct_artists', 'artist' ),
            'songs'   => self::cct_lookup( 'jet_cct_songs', 'song' ),
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
            );
        }
        return $out;
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
