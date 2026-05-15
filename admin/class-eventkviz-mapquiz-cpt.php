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
            'name'                  => 'Mapové šablóny',
            'singular_name'         => 'Mapová šablóna',
            'menu_name'             => '🗺️ Mapové šablóny',
            'name_admin_bar'        => 'Mapová šablóna',
            'add_new'               => 'Pridať šablónu',
            'add_new_item'          => 'Pridať novú mapovú šablónu',
            'new_item'              => 'Nová mapová šablóna',
            'edit_item'             => 'Uprav mapovú šablónu',
            'view_item'             => 'Zobraz mapovú šablónu',
            'all_items'             => 'Všetky mapové šablóny',
            'search_items'          => 'Hľadať mapové šablóny',
            'not_found'             => 'Žiadne mapové šablóny nenájdené.',
            'not_found_in_trash'    => 'V koši nie sú žiadne mapové šablóny.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            // String value = parent menu slug → registers as submenu under existing EventKviz CPT menu
            'show_in_menu'       => 'edit.php?post_type=eventkviz_event',
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
