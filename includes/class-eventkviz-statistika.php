<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_Statistika_Class extends Eventkviz_Quiz_Class{

    public function __construct() {

    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'statistika', array( $plugin, 'statistika' ) );
    }

    /**
     * Ikona + slovenský názov pre quiz_type. Fallback pre neznáme typy.
     */
    private function stats_quiz_meta( $type ) {
        $map = array(
            'music'     => array( '🎵', 'Hudobný kvíz' ),
            'movies'    => array( '🎬', 'Filmový kvíz' ),
            'knowledge' => array( '🧠', 'Vedomostný kvíz' ),
            'sudoku'    => array( '🔢', 'Sudoku' ),
            'mapa'      => array( '🗺️', 'Mapový kvíz' ),
            'final'     => array( '🏁', 'Finálne miesto' ),
        );
        return isset( $map[ $type ] ) ? $map[ $type ] : array( '📋', ucfirst( $type ) );
    }

    /**
     * Rebríček (leaderboard). $sorted = [ 'názov' => body ] už zoradené zostupne.
     */
    private function render_leaderboard( $sorted ) {
        if ( empty( $sorted ) ) {
            echo '<p class="ek-stats-empty">Zatiaľ žiadne body.</p>';
            return;
        }
        echo '<ol class="ek-stats-leaderboard">';
        $pos = 0;
        foreach ( $sorted as $name => $pts ) {
            $pos++;
            $medal = $pos === 1 ? '🥇' : ( $pos === 2 ? '🥈' : ( $pos === 3 ? '🥉' : $pos ) );
            $cls   = $pos <= 3 ? ' ek-stats-rank--' . $pos : '';
            echo '<li class="ek-stats-rank' . $cls . '">';
            echo '<span class="ek-stats-rank-badge">' . $medal . '</span>';
            echo '<span class="ek-stats-rank-name">' . esc_html( $name ) . '</span>';
            echo '<span class="ek-stats-rank-points">' . intval( $pts ) . ' b</span>';
            echo '</li>';
        }
        echo '</ol>';
    }

    /**
     * Karty po kvízoch. $grouped = [ 'quiz_type' => [ 'názov' => body ] ].
     */
    private function render_by_quiz( $grouped ) {
        if ( empty( $grouped ) ) {
            echo '<p class="ek-stats-empty">Zatiaľ žiadne výsledky po kvízoch.</p>';
            return;
        }
        echo '<div class="ek-stats-quiz-grid">';
        foreach ( $grouped as $type => $entries ) {
            arsort( $entries );
            list( $icon, $label ) = $this->stats_quiz_meta( $type );
            echo '<div class="ek-stats-quiz-card">';
            echo '<div class="ek-stats-quiz-card-title">' . $icon . ' ' . esc_html( $label ) . '</div>';
            $pos = 0;
            foreach ( $entries as $name => $pts ) {
                $pos++;
                echo '<div class="ek-stats-quiz-row">';
                echo '<span class="ek-stats-quiz-row-pos">' . $pos . '.</span>';
                echo '<span class="ek-stats-quiz-row-name">' . esc_html( $name ) . '</span>';
                echo '<span class="ek-stats-quiz-row-pts">' . intval( $pts ) . ' b</span>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Jednoduchý zoznam „štítok → hodnota" v glass štýle (napr. počet hráčov v tíme).
     * $rows = [ 'štítok' => 'hodnota' ].
     */
    private function render_kv_list( $rows ) {
        if ( empty( $rows ) ) {
            echo '<p class="ek-stats-empty">Žiadne dáta.</p>';
            return;
        }
        echo '<ul class="ek-stats-leaderboard">';
        foreach ( $rows as $label => $val ) {
            echo '<li class="ek-stats-rank">';
            echo '<span class="ek-stats-rank-name">' . esc_html( $label ) . '</span>';
            echo '<span class="ek-stats-rank-points">' . esc_html( $val ) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    public function statistika( $atts = '' ) {

        $value = shortcode_atts( array(
            'type'  => '',
            'akcia' => '',
        ), $atts );

        if ( empty( $value['akcia'] ) && get_query_var( 'akcia' ) ) {
            $value['akcia'] = sanitize_key( get_query_var( 'akcia' ) );
        }

        if ( empty( $value['akcia'] ) ) {
            echo '<div class="ek-quiz ek-quiz--stats"><div class="ek-quiz-content">';
            echo '<p class="ek-stats-empty">Akcia nie je špecifikovaná. Použite ?akcia=&lt;slug&gt; v URL.</p>';
            echo '</div></div>';
            return;
        }

        global $wpdb;

        // load_basic_event_settings() zvláda legacy per-event triedy aj meta-based eventy.
        $this->load_basic_event_settings( $value['akcia'] );

        if ( ! isset( $this->cAkcia ) || empty( $this->cAkcia->all_quizes_settings ) ) {
            echo '<div class="ek-quiz ek-quiz--stats"><div class="ek-quiz-content">';
            echo '<p class="ek-stats-empty">Akcia „' . esc_html( $value['akcia'] ) . '" sa nenašla alebo nemá nastavenia.</p>';
            echo '</div></div>';
            return;
        }

        echo '<div class="ek-quiz ek-quiz--stats">';
        echo '<div class="ek-quiz-content">';
        echo '<h1 class="ek-quiz-title">🏆 Výsledky</h1>';
        echo '<p class="ek-quiz-subtitle">Priebežné poradie a body po kvízoch</p>';

        if ( $this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true ) {

            // ----- Identifikácia hráčom (user code) -----

            // Rebríček hráčov — súčet najlepších bodov hráča naprieč kvízmi.
            $results = $wpdb->get_results( $wpdb->prepare( "
                SELECT quiz_type, MAX(points) as points, user
                FROM {$wpdb->prefix}jet_cct_results
                WHERE akcia = %s
                GROUP BY quiz_type, user
            ", $value['akcia'] ) );

            $leaderboard = array();
            $by_quiz     = array();
            foreach ( (array) $results as $r ) {
                if ( $r->user === null || $r->user === '' ) continue;
                if ( ! isset( $leaderboard[ $r->user ] ) ) $leaderboard[ $r->user ] = 0;
                $leaderboard[ $r->user ] += (int) $r->points;
                $by_quiz[ $r->quiz_type ][ $r->user ] = (int) $r->points;
            }
            arsort( $leaderboard );

            // Poradie tímov (kumulatívne body tímu naprieč kvízmi).
            $team_board = array();
            foreach ( (array) $results as $r ) {
                if ( $r->team === null || $r->team === '' ) continue;
                if ( ! isset( $team_board[ $r->team ] ) ) $team_board[ $r->team ] = 0;
                $team_board[ $r->team ] += (int) $r->points;
            }
            arsort( $team_board );

            echo '<h2 class="ek-stats-section-title">Poradie hráčov</h2>';
            $this->render_leaderboard( $leaderboard );

            if ( ! empty( $team_board ) ) {
                echo '<h2 class="ek-stats-section-title">Poradie tímov</h2>';
                $this->render_leaderboard( $team_board );
            }

            echo '<h2 class="ek-stats-section-title">Body po kvízoch (najlepší pokus)</h2>';
            $this->render_by_quiz( $by_quiz );

            // Body vrátane opakovaní — súčet bodov hráča cez všetky pokusy daného kvíze.
            $sum_rows = $wpdb->get_results( $wpdb->prepare( "
                SELECT r.quiz_type, r.user, SUM(r.points) as total_points
                FROM (
                    SELECT quiz_type, user, MAX(points) as points
                    FROM {$wpdb->prefix}jet_cct_results
                    WHERE akcia = %s
                    GROUP BY quiz_type, user
                ) as m
                INNER JOIN {$wpdb->prefix}jet_cct_results as r
                ON m.quiz_type = r.quiz_type AND m.user = r.user AND m.points = r.points
                GROUP BY r.quiz_type, r.user
            ", $value['akcia'] ) );
            $by_quiz_sum = array();
            foreach ( (array) $sum_rows as $r ) {
                if ( $r->user === null || $r->user === '' ) continue;
                $by_quiz_sum[ $r->quiz_type ][ $r->user ] = (int) $r->total_points;
            }
            if ( ! empty( $by_quiz_sum ) ) {
                echo '<h2 class="ek-stats-section-title">Body vrátane opakovaní</h2>';
                $this->render_by_quiz( $by_quiz_sum );
            }

            // Počet hráčov v jednotlivých tímoch.
            $team_users = $wpdb->get_results( $wpdb->prepare( "
                SELECT team, COUNT(DISTINCT user) as unique_users
                FROM {$wpdb->prefix}jet_cct_results
                WHERE akcia = %s AND user IS NOT NULL AND user <> ''
                GROUP BY team
                ORDER BY unique_users DESC
            ", $value['akcia'] ) );
            $tu_rows = array();
            foreach ( (array) $team_users as $r ) {
                if ( $r->team === null || $r->team === '' ) continue;
                $n = (int) $r->unique_users;
                $tu_rows[ $r->team ] = $n . ' ' . ( $n === 1 ? 'hráč' : ( $n >= 2 && $n <= 4 ? 'hráči' : 'hráčov' ) );
            }
            if ( ! empty( $tu_rows ) ) {
                echo '<h2 class="ek-stats-section-title">Počet hráčov v tímoch</h2>';
                $this->render_kv_list( $tu_rows );
            }

        } elseif ( $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] == true
                && $this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false ) {

            // ----- Identifikácia tímom -----

            $results = $wpdb->get_results( $wpdb->prepare( "
                SELECT quiz_type, MAX(points) as points, team
                FROM {$wpdb->prefix}jet_cct_results
                WHERE akcia = %s
                GROUP BY quiz_type, team
            ", $value['akcia'] ) );

            $leaderboard = array();
            $by_quiz     = array();
            foreach ( (array) $results as $r ) {
                if ( $r->team === null || $r->team === '' ) continue;
                if ( ! isset( $leaderboard[ $r->team ] ) ) $leaderboard[ $r->team ] = 0;
                $leaderboard[ $r->team ] += (int) $r->points;
                $by_quiz[ $r->quiz_type ][ $r->team ] = (int) $r->points;
            }
            arsort( $leaderboard );

            echo '<h2 class="ek-stats-section-title">Poradie tímov</h2>';
            $this->render_leaderboard( $leaderboard );

            echo '<h2 class="ek-stats-section-title">Body po kvízoch (najlepší pokus)</h2>';
            $this->render_by_quiz( $by_quiz );

            // Body vrátane opakovaní — súčet bodov tímu cez všetky pokusy kvíze.
            $sum_rows = $wpdb->get_results( $wpdb->prepare( "
                SELECT r.quiz_type, r.team, SUM(r.points) as total_points
                FROM (
                    SELECT quiz_type, team, MAX(points) as points
                    FROM {$wpdb->prefix}jet_cct_results
                    WHERE akcia = %s
                    GROUP BY quiz_type, team
                ) as m
                INNER JOIN {$wpdb->prefix}jet_cct_results as r
                ON m.quiz_type = r.quiz_type AND m.team = r.team AND m.points = r.points
                GROUP BY r.quiz_type, r.team
            ", $value['akcia'] ) );
            $by_quiz_sum = array();
            foreach ( (array) $sum_rows as $r ) {
                if ( $r->team === null || $r->team === '' ) continue;
                $by_quiz_sum[ $r->quiz_type ][ $r->team ] = (int) $r->total_points;
            }
            if ( ! empty( $by_quiz_sum ) ) {
                echo '<h2 class="ek-stats-section-title">Body vrátane opakovaní</h2>';
                $this->render_by_quiz( $by_quiz_sum );
            }

        } else {
            echo '<p class="ek-stats-empty">Pre túto akciu nie je nastavená identifikácia hráčov ani tímov.</p>';
        }

        echo '</div>'; // .ek-quiz-content
        echo '</div>'; // .ek-quiz
    }
}
