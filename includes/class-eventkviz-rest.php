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
