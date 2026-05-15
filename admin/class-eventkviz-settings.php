<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin-level settings page (WP options API). Currently houses:
 *   - MapTiler API key (used by map quiz admin editor + future player tiles)
 *
 * Accessed via: Admin → EventKviz výsledky → ⚙️ Nastavenia
 */
class Eventkviz_Settings {

    const OPTION_GROUP   = 'eventkviz_settings';
    const OPTION_NAME    = 'eventkviz_options';
    const MAPTILER_KEY   = 'maptiler_api_key';
    const PAGE_SLUG      = 'eventkviz-settings';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function register_menu() {
        add_submenu_page(
            'edit.php?post_type=eventkviz_event',     // parent: existing EventKviz CPT top-level
            __( 'EventKviz – Nastavenia', 'eventkviz' ),
            __( '⚙️ Nastavenia', 'eventkviz' ),
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    public static function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize' ),
                'default'           => array(),
            )
        );

        add_settings_section(
            'eventkviz_section_maptiler',
            __( 'MapTiler integrácia', 'eventkviz' ),
            function() {
                echo '<p>API kľúč pre <a href="https://www.maptiler.com/cloud/account/keys/" target="_blank" rel="noopener">MapTiler Cloud</a>. Používa sa v admin editore mapových kvízov pre kreslenie pinov na detailnej mape. (Hráč vidí len blank outline — nečerpá tile quote.)</p>';
            },
            self::PAGE_SLUG
        );

        add_settings_field(
            self::MAPTILER_KEY,
            __( 'API kľúč', 'eventkviz' ),
            array( __CLASS__, 'render_maptiler_field' ),
            self::PAGE_SLUG,
            'eventkviz_section_maptiler'
        );
    }

    public static function render_maptiler_field() {
        $options = get_option( self::OPTION_NAME, array() );
        $value   = isset( $options[ self::MAPTILER_KEY ] ) ? $options[ self::MAPTILER_KEY ] : '';
        printf(
            '<input type="text" name="%s[%s]" value="%s" class="regular-text" autocomplete="off" placeholder="napr. AbC123def456…" />',
            esc_attr( self::OPTION_NAME ),
            esc_attr( self::MAPTILER_KEY ),
            esc_attr( $value )
        );
        echo '<p class="description">Bez kľúča sa admin editor mapových kvízov nebude vedieť načítať. <strong>Nezdieľaj tento kľúč verejne.</strong></p>';
    }

    public static function sanitize( $input ) {
        $clean = array();
        if ( isset( $input[ self::MAPTILER_KEY ] ) ) {
            $clean[ self::MAPTILER_KEY ] = sanitize_text_field( $input[ self::MAPTILER_KEY ] );
        }
        return $clean;
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nemáte oprávnenie.', 'eventkviz' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'EventKviz — Nastavenia', 'eventkviz' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Helper for other classes to read the MapTiler API key.
     */
    public static function get_maptiler_key() {
        $options = get_option( self::OPTION_NAME, array() );
        return isset( $options[ self::MAPTILER_KEY ] ) ? $options[ self::MAPTILER_KEY ] : '';
    }
}
