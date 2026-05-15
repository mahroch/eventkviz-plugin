<?php

require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Player-side form for map quiz. Renders Leaflet map + clickable task list
 * + hidden inputs for guessed coordinates. Submits to eval page (Fáza 5).
 *
 * Shortcode: [mapa_form_dynamic]
 *
 * Template lookup flow:
 *   1. event_mapa_template_id → mapquiz_template post
 *   2. Read _mapquiz_pins JSON → pick N random (or reuse via questions_set)
 *   3. Render N tasks with player has to find on map
 */
class Eventkviz_MapaForm_Quiz_Class extends Eventkviz_Quiz_Class {

    public function __construct() {
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'mapa_form_dynamic', array( $plugin, 'eventkviz_mapa_form' ) );
    }

    public function eventkviz_mapa_form( $atts = '' ) {
        $user_code  = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' );
        $this->load_basic_event_settings( $akcia_code );

        // GeoChallenge per-player scoping
        $gc_user = $this->geo_user_code( 'form' );
        if ( $gc_user !== '' ) $user_code = $gc_user;

        $team_code = $this->set_team_code( $user_code, $akcia_code );

        // Show entry form if needed (mirror pattern from other quizes)
        if ( ! empty( $this->cAkcia->mapa_settings['show_entry_form'] ) ) {
            if ( ! empty( $this->cAkcia->all_quizes_settings['select_from_teams_array'] ) && empty( $team_code ) ) {
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;
                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links( $att, 'mapa' );
                die;
            }
        }

        $check_result = $this->check_number_of_tries( $user_code, $akcia_code, 'mapa', $team_code );
        if ( $check_result !== true ) return;

        $template_id = isset( $this->cAkcia->mapa_settings['template_id'] ) ? (int) $this->cAkcia->mapa_settings['template_id'] : 0;
        if ( $template_id <= 0 ) {
            echo '<p class="ek-quiz-error">Pre tento event nie je nastavená šablóna mapového kvízu. Otvor admin → event → tab Mapa.</p>';
            return;
        }

        $template = get_post( $template_id );
        if ( ! $template || $template->post_type !== 'mapquiz_template' || $template->post_status !== 'publish' ) {
            echo '<p class="ek-quiz-error">Šablóna mapového kvízu nenájdená alebo nepublikovaná.</p>';
            return;
        }

        // Load template data
        $region        = get_post_meta( $template_id, '_mapquiz_region', true ) ?: 'slovakia';
        $player_detail = get_post_meta( $template_id, '_mapquiz_player_detail', true ) ?: 'outline-only';
        $pins_json     = get_post_meta( $template_id, '_mapquiz_pins', true );
        $all_pins      = is_string( $pins_json ) ? json_decode( $pins_json, true ) : array();
        if ( ! is_array( $all_pins ) ) $all_pins = array();

        if ( count( $all_pins ) === 0 ) {
            echo '<p class="ek-quiz-error">Šablóna „' . esc_html( $template->post_title ) . '" nemá žiadne piny. Admin musí v editore šablóny pridať aspoň jeden pin.</p>';
            return;
        }

        // Determine which pins to use (reuse from prior session or generate new)
        $question_set_exists = $this->check_if_questions_set_exists( $akcia_code, 'mapa', $user_code, $team_code );
        $regenerate_on_retry = ! empty( $this->cAkcia->mapa_settings['new_questions_on_retry'] );
        $treat_as_new        = ! $question_set_exists || $regenerate_on_retry;

        $count_in_set = isset( $this->cAkcia->mapa_settings['pocet_otazok_v_sete'] ) ? max( 1, (int) $this->cAkcia->mapa_settings['pocet_otazok_v_sete'] ) : 10;
        $count_in_set = min( $count_in_set, count( $all_pins ) );

        if ( $treat_as_new ) {
            // Random selection of pin ids
            $shuffled = $all_pins;
            shuffle( $shuffled );
            $selected = array_slice( $shuffled, 0, $count_in_set );
            $selected_pin_ids = array_map( function( $p ) { return $p['id']; }, $selected );
        } else {
            // Reuse pin ids from prior session (stored in $this->questions_set from check_if_questions_set_exists)
            $selected_pin_ids = is_array( $this->questions_set ) ? $this->questions_set : array();
            $selected = array();
            foreach ( $selected_pin_ids as $pid ) {
                foreach ( $all_pins as $p ) {
                    if ( $p['id'] === $pid ) { $selected[] = $p; break; }
                }
            }
        }

        if ( empty( $selected ) ) {
            echo '<p class="ek-quiz-error">Pri výbere pinov nastala chyba (prázdny set).</p>';
            return;
        }

        // Build sanitized data for JS — strip out solution coords (lat/lon) so they
        // are not exposed in the HTML source. Only id + name + hint + photo + description.
        $tasks_for_js = array();
        foreach ( $selected as $p ) {
            $task = array(
                'id'          => (string) $p['id'],
                'name'        => (string) ( $p['name'] ?? '' ),
                'hint'        => (string) ( $p['hint'] ?? '' ),
                'description' => (string) ( $p['description'] ?? '' ),
                'photo_url'   => '',
            );
            if ( ! empty( $p['photo_id'] ) ) {
                $url = wp_get_attachment_image_url( (int) $p['photo_id'], 'medium' );
                if ( $url ) $task['photo_url'] = $url;
            }
            $tasks_for_js[] = $task;
        }

        $serialized_set = wp_json_encode( $selected_pin_ids );

        $url = home_url( '/mapa-quiz-dynamic-evaluation/' );

        $is_review = ! empty( $_POST['prev_review'] );

        echo '<div class="ek-quiz">';
        echo '<div class="ek-quiz-content">';
        echo '<h1 class="ek-quiz-title">Mapový kvíz: ' . esc_html( $template->post_title ) . '</h1>';
        echo '<p class="ek-quiz-subtitle">Označ na mape miesta uvedené v zozname.</p>';
        $this->render_tries_remaining_banner( 'mapa' );
        if ( $is_review ) {
            echo '<div class="ek-review-banner">📝 Vaše predchádzajúce odpovede sú vyznačené — <strong style="color:#6dd58c">zelené</strong> boli v tieri (úspech), <strong style="color:#ff6b6b">červené</strong> mimo. Klikni znova na ktorýkoľvek pin a presuň.</div>';
        }

        echo '<form action="' . esc_url( $url ) . '" method="post" class="ek-quiz-form" data-quiz-type="mapa">';

        // Container for player map + task list. JS handles rendering.
        echo '<div id="ek-mapa-container" data-region="' . esc_attr( $region ) . '" data-detail="' . esc_attr( $player_detail ) . '">';
        echo '<div id="ek-mapa-tasks" class="ek-mapa-tasks"></div>';
        echo '<div id="ek-mapa-map" class="ek-mapa-map"></div>';
        echo '</div>';

        // Hidden inputs per task — JS writes lat/lon on map click. Pre-fill with prev review if any.
        foreach ( $selected as $idx => $p ) {
            $hn = $idx + 1;
            $prev_lat = $is_review ? ( isset( $_POST['prev_mapa' . $hn . '_lat'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_lat'] ) ) : '' ) : '';
            $prev_lon = $is_review ? ( isset( $_POST['prev_mapa' . $hn . '_lon'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_lon'] ) ) : '' ) : '';
            $prev_pin = $is_review ? ( isset( $_POST['prev_mapa' . $hn . '_pin'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_pin'] ) ) : '' ) : '';
            $prev_correct = $is_review ? ( isset( $_POST['prev_mapa' . $hn . '_correct'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_correct'] ) ) : '' ) : '';
            echo '<input type="hidden" name="mapa' . $hn . '_lat" id="ek-mapa-lat-' . $hn . '" value="' . $prev_lat . '">';
            echo '<input type="hidden" name="mapa' . $hn . '_lon" id="ek-mapa-lon-' . $hn . '" value="' . $prev_lon . '">';
            echo '<input type="hidden" name="mapa' . $hn . '_pin" id="ek-mapa-pin-' . $hn . '" value="' . ( $prev_pin !== '' ? $prev_pin : esc_attr( $p['id'] ) ) . '">';
            if ( $prev_correct !== '' ) {
                echo '<input type="hidden" name="prev_mapa' . $hn . '_correct" value="' . $prev_correct . '">';
            }
        }

        // Pass tasks to JS
        echo '<script>window.ekMapaTasks = ' . wp_json_encode( $tasks_for_js ) . ';</script>';

        // Standard hidden inputs
        echo '<input type="hidden" name="team" value="' . esc_attr( $team_code ) . '">';
        echo '<input type="hidden" name="user" value="' . esc_attr( $user_code ) . '">';
        echo '<input type="hidden" name="akcia" value="' . esc_attr( $akcia_code ) . '">';
        echo '<input type="hidden" name="set" value="' . esc_attr( $serialized_set ) . '">';
        echo '<input type="hidden" name="set_sig" value="' . esc_attr( $this->sign_question_set( $serialized_set, $akcia_code ) ) . '">';

        $gc_id     = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
        $gc_cp     = isset( $_GET['cp'] ) ? sanitize_text_field( $_GET['cp'] ) : '';
        $gc_return = isset( $_GET['return_url'] ) ? esc_url_raw( $_GET['return_url'] ) : '';
        if ( ! empty( $gc_id ) && ! empty( $gc_cp ) ) {
            echo '<input type="hidden" name="gc_id" value="' . esc_attr( $gc_id ) . '">';
            echo '<input type="hidden" name="gc_cp" value="' . esc_attr( $gc_cp ) . '">';
            echo '<input type="hidden" name="gc_return" value="' . esc_attr( $gc_return ) . '">';
        }

        echo '<button type="submit" class="ek-quiz-submit">Odoslať odpovede</button>';
        echo '</form>';
        echo '</div>'; // .ek-quiz-content
        echo '</div>'; // .ek-quiz

        // Persist question set for retry reuse (first load only)
        if ( ! $question_set_exists ) {
            $this->write_question_set_to_db( $serialized_set, $akcia_code, 'mapa', $user_code, $team_code );
        }
    }
}
