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
     * Názov mapovej šablóny podľa mq slug (z nastavení eventu).
     */
    private function mapa_label( $slug ) {
        if ( empty( $this->cAkcia->mapa_quizzes ) || ! is_array( $this->cAkcia->mapa_quizzes ) ) return '';
        foreach ( $this->cAkcia->mapa_quizzes as $q ) {
            if ( isset( $q['slug'] ) && $q['slug'] === $slug && ! empty( $q['label'] ) ) return $q['label'];
        }
        return '';
    }

    /**
     * Ikona + slovenský názov pre kľúč kvíze. Mapové kľúče majú tvar "mapa:<slug>"
     * a názov sa berie z konkrétnej mapovej šablóny.
     */
    private function stats_quiz_meta( $key ) {
        if ( strpos( $key, 'mapa:' ) === 0 ) {
            $label = $this->mapa_label( substr( $key, 5 ) );
            return array( '🗺️', $label !== '' ? $label : 'Mapový kvíz' );
        }
        $map = array(
            'music'     => array( '🎵', 'Hudobný kvíz' ),
            'movies'    => array( '🎬', 'Filmový kvíz' ),
            'knowledge' => array( '🧠', 'Vedomostný kvíz' ),
            'sudoku'    => array( '🔢', 'Sudoku' ),
            'final'     => array( '🏁', 'Finálne miesto' ),
        );
        return isset( $map[ $key ] ) ? $map[ $key ] : array( '📋', ucfirst( $key ) );
    }

    /**
     * Agreguje výsledky akcie podľa entity ('team' | 'user').
     * Mapový kvíz rozlišuje jednotlivé šablóny (mq slug z question_set) — každá
     * šablóna je samostatná súťažná položka. Pre každú položku sa berie najlepší
     * pokus (MAX), celkové poradie = súčet týchto najlepších pokusov.
     *
     * @return array [ $leaderboard (zoradené zostupne), $by_quiz ]
     */
    private function build_stats( $akcia, $entity ) {
        global $wpdb;
        $col  = $entity === 'team' ? 'team' : 'user';
        $rows = $wpdb->get_results( $wpdb->prepare( "
            SELECT quiz_type, points, {$col} as ent, question_set
            FROM {$wpdb->prefix}jet_cct_results
            WHERE akcia = %s
        ", $akcia ) );

        $max = array(); // [itemKey][entita] = najlepší pokus
        foreach ( (array) $rows as $r ) {
            $ent = $r->ent;
            if ( $ent === null || $ent === '' ) continue;

            $key = $r->quiz_type;
            if ( $r->quiz_type === 'mapa' ) {
                $qs  = json_decode( (string) $r->question_set, true );
                $mq  = ( is_array( $qs ) && ! empty( $qs['mq'] ) ) ? sanitize_key( $qs['mq'] ) : 'ine';
                $key = 'mapa:' . $mq;
            }

            $p = (int) $r->points;
            if ( ! isset( $max[ $key ][ $ent ] ) || $p > $max[ $key ][ $ent ] ) {
                $max[ $key ][ $ent ] = $p;
            }
        }

        $leaderboard = array();
        $by_quiz     = array();
        foreach ( $max as $key => $ents ) {
            foreach ( $ents as $ent => $p ) {
                if ( ! isset( $leaderboard[ $ent ] ) ) $leaderboard[ $ent ] = 0;
                $leaderboard[ $ent ] += $p;
                $by_quiz[ $key ][ $ent ] = $p;
            }
        }
        arsort( $leaderboard );

        return array( $leaderboard, $by_quiz );
    }

    /**
     * Rebríček. $sorted = [ 'názov' => body ] zoradené zostupne. Pri $highlight !== ''
     * zobrazí IBA jeden riadok pre daný tím/hráč so správnou pozíciou v celkovom
     * poradí (napr. "6. miesto z 8"). Bez highlight = celý rebríček.
     */
    private function render_leaderboard( $sorted, $highlight = '', $by_quiz = array() ) {
        if ( empty( $sorted ) ) {
            echo '<p class="ek-stats-empty">Zatiaľ žiadne body.</p>';
            return;
        }
        $total = count( $sorted );
        echo '<ol class="ek-stats-leaderboard">';
        $pos = 0;
        foreach ( $sorted as $name => $pts ) {
            $pos++;
            if ( $highlight !== '' && $highlight !== $name ) {
                continue; // filter na jeden tím/hráč
            }
            $hl_cls = $highlight !== '' ? ' ek-stats-rank--highlight' : '';
            echo '<li class="ek-stats-rank' . $hl_cls . '">';
            echo '<div class="ek-stats-rank-head">';
            echo '<span class="ek-stats-rank-badge">' . $pos . '</span>';
            echo '<span class="ek-stats-rank-name">' . esc_html( $name ) . '</span>';
            echo '<span class="ek-stats-rank-points">' . intval( $pts ) . ' b</span>';
            echo '</div>';
            // Vždy viditeľný rozpis bodov po kvízoch pre tento tím/hráča.
            if ( $highlight === '' && ! empty( $by_quiz ) ) {
                $bd = $this->entity_breakdown( $name, $by_quiz );
                if ( ! empty( $bd ) ) {
                    echo '<ul class="ek-stats-rank-breakdown">';
                    foreach ( $bd as $r ) {
                        echo '<li class="ek-stats-rank-bd-row">';
                        echo '<span class="ek-stats-rank-bd-quiz">' . $r['icon'] . ' ' . esc_html( $r['label'] ) . '</span>';
                        echo '<span class="ek-stats-rank-bd-pts">' . intval( $r['pts'] ) . ' b</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
            }
            echo '</li>';
        }
        echo '</ol>';
        if ( $highlight !== '' ) {
            echo '<p class="ek-stats-context">z celkového počtu <strong>' . $total . '</strong> tímov / hráčov</p>';
        }
    }

    /** Poradové číslo kvíze pre stabilné triedenie (rovnaké ako render_by_quiz). */
    private function quiz_key_rank( $k ) {
        $order = array( 'music' => 1, 'movies' => 2, 'knowledge' => 3, 'sudoku' => 4, 'final' => 5 );
        return isset( $order[ $k ] ) ? $order[ $k ] : ( strpos( $k, 'mapa:' ) === 0 ? 10 : 8 );
    }

    /** Rozpis bodov jednej entity po kvízoch (len kde má > 0), zoradený. */
    private function entity_breakdown( $name, $by_quiz ) {
        $rows = array();
        foreach ( $by_quiz as $key => $ents ) {
            if ( ! isset( $ents[ $name ] ) ) continue;
            $p = (int) $ents[ $name ];
            if ( $p <= 0 ) continue;
            list( $icon, $label ) = $this->stats_quiz_meta( $key );
            $rows[] = array( 'icon' => $icon, 'label' => $label, 'pts' => $p, 'rank' => $this->quiz_key_rank( $key ) );
        }
        usort( $rows, function ( $a, $b ) { return $a['rank'] <=> $b['rank']; } );
        return $rows;
    }

    /**
     * Export toolbar (CSV + PDF) — len pre plný pohľad (žiadny highlight), aby sa
     * v zamknutom režime neexportovali cudzie tímy. Dáta sa odovzdajú do JS, ktorý
     * vygeneruje súbor klientsky (žiadny server round-trip).
     */
    private function render_export( $entity_label, $sorted, $by_quiz, $akcia ) {
        if ( empty( $sorted ) ) return;
        $keys = array_keys( $by_quiz );
        usort( $keys, function ( $a, $b ) { return $this->quiz_key_rank( $a ) <=> $this->quiz_key_rank( $b ); } );
        $quizzes = array();
        foreach ( $keys as $k ) {
            list( $icon, $label ) = $this->stats_quiz_meta( $k );
            $quizzes[] = array( 'key' => $k, 'label' => $label );
        }
        $rows = array();
        $pos = 0;
        foreach ( $sorted as $name => $pts ) {
            $pos++;
            $pmap = array();
            foreach ( $keys as $k ) {
                $pmap[ $k ] = isset( $by_quiz[ $k ][ $name ] ) ? (int) $by_quiz[ $k ][ $name ] : 0;
            }
            $rows[] = array( 'rank' => $pos, 'name' => (string) $name, 'total' => (int) $pts, 'points' => $pmap );
        }
        $data = array(
            'akcia'       => (string) $akcia,
            'entityLabel' => $entity_label,
            'quizzes'     => $quizzes,
            'rows'        => $rows,
        );
        wp_enqueue_script(
            'eventkviz-stats-export',
            plugin_dir_url( __FILE__ ) . '../public/js/eventkviz-stats-export.js',
            array(),
            defined( 'EVENKVIZ_VERSION' ) ? EVENKVIZ_VERSION : '1.0',
            true
        );
        echo '<div class="ek-stats-export">';
        echo '<button type="button" class="ek-stats-export-btn" data-fmt="csv">⬇ Export CSV</button>';
        echo '<button type="button" class="ek-stats-export-btn" data-fmt="pdf">⬇ Export PDF</button>';
        echo '</div>';
        echo '<script>window.ekStatsExport = ' . wp_json_encode( $data ) . ';</script>';
    }

    /**
     * Karty po kvízoch. $grouped = [ kľúč => [ 'názov' => body ] ].
     * Poradie kariet: štandardné kvízy, potom jednotlivé mapové šablóny.
     * $highlight = názov ktorý sa má zvýrazniť (riadok v každej karte).
     */
    private function render_by_quiz( $grouped, $highlight = '' ) {
        if ( empty( $grouped ) ) {
            echo '<p class="ek-stats-empty">Zatiaľ žiadne výsledky po kvízoch.</p>';
            return;
        }
        $order = array( 'music' => 1, 'movies' => 2, 'knowledge' => 3, 'sudoku' => 4, 'final' => 5 );
        uksort( $grouped, function( $a, $b ) use ( $order ) {
            $oa = isset( $order[ $a ] ) ? $order[ $a ] : ( strpos( $a, 'mapa:' ) === 0 ? 10 : 8 );
            $ob = isset( $order[ $b ] ) ? $order[ $b ] : ( strpos( $b, 'mapa:' ) === 0 ? 10 : 8 );
            return $oa <=> $ob;
        } );

        echo '<div class="ek-stats-quiz-grid">';
        foreach ( $grouped as $key => $entries ) {
            arsort( $entries );
            list( $icon, $label ) = $this->stats_quiz_meta( $key );
            echo '<div class="ek-stats-quiz-card">';
            echo '<div class="ek-stats-quiz-card-title">' . $icon . ' ' . esc_html( $label ) . '</div>';
            $pos = 0;
            $rendered_any = false;
            foreach ( $entries as $name => $pts ) {
                $pos++;
                if ( $highlight !== '' && $highlight !== $name ) {
                    continue;
                }
                $hl_cls = $highlight !== '' ? ' ek-stats-quiz-row--highlight' : '';
                echo '<div class="ek-stats-quiz-row' . $hl_cls . '">';
                echo '<span class="ek-stats-quiz-row-pos">' . $pos . '.</span>';
                echo '<span class="ek-stats-quiz-row-name">' . esc_html( $name ) . '</span>';
                echo '<span class="ek-stats-quiz-row-pts">' . intval( $pts ) . ' b</span>';
                echo '</div>';
                $rendered_any = true;
            }
            if ( $highlight !== '' && ! $rendered_any ) {
                echo '<div class="ek-stats-empty" style="margin:6px 0 0;text-align:center;font-size:13px;">Neabsolvoval</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Jednoduchý zoznam „štítok → hodnota" (napr. počet hráčov v tíme).
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
            'team'  => '',
            'user'  => '',
        ), $atts );

        if ( empty( $value['akcia'] ) && get_query_var( 'akcia' ) ) {
            $value['akcia'] = sanitize_key( get_query_var( 'akcia' ) );
        }

        // C2: filter rebríčka pre jeden konkrétny tím/hráča.
        // Identita (team/user) sa rieši nižšie — až po načítaní nastavení eventu,
        // aby sme vedeli, či je zapnutý zamknutý tímový režim (token-only).

        if ( empty( $value['akcia'] ) ) {
            echo '<div class="ek-quiz ek-quiz--stats"><div class="ek-quiz-content">';
            echo '<p class="ek-stats-empty">Akcia nie je špecifikovaná. Použite ?akcia=&lt;slug&gt; v URL.</p>';
            echo '</div></div>';
            return;
        }

        $this->load_basic_event_settings( $value['akcia'] );

        if ( ! isset( $this->cAkcia ) || empty( $this->cAkcia->all_quizes_settings ) ) {
            echo '<div class="ek-quiz ek-quiz--stats"><div class="ek-quiz-content">';
            echo '<p class="ek-stats-empty">Akcia „' . esc_html( $value['akcia'] ) . '" sa nenašla alebo nemá nastavenia.</p>';
            echo '</div></div>';
            return;
        }

        // 🔒 Zamknutý režim: identita LEN z podpísaného tokenu (?t=); plain ?team= sa
        // ignoruje (dá sa prepísať na cudzí tím). Mimo režimu = pôvodné plain ?team/?user.
        $ek_locked = ! empty( $this->cAkcia->all_quizes_settings['locked_team_mode'] );
        if ( $ek_locked ) {
            $ek_tok = class_exists( 'Eventkviz_Link_Token' ) ? Eventkviz_Link_Token::request_token() : null;
            if ( $value['team'] === '' ) $value['team'] = (string) ( $ek_tok['team'] ?? '' );
            if ( $value['user'] === '' ) $value['user'] = (string) ( $ek_tok['user'] ?? '' );
        } else {
            if ( $value['team'] === '' ) {
                $value['team'] = isset( $_GET['team'] ) ? sanitize_text_field( wp_unslash( $_GET['team'] ) ) : '';
            }
            if ( $value['user'] === '' ) {
                $value['user'] = isset( $_GET['user'] ) ? sanitize_text_field( wp_unslash( $_GET['user'] ) ) : '';
            }
        }
        $highlight = $value['team'] !== '' ? $value['team'] : $value['user'];

        // Admin (prihlásený WP používateľ s manage_options) vidí celý rebríček všetkých
        // tímov aj v zamknutom režime — súkromie medzi tímami sa týka len hráčov.
        $ek_is_admin = current_user_can( 'manage_options' );

        // V zamknutom režime bez platného tokenu NEUKÁZAŤ celý rebríček (únik cudzích
        // tímov). Štatistika je dostupná len cez vlastný tímový link. Admin je výnimka.
        if ( $ek_locked && $highlight === '' && ! $ek_is_admin ) {
            echo '<div class="ek-quiz ek-quiz--stats"><div class="ek-quiz-content">';
            echo '<p class="ek-stats-empty">Štatistika je dostupná len cez tvoj tímový link.</p>';
            echo '</div></div>';
            return;
        }

        echo '<div class="ek-quiz ek-quiz--stats">';
        echo '<div class="ek-quiz-content">';
        if ( $highlight !== '' ) {
            $kind = $value['team'] !== '' ? 'tímu' : 'hráča';
            echo '<h1 class="ek-quiz-title">🏆 Výsledky ' . $kind . ': ' . esc_html( $highlight ) . '</h1>';
            echo '<p class="ek-quiz-subtitle">Tvoja pozícia v celkovom poradí a body po jednotlivých kvízoch</p>';
        } else {
            echo '<h1 class="ek-quiz-title">🏆 Výsledky</h1>';
            echo '<p class="ek-quiz-subtitle">Priebežné poradie a body po kvízoch</p>';
        }

        if ( $this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true ) {

            // ----- Identifikácia hráčom -----
            list( $leaderboard, $by_quiz ) = $this->build_stats( $value['akcia'], 'user' );

            echo '<h2 class="ek-stats-section-title">Poradie hráčov</h2>';
            if ( $highlight === '' ) $this->render_export( 'Hráč', $leaderboard, $by_quiz, $value['akcia'] );
            $this->render_leaderboard( $leaderboard, $highlight, $by_quiz );

            echo '<h2 class="ek-stats-section-title">Body po kvízoch</h2>';
            $this->render_by_quiz( $by_quiz, $highlight );

            // Počet hráčov v jednotlivých tímoch.
            global $wpdb;
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
            list( $leaderboard, $by_quiz ) = $this->build_stats( $value['akcia'], 'team' );

            echo '<h2 class="ek-stats-section-title">Poradie tímov</h2>';
            if ( $highlight === '' ) $this->render_export( 'Tím', $leaderboard, $by_quiz, $value['akcia'] );
            $this->render_leaderboard( $leaderboard, $highlight, $by_quiz );

            echo '<h2 class="ek-stats-section-title">Body po kvízoch</h2>';
            $this->render_by_quiz( $by_quiz, $highlight );

        } else {
            echo '<p class="ek-stats-empty">Pre túto akciu nie je nastavená identifikácia hráčov ani tímov.</p>';
        }

        echo '</div>'; // .ek-quiz-content
        echo '</div>'; // .ek-quiz
    }
}
