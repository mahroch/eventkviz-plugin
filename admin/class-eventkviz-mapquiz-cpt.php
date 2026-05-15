<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Registers the `mapquiz_template` Custom Post Type — global reusable map quiz
 * templates (e.g. "Hrady SR", "Elektrárne EU"). Each template is a record;
 * pins are stored as JSON in postmeta (see _mapquiz_pins).
 *
 * Phase 1 only registers the CPT. The custom map-editor admin UI for placing
 * pins comes in Phase 2.
 */
class Eventkviz_MapQuiz_CPT {

    const POST_TYPE = 'mapquiz_template';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        $labels = array(
            'name'                  => 'Mapové kvízy',
            'singular_name'         => 'Mapový kvíz',
            'menu_name'             => '🗺️ Mapové kvízy',
            'name_admin_bar'        => 'Mapový kvíz',
            'add_new'               => 'Pridať mapový kvíz',
            'add_new_item'          => 'Pridať nový mapový kvíz',
            'new_item'              => 'Nový mapový kvíz',
            'edit_item'             => 'Uprav mapový kvíz',
            'view_item'             => 'Zobraz mapový kvíz',
            'all_items'             => 'Všetky mapové kvízy',
            'search_items'          => 'Hľadať mapové kvízy',
            'not_found'             => 'Žiadne mapové kvízy nenájdené.',
            'not_found_in_trash'    => 'V koši nie sú žiadne mapové kvízy.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            // String value = parent menu slug → registers as submenu under existing EventKviz menu
            'show_in_menu'       => 'eventkviz-leaderboard',
            'query_var'          => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array( 'title' ),
            'show_in_rest'       => false,
        );

        register_post_type( self::POST_TYPE, $args );
    }
}
