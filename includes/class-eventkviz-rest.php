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

        $index   = self::get_dataset( $type );
        $matches = array();
        foreach ( $index as $row ) {
            if ( strpos( $row['n'], $q ) !== false ) {
                $matches[] = array(
                    'id'    => (int) $row['id'],
                    'label' => $row['l'],
                );
                if ( count( $matches ) >= $limit ) {
                    break;
                }
            }
        }
        return $matches;
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
