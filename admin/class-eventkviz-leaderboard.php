<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Eventkviz_Leaderboard {

    public static function init() {
        // Priority 15 — registers AFTER admin.php's add_plugin_admin_menu (priority 10).
        // Critical: parent menu's first submenu MUST be „Zoznam eventov" (slug =
        // edit.php?post_type=eventkviz_event) — inak sa top-level klik pokúsi ísť na URL
        // odvodené z prvého submenu (eventkviz-leaderboard) a vznikne 404.
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 15 );
    }

    public static function register_menu() {
        // Submenu pod existujúce CPT menu „EventKviz" (edit.php?post_type=eventkviz_event)
        // — jednotný admin menu pattern, žiadny duplikát top-level menu.
        add_submenu_page(
            'edit.php?post_type=eventkviz_event',
            __( 'Výsledky', 'eventkviz' ),
            __( 'Výsledky', 'eventkviz' ),
            'manage_options',
            'eventkviz-leaderboard',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nemáte oprávnenie.', 'eventkviz' ) );
        }

        global $wpdb;
        $akcie = $wpdb->get_col( "SELECT DISTINCT akcia FROM {$wpdb->prefix}jet_cct_results WHERE akcia IS NOT NULL AND akcia != '' ORDER BY akcia ASC" );
        $selected_akcia = isset( $_GET['akcia'] ) ? sanitize_text_field( wp_unslash( $_GET['akcia'] ) ) : ( $akcie ? $akcie[0] : '' );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Výsledky kvízov', 'eventkviz' ) . '</h1>';

        if ( empty( $akcie ) ) {
            echo '<p>' . esc_html__( 'Zatiaľ nie sú žiadne výsledky.', 'eventkviz' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<form method="get" style="margin:14px 0">';
        echo '<input type="hidden" name="page" value="eventkviz-leaderboard">';
        echo '<label for="akcia"><strong>' . esc_html__( 'Akcia', 'eventkviz' ) . ':</strong> </label>';
        echo '<select name="akcia" id="akcia" onchange="this.form.submit()">';
        foreach ( $akcie as $a ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $a ),
                selected( $a, $selected_akcia, false ),
                esc_html( $a )
            );
        }
        echo '</select>';
        echo '</form>';

        if ( $selected_akcia === '' ) {
            echo '</div>';
            return;
        }

        self::render_summary_table( $selected_akcia );
        self::render_per_quiz_table( $selected_akcia );

        echo '</div>';
    }

    private static function render_summary_table( $akcia ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                COALESCE(NULLIF(team,''), CONCAT('user:', user)) AS participant,
                team,
                user,
                SUM(points) AS total_points,
                COUNT(DISTINCT quiz_type) AS quizes_played
             FROM {$wpdb->prefix}jet_cct_results
             WHERE akcia = %s
             GROUP BY participant, team, user
             ORDER BY total_points DESC",
            $akcia
        ) );

        echo '<h2>' . esc_html__( 'Celkový rebríček', 'eventkviz' ) . '</h2>';
        if ( empty( $rows ) ) {
            echo '<p>' . esc_html__( 'Žiadne výsledky.', 'eventkviz' ) . '</p>';
            return;
        }
        echo '<table class="widefat striped" style="max-width:800px">';
        echo '<thead><tr><th>#</th><th>' . esc_html__( 'Tím / hráč', 'eventkviz' ) . '</th><th>' . esc_html__( 'Body', 'eventkviz' ) . '</th><th>' . esc_html__( 'Kvízov', 'eventkviz' ) . '</th></tr></thead><tbody>';
        $i = 1;
        foreach ( $rows as $r ) {
            $name = $r->team !== '' ? $r->team : ( $r->user !== '' ? $r->user : '—' );
            printf(
                '<tr><td>%d</td><td><strong>%s</strong></td><td>%s</td><td>%d</td></tr>',
                $i++,
                esc_html( $name ),
                esc_html( number_format_i18n( (int) $r->total_points ) ),
                (int) $r->quizes_played
            );
        }
        echo '</tbody></table>';
    }

    private static function render_per_quiz_table( $akcia ) {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT team, user, quiz_type, points, cct_created
             FROM {$wpdb->prefix}jet_cct_results
             WHERE akcia = %s
             ORDER BY quiz_type ASC, points DESC, cct_created DESC",
            $akcia
        ) );

        if ( empty( $rows ) ) {
            return;
        }

        $by_type = array();
        foreach ( $rows as $r ) {
            $by_type[ $r->quiz_type ][] = $r;
        }

        echo '<h2 style="margin-top:28px">' . esc_html__( 'Podľa typu kvízu', 'eventkviz' ) . '</h2>';
        foreach ( $by_type as $type => $list ) {
            echo '<h3>' . esc_html( ucfirst( $type ) ) . '</h3>';
            echo '<table class="widefat striped" style="max-width:800px;margin-bottom:18px">';
            echo '<thead><tr><th>#</th><th>' . esc_html__( 'Tím / hráč', 'eventkviz' ) . '</th><th>' . esc_html__( 'Body', 'eventkviz' ) . '</th><th>' . esc_html__( 'Čas', 'eventkviz' ) . '</th></tr></thead><tbody>';
            $i = 1;
            foreach ( $list as $r ) {
                $name = $r->team !== '' ? $r->team : ( $r->user !== '' ? $r->user : '—' );
                printf(
                    '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    $i++,
                    esc_html( $name ),
                    esc_html( number_format_i18n( (int) $r->points ) ),
                    esc_html( $r->cct_created )
                );
            }
            echo '</tbody></table>';
        }
    }
}
