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

        // ── Multi-mapa routing: requires ?mq=<slug> URL param ────────────
        $mq_slug = isset( $_GET['mq'] ) ? sanitize_key( $_GET['mq'] ) : '';
        if ( $mq_slug === '' ) {
            echo '<p class="ek-quiz-error">Chýba parameter <code>mq</code> (identifikátor mapového kvízu) v URL. Kontaktuj administrátora pre správny link.</p>';
            return;
        }
        $sub_quiz = $this->find_mapa_sub_quiz( $mq_slug );
        if ( ! $sub_quiz ) {
            echo '<p class="ek-quiz-error">Mapový kvíz s týmto identifikátorom neexistuje pre tento event.</p>';
            return;
        }
        // Cache pre parent helpers (check_number_of_tries, show_answer, render_tries_banner)
        $this->_current_mapa_sub_quiz = $sub_quiz;

        // ── ?reset=1 — DELETE záznamy pre tento sub-kvíz IBA (filter cez mq slug v question_set JSON)
        $just_reset = false;
        if ( ! empty( $_GET['reset'] ) && current_user_can( 'manage_options' ) ) {
            $this->mapa_reset_sub_quiz_rows( $akcia_code, $user_code, $team_code, $mq_slug );
            $just_reset = true;
        }

        // Show entry form if needed
        if ( ! empty( $sub_quiz['show_entry_form'] ) ) {
            if ( ! empty( $this->cAkcia->all_quizes_settings['select_from_teams_array'] ) && empty( $team_code ) ) {
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;
                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links( $att, 'mapa' );
                die;
            }
        }

        // Tries check — multi-mapa aware (per sub-kvíz cez question_set LIKE filter)
        $check_result = $this->mapa_check_tries( $user_code, $akcia_code, $team_code, $mq_slug, $sub_quiz );
        if ( $check_result !== true ) return;

        $template_id = (int) $sub_quiz['template_id'];
        if ( $template_id <= 0 ) {
            echo '<p class="ek-quiz-error">Sub-kvíz nemá nastavenú šablónu.</p>';
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
        $quiz_type     = get_post_meta( $template_id, '_mapquiz_quiz_type', true ) ?: 'pin';
        if ( ! in_array( $quiz_type, array( 'pin', 'area', 'line' ), true ) ) $quiz_type = 'pin';

        // Build pool podľa quiz_type:
        //   pin       → pole {id, name, hint, description, photo_id, lat, lon}
        //   area/line → pole {id, name} (id = feature name z bundleovaného datasetu)
        $all_pins = array();
        if ( $quiz_type === 'pin' ) {
            $pins_json = get_post_meta( $template_id, '_mapquiz_pins', true );
            $all_pins  = is_string( $pins_json ) ? json_decode( $pins_json, true ) : array();
            if ( ! is_array( $all_pins ) ) $all_pins = array();
        } else {
            // area/line — admin si vybral dataset + checkbox feature pool
            $dataset_slug = get_post_meta( $template_id, '_mapquiz_dataset_slug', true );
            $pool_json    = get_post_meta( $template_id, '_mapquiz_feature_pool', true );
            $pool         = is_string( $pool_json ) ? json_decode( $pool_json, true ) : array();
            if ( ! is_array( $pool ) ) $pool = array();
            foreach ( $pool as $name ) {
                $name = (string) $name;
                if ( $name !== '' ) $all_pins[] = array( 'id' => $name, 'name' => $name );
            }
        }

        if ( count( $all_pins ) === 0 ) {
            if ( $quiz_type === 'pin' ) {
                $err = 'Šablóna „' . esc_html( $template->post_title ) . '" nemá žiadne piny. Admin musí v editore šablóny pridať aspoň jeden pin.';
            } else {
                $err = 'Šablóna „' . esc_html( $template->post_title ) . '" nemá vybraté žiadne features v pool. Admin musí v editore šablóny vybrať dataset a zaškrtnúť aspoň jednu feature.';
            }
            echo '<p class="ek-quiz-error">' . $err . '</p>';
            return;
        }

        // Determine which pins to use (reuse from prior session or generate new)
        $question_set_exists = $this->mapa_check_set_exists( $akcia_code, $user_code, $team_code, $mq_slug );
        $regenerate_on_retry = ! empty( $sub_quiz['new_questions_on_retry'] );
        $treat_as_new        = ! $question_set_exists || $regenerate_on_retry;

        $count_in_set = isset( $sub_quiz['pocet_otazok_v_sete'] ) ? max( 1, (int) $sub_quiz['pocet_otazok_v_sete'] ) : 10;
        $count_in_set = min( $count_in_set, count( $all_pins ) );

        if ( ! $treat_as_new ) {
            // Reuse stored set, ale len ak:
            //   1) VŠETKY IDs existujú v aktuálnom poole (po zmene quiz_type/template by neexistovali)
            //   2) Počet uložených IDs sa zhoduje s aktuálnym pocet_otazok_v_sete
            //      (po zmene počtu by stary set bol nesprávnej veľkosti)
            // Inak treat_as_new — regeneruje sa fresh sada s aktuálnymi nastaveniami.
            $stored_ids = is_array( $this->questions_set ) ? $this->questions_set : array();
            $selected = array();
            $all_match = ! empty( $stored_ids ) && count( $stored_ids ) === $count_in_set;
            if ( $all_match ) {
                foreach ( $stored_ids as $pid ) {
                    $found = null;
                    foreach ( $all_pins as $p ) {
                        if ( (string) $p['id'] === (string) $pid ) { $found = $p; break; }
                    }
                    if ( $found ) { $selected[] = $found; }
                    else { $all_match = false; break; }
                }
            }
            if ( ! $all_match ) {
                $treat_as_new = true;
            } else {
                $selected_pin_ids = $stored_ids;
            }
        }

        if ( $treat_as_new ) {
            $shuffled = $all_pins;
            shuffle( $shuffled );
            $selected = array_slice( $shuffled, 0, $count_in_set );
            $selected_pin_ids = array_map( function( $p ) { return $p['id']; }, $selected );
        }

        if ( empty( $selected ) ) {
            echo '<p class="ek-quiz-error">Pri výbere pinov nastala chyba (prázdny set).</p>';
            return;
        }

        // Build sanitized data for JS — strip out solution coords (lat/lon for pin mode)
        // alebo feature_id len v hidden inputs (pre area/line — to je correct ID, treba
        // ho server-side ale klient ho aj tak musí poznať aby vedel aký je task. Anti-cheat
        // tu je obmedzená — pre feature mód má každá úloha viditeľný "Nájdi: Dunaj").
        $tasks_for_js = array();
        foreach ( $selected as $p ) {
            $task = array(
                'id'   => (string) $p['id'],
                'name' => (string) ( $p['name'] ?? '' ),
            );
            if ( $quiz_type === 'pin' ) {
                $task['hint']        = (string) ( $p['hint'] ?? '' );
                $task['description'] = (string) ( $p['description'] ?? '' );
                $task['photo_url']   = '';
                if ( ! empty( $p['photo_id'] ) ) {
                    $url = wp_get_attachment_image_url( (int) $p['photo_id'], 'medium' );
                    if ( $url ) $task['photo_url'] = $url;
                }
            }
            $tasks_for_js[] = $task;
        }

        $serialized_set = wp_json_encode( $selected_pin_ids );

        $url = home_url( '/mapa-quiz-dynamic-evaluation/' );

        $is_review = ! empty( $_POST['prev_review'] );

        echo '<div class="ek-quiz ek-quiz--mapa">';
        echo '<div class="ek-quiz-content ek-quiz-content--mapa">';
        echo '<h1 class="ek-quiz-title">Mapový kvíz: ' . esc_html( $template->post_title ) . '</h1>';
        echo '<p class="ek-quiz-subtitle">Označ na mape miesta uvedené v zozname.</p>';
        if ( $just_reset ) {
            echo '<div class="ek-mapa-restored-hint" style="background:rgba(76,175,80,0.18);border-color:rgba(76,175,80,0.4)">🔄 <strong>Reset prebehol.</strong> Zmazané všetky predošlé pokusy a uložený set úloh pre tohto hráča/team v tomto evente. Začínaš od nuly.</div>';
        }
        if ( current_user_can( 'manage_options' ) ) {
            $reset_url = add_query_arg( 'reset', '1' );
            echo '<div style="margin-bottom:12px;font-size:12px;text-align:right">';
            echo '<a href="' . esc_url( $reset_url ) . '" style="color:#888;text-decoration:none" onclick="try{Object.keys(localStorage).filter(function(k){return k.indexOf(\'ek_autosave:mapa:\')===0}).forEach(function(k){localStorage.removeItem(k)});}catch(e){};return true;">🔧 Reset (admin only) — vymaže DB záznamy + localStorage pre tento event</a>';
            echo '</div>';
        }
        // Scoring info — friendly text o tom ako sa kvíz vyhodnocuje (max body,
        // počet úloh, scoring metóda podľa quiz_type, prah na kód, počet pokusov).
        $form_max_points_override = isset( $sub_quiz['max_points_override'] ) ? trim( (string) $sub_quiz['max_points_override'] ) : '';
        $form_max_points_template = (int) get_post_meta( $template_id, '_mapquiz_max_points', true );
        $form_max_points          = $form_max_points_override !== '' ? (int) $form_max_points_override : $form_max_points_template;
        if ( $form_max_points <= 0 ) $form_max_points = 100;
        $form_max_per_task = $count_in_set > 0 ? ( $form_max_points / $count_in_set ) : 0;
        $this->render_scoring_info( 'mapa', $sub_quiz, 'form', array(
            'mapa_type'         => $quiz_type,
            'mapa_max_per_task' => $form_max_per_task,
            'mapa_task_count'   => $count_in_set,
        ) );
        $this->render_tries_remaining_banner( 'mapa' );
        if ( $is_review ) {
            echo '<div class="ek-review-banner">📝 Vaše predchádzajúce odpovede sú vyznačené — <strong style="color:#6dd58c">zelené</strong> boli v tieri (úspech), <strong style="color:#ff6b6b">červené</strong> mimo. Klikni znova na ktorýkoľvek pin a presuň.</div>';
        }

        echo '<form action="' . esc_url( $url ) . '" method="post" class="ek-quiz-form" data-quiz-type="mapa">';

        // Overlay vodítka (mestá / kraje / rieky) — z template settings
        // Passujeme cely overlays object (save handler ho už striktne whitelistnul
        // per-region cez registry). JS si vyberie flagy ktoré pozná — extensible
        // pre nové overlays bez zmien v tomto súbore.
        $overlays_json = get_post_meta( $template_id, '_mapquiz_overlays', true );
        $overlays      = is_string( $overlays_json ) && $overlays_json !== '' ? json_decode( $overlays_json, true ) : array();
        if ( ! is_array( $overlays ) ) $overlays = array();
        $overlays_attr = wp_json_encode( $overlays );

        // Container for player map + task list. JS handles rendering.
        // Dataset slug pre area/line + singular label pre sidebar „Nájdi <singular>: X"
        $dataset_slug_attr = get_post_meta( $template_id, '_mapquiz_dataset_slug', true );
        $features_source   = get_post_meta( $template_id, '_mapquiz_features_source', true ) ?: 'bundle';
        $dataset_singular = '';
        if ( $features_source === 'custom' ) {
            // Pre custom feature collection nemáme registry → use generic singular podľa quiz_type
            $dataset_singular = ( $quiz_type === 'line' ? 'líniu' : ( $quiz_type === 'area' ? 'oblasť' : '' ) );
        } elseif ( $dataset_slug_attr && class_exists( 'Eventkviz_MapQuiz_Datasets' ) ) {
            $ds_def = Eventkviz_MapQuiz_Datasets::get( $dataset_slug_attr );
            if ( $ds_def && ! empty( $ds_def['singular'] ) ) $dataset_singular = $ds_def['singular'];
        }
        echo '<div id="ek-mapa-container" data-region="' . esc_attr( $region ) . '" data-detail="' . esc_attr( $player_detail ) . '" data-overlays="' . esc_attr( $overlays_attr ) . '" data-quiz-type="' . esc_attr( $quiz_type ) . '" data-dataset="' . esc_attr( $dataset_slug_attr ) . '" data-features-source="' . esc_attr( $features_source ) . '" data-singular="' . esc_attr( $dataset_singular ) . '">';
        echo '<div id="ek-mapa-tasks" class="ek-mapa-tasks"></div>';
        echo '<div id="ek-mapa-map" class="ek-mapa-map"></div>';
        echo '</div>';

        // Pre custom features source — passni FeatureCollection inline ako JS global
        // (vyhne sa fetch HTTP requestu na bundle file). JS si to vyzdvihne v
        // loadFeatureLayer() namiesto fetch.
        if ( $features_source === 'custom' ) {
            $custom_json = get_post_meta( $template_id, '_mapquiz_custom_features', true );
            if ( empty( $custom_json ) ) $custom_json = '{"type":"FeatureCollection","features":[]}';
            echo '<script>window.ekMapaCustomFeatures = ' . $custom_json . ';</script>';
        }

        // Hidden inputs per task. Pin mode: lat/lon. Feature mode: feature_id (hráčov výber).
        // mapaN_pin obsahuje vždy CORRECT id (task ID) — eval to porovnáva so guess.
        foreach ( $selected as $idx => $p ) {
            $hn = $idx + 1;
            $prev_pin = $is_review && isset( $_POST['prev_mapa' . $hn . '_pin'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_pin'] ) ) : '';
            $prev_correct = $is_review && isset( $_POST['prev_mapa' . $hn . '_correct'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_correct'] ) ) : '';
            echo '<input type="hidden" name="mapa' . $hn . '_pin" id="ek-mapa-pin-' . $hn . '" value="' . ( $prev_pin !== '' ? $prev_pin : esc_attr( $p['id'] ) ) . '">';

            if ( $quiz_type === 'pin' ) {
                $prev_lat = $is_review && isset( $_POST['prev_mapa' . $hn . '_lat'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_lat'] ) ) : '';
                $prev_lon = $is_review && isset( $_POST['prev_mapa' . $hn . '_lon'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_lon'] ) ) : '';
                echo '<input type="hidden" name="mapa' . $hn . '_lat" id="ek-mapa-lat-' . $hn . '" value="' . $prev_lat . '">';
                echo '<input type="hidden" name="mapa' . $hn . '_lon" id="ek-mapa-lon-' . $hn . '" value="' . $prev_lon . '">';
            } else {
                // area/line — feature_id (názov vybranej feature)
                $prev_feat = $is_review && isset( $_POST['prev_mapa' . $hn . '_feature'] ) ? esc_attr( wp_unslash( $_POST['prev_mapa' . $hn . '_feature'] ) ) : '';
                echo '<input type="hidden" name="mapa' . $hn . '_feature" id="ek-mapa-feature-' . $hn . '" value="' . $prev_feat . '">';
            }
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
        echo '<input type="hidden" name="mq" value="' . esc_attr( $mq_slug ) . '">';
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

        // Persist question set for retry reuse. Aj keď question_set_exists ale obsah je
        // mismatch (admin zmenil quiz_type alebo pool), prepíšeme aktuálnym setom cez UPDATE.
        if ( ! $question_set_exists ) {
            $this->mapa_write_set_to_db( $serialized_set, $akcia_code, $user_code, $team_code, $mq_slug );
        } elseif ( $treat_as_new ) {
            // Stored set was stale → rewrite. write_question_set_to_db sa volá v UPDATE móde
            // ak existing row → update, inak insert. Funguje rovnako pre obidva prípady.
            $this->mapa_write_set_to_db( $serialized_set, $akcia_code, $user_code, $team_code, $mq_slug );
        }
    }
}


/**
 * Multi-mapa helpers — pridáné do parent class via trait alebo bezposredne.
 * Zatiaľ duplikujem v oboch (form + eval) ako static methods cez include.
 * Lepšie: vytvoriť trait `Eventkviz_MapaTrait` — TODO refactor.
 *
 * Pre teraz: pridám helpers na koniec MapaForm class (instance methods),
 * MapaEval ich tiež môže volať (lebo extend rovnaký parent), len musí mať
 * vlastné helpers. Aby som neduplikoval, dajme do Eventkviz_Quiz_Class
 * (parent) — tam ich už majú obe classes k dispozícii.
 */

class Eventkviz_MapaEval_Quiz_Class extends Eventkviz_Quiz_Class {

    public function __construct() {
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'eval_mapa_quiz_dynamic', array( $plugin, 'eventkviz_eval_mapa_quiz' ) );
    }

    /**
     * Haversine distance in kilometers between two lat/lon pairs.
     */
    public static function haversine_km( $lat1, $lon1, $lat2, $lon2 ) {
        $earth = 6371.0; // km
        $lat1r = deg2rad( $lat1 );
        $lat2r = deg2rad( $lat2 );
        $dLat  = deg2rad( $lat2 - $lat1 );
        $dLon  = deg2rad( $lon2 - $lon1 );
        $a = sin( $dLat / 2 ) ** 2 + cos( $lat1r ) * cos( $lat2r ) * sin( $dLon / 2 ) ** 2;
        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
        return $earth * $c;
    }

    /**
     * Given a distance in km and a sorted tier list, return the percent of
     * the per-task max points the player earns (0 if outside all tiers).
     * Tiers MUST be pre-sorted ascending by maxKm (admin save handler sorts).
     */
    public static function percent_for_distance( $distance_km, $tiers ) {
        foreach ( $tiers as $tier ) {
            $max_km = isset( $tier['maxKm'] ) ? (float) $tier['maxKm'] : 0;
            $pct    = isset( $tier['percent'] ) ? (float) $tier['percent'] : 0;
            if ( $distance_km <= $max_km ) return $pct;
        }
        return 0.0;
    }

    public function eventkviz_eval_mapa_quiz( $atts = '' ) {
        global $pocet_pokusov_reached;

        $akcia = isset( $_POST['akcia'] ) ? sanitize_text_field( wp_unslash( $_POST['akcia'] ) ) : '';
        if ( $akcia === '' ) {
            echo '<p class="ek-quiz-error">Chýba parameter akcia.</p>';
            return;
        }
        $this->load_basic_event_settings( $akcia );

        // GeoChallenge per-player scoping (POST gc_cp / cookie fallback)
        $gc_override = $this->geo_user_code( 'eval' );

        $user = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';
        $team = isset( $_POST['team'] ) ? sanitize_text_field( wp_unslash( $_POST['team'] ) ) : '';
        if ( $gc_override !== '' ) $user = $gc_override;

        $serialized_set = isset( $_POST['set'] ) ? wp_unslash( $_POST['set'] ) : '';
        $set_sig        = isset( $_POST['set_sig'] ) ? wp_unslash( $_POST['set_sig'] ) : '';
        if ( ! $this->verify_question_set_signature( $serialized_set, $akcia, $set_sig ) ) {
            echo '<p class="ek-quiz-error">Neplatný podpis odoslaných údajov — formulár bol pravdepodobne zmenený. Skúste znova zo začiatku.</p>';
            return;
        }

        $selected_pin_ids = json_decode( $serialized_set, true );
        if ( ! is_array( $selected_pin_ids ) || empty( $selected_pin_ids ) ) {
            echo '<p class="ek-quiz-error">Set úloh je prázdny alebo poškodený.</p>';
            return;
        }

        // Multi-mapa: read mq slug z POST (form ho sem posiela cez hidden input)
        $mq_slug = isset( $_POST['mq'] ) ? sanitize_key( wp_unslash( $_POST['mq'] ) ) : '';
        if ( $mq_slug === '' ) {
            echo '<p class="ek-quiz-error">Chýba parameter mq.</p>';
            return;
        }
        $sub_quiz = $this->find_mapa_sub_quiz( $mq_slug );
        if ( ! $sub_quiz ) {
            echo '<p class="ek-quiz-error">Mapový kvíz s týmto identifikátorom neexistuje.</p>';
            return;
        }
        $this->_current_mapa_sub_quiz = $sub_quiz;

        // Tries check per sub-kvíz
        $check_result = $this->mapa_check_tries( $user, $akcia, $team, $mq_slug, $sub_quiz );
        if ( $check_result !== true ) return;

        // Load template pins from server side (authoritative source of correct coords)
        $template_id = (int) $sub_quiz['template_id'];
        $template    = $template_id > 0 ? get_post( $template_id ) : null;
        if ( ! $template || $template->post_type !== 'mapquiz_template' ) {
            echo '<p class="ek-quiz-error">Šablóna mapového kvízu nenájdená.</p>';
            return;
        }
        $quiz_type = get_post_meta( $template_id, '_mapquiz_quiz_type', true ) ?: 'pin';
        if ( ! in_array( $quiz_type, array( 'pin', 'area', 'line' ), true ) ) $quiz_type = 'pin';

        // Build authoritative pin/feature map: id → record. Pre pin = lat/lon; pre
        // area/line = id (= správna feature name) — eval len porovnáva guess vs id.
        $pin_by_id = array();
        if ( $quiz_type === 'pin' ) {
            $pins_json = get_post_meta( $template_id, '_mapquiz_pins', true );
            $all_pins  = is_string( $pins_json ) ? json_decode( $pins_json, true ) : array();
            if ( ! is_array( $all_pins ) ) $all_pins = array();
            foreach ( $all_pins as $p ) {
                if ( ! empty( $p['id'] ) ) $pin_by_id[ (string) $p['id'] ] = $p;
            }
        } else {
            // Pre feature mode používame `selected_pin_ids` ako autoritatívny zoznam
            // — čo POST poslal v 'set' pole sa overuje cez set_sig vyššie. Stačí teda
            //   id → {id, name} bez ďalšieho geom lookup.
            foreach ( $selected_pin_ids as $pid ) {
                $pid = (string) $pid;
                $pin_by_id[ $pid ] = array( 'id' => $pid, 'name' => $pid );
            }
        }

        // Resolve scoring config (per-sub-kvíz override → template default)
        $max_points_override   = isset( $sub_quiz['max_points_override'] ) ? trim( (string) $sub_quiz['max_points_override'] ) : '';
        $max_points_template   = (int) get_post_meta( $template_id, '_mapquiz_max_points', true );
        $max_points            = $max_points_override !== '' ? (int) $max_points_override : $max_points_template;
        if ( $max_points <= 0 ) $max_points = 100;

        $tiers_override_json = isset( $sub_quiz['score_tiers_override'] ) ? trim( (string) $sub_quiz['score_tiers_override'] ) : '';
        $tiers_template_json = (string) get_post_meta( $template_id, '_mapquiz_score_tiers', true );
        $tiers_json          = $tiers_override_json !== '' ? $tiers_override_json : $tiers_template_json;
        $tiers               = is_string( $tiers_json ) ? json_decode( $tiers_json, true ) : array();
        if ( ! is_array( $tiers ) || empty( $tiers ) ) {
            $tiers = array(
                array( 'maxKm' => 5,  'percent' => 100 ),
                array( 'maxKm' => 10, 'percent' => 75 ),
                array( 'maxKm' => 20, 'percent' => 50 ),
                array( 'maxKm' => 40, 'percent' => 25 ),
            );
        }

        $task_count       = count( $selected_pin_ids );
        $max_per_task     = $task_count > 0 ? ( $max_points / $task_count ) : 0;
        $gained_credits   = 0;
        $previous_state   = array(); // pre retry button
        $tasks_for_js     = array(); // pre review map JS
        $per_task_results = array();

        for ( $i = 0; $i < $task_count; $i++ ) {
            $hn      = $i + 1;
            $pin_key = 'mapa' . $hn . '_pin';
            $pin_id  = isset( $_POST[ $pin_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $pin_key ] ) ) : (string) $selected_pin_ids[ $i ];

            $pin = isset( $pin_by_id[ $pin_id ] ) ? $pin_by_id[ $pin_id ] : null;
            if ( ! $pin ) continue;

            if ( $quiz_type === 'pin' ) {
                $guess_lat_raw = isset( $_POST[ 'mapa' . $hn . '_lat' ] ) ? trim( (string) wp_unslash( $_POST[ 'mapa' . $hn . '_lat' ] ) ) : '';
                $guess_lon_raw = isset( $_POST[ 'mapa' . $hn . '_lon' ] ) ? trim( (string) wp_unslash( $_POST[ 'mapa' . $hn . '_lon' ] ) ) : '';
                $is_answered   = ( $guess_lat_raw !== '' && $guess_lon_raw !== '' );
                $guess_lat     = $is_answered ? (float) $guess_lat_raw : null;
                $guess_lon     = $is_answered ? (float) $guess_lon_raw : null;
                $correct_lat   = (float) $pin['lat'];
                $correct_lon   = (float) $pin['lon'];
                $distance_km   = $is_answered ? self::haversine_km( $guess_lat, $guess_lon, $correct_lat, $correct_lon ) : null;
                $percent       = ( $is_answered && $distance_km !== null ) ? self::percent_for_distance( $distance_km, $tiers ) : 0;
                $points        = (int) round( $max_per_task * $percent / 100 );
                $gained_credits += $points;

                $previous_state[ 'prev_mapa' . $hn . '_lat' ]     = $guess_lat_raw;
                $previous_state[ 'prev_mapa' . $hn . '_lon' ]     = $guess_lon_raw;
                $previous_state[ 'prev_mapa' . $hn . '_pin' ]     = $pin_id;
                $previous_state[ 'prev_mapa' . $hn . '_correct' ] = $points > 0 ? '1' : '0';

                $per_task_results[] = array(
                    'idx' => $i, 'hn' => $hn, 'pin' => $pin,
                    'is_answered' => $is_answered, 'guess_lat' => $guess_lat, 'guess_lon' => $guess_lon,
                    'distance_km' => $distance_km, 'percent' => $percent, 'points' => $points,
                    'max_per_task' => $max_per_task,
                );
                $tasks_for_js[] = array(
                    'id' => (string) $pin['id'], 'name' => (string) ( $pin['name'] ?? '' ),
                    'correct_lat' => $correct_lat, 'correct_lon' => $correct_lon,
                    'guess_lat' => $is_answered ? $guess_lat : null, 'guess_lon' => $is_answered ? $guess_lon : null,
                    'distance_km' => $distance_km, 'points' => $points,
                );
            } else {
                // area/line — binárne hodnotenie. Hráč pošle mapaN_feature = názov vybranej feature.
                $guess_feature = isset( $_POST[ 'mapa' . $hn . '_feature' ] ) ? trim( (string) wp_unslash( $_POST[ 'mapa' . $hn . '_feature' ] ) ) : '';
                $is_answered   = $guess_feature !== '';
                $is_correct    = ( $is_answered && $guess_feature === $pin_id );
                $points        = $is_correct ? (int) round( $max_per_task ) : 0;
                $gained_credits += $points;

                $previous_state[ 'prev_mapa' . $hn . '_feature' ] = $guess_feature;
                $previous_state[ 'prev_mapa' . $hn . '_pin' ]     = $pin_id;
                $previous_state[ 'prev_mapa' . $hn . '_correct' ] = $is_correct ? '1' : '0';

                $per_task_results[] = array(
                    'idx' => $i, 'hn' => $hn, 'pin' => $pin,
                    'is_answered' => $is_answered, 'guess_feature' => $guess_feature,
                    'correct_feature' => $pin_id, 'is_correct' => $is_correct,
                    'points' => $points, 'max_per_task' => $max_per_task,
                );
                $tasks_for_js[] = array(
                    'id' => $pin_id, 'name' => $pin_id,
                    'correct_feature' => $pin_id,
                    'guess_feature'   => $is_answered ? $guess_feature : null,
                    'is_correct'      => $is_correct, 'points' => $points,
                );
            }
        }

        // --- Render ---
        $region        = get_post_meta( $template_id, '_mapquiz_region', true ) ?: 'slovakia';
        $player_detail = get_post_meta( $template_id, '_mapquiz_player_detail', true ) ?: 'outline-only';

        echo '<div class="ek-quiz ek-quiz--mapa">';
        echo '<div class="ek-quiz-content ek-quiz-content--mapa">';
        echo '<h1 class="ek-quiz-title">Vyhodnotenie mapového kvízu: ' . esc_html( $template->post_title ) . '</h1>';

        // Overlay vodítka (rovnaké ako form) — pomáhajú hráčovi orientovať sa pri review
        // Cely overlays object — sanitization už spravil save handler (whitelist).
        $overlays_json = get_post_meta( $template_id, '_mapquiz_overlays', true );
        $overlays      = is_string( $overlays_json ) && $overlays_json !== '' ? json_decode( $overlays_json, true ) : array();
        if ( ! is_array( $overlays ) ) $overlays = array();
        $overlays_attr = wp_json_encode( $overlays );

        // Review map container — JS reads window.ekMapaReview
        $dataset_slug_attr = get_post_meta( $template_id, '_mapquiz_dataset_slug', true );
        $features_source   = get_post_meta( $template_id, '_mapquiz_features_source', true ) ?: 'bundle';
        $dataset_singular = '';
        if ( $features_source === 'custom' ) {
            $dataset_singular = ( $quiz_type === 'line' ? 'líniu' : ( $quiz_type === 'area' ? 'oblasť' : '' ) );
        } elseif ( $dataset_slug_attr && class_exists( 'Eventkviz_MapQuiz_Datasets' ) ) {
            $ds_def = Eventkviz_MapQuiz_Datasets::get( $dataset_slug_attr );
            if ( $ds_def && ! empty( $ds_def['singular'] ) ) $dataset_singular = $ds_def['singular'];
        }
        echo '<div id="ek-mapa-container" class="ek-mapa-review" data-region="' . esc_attr( $region ) . '" data-detail="' . esc_attr( $player_detail ) . '" data-overlays="' . esc_attr( $overlays_attr ) . '" data-quiz-type="' . esc_attr( $quiz_type ) . '" data-dataset="' . esc_attr( $dataset_slug_attr ) . '" data-features-source="' . esc_attr( $features_source ) . '" data-singular="' . esc_attr( $dataset_singular ) . '" data-review="1">';
        echo '<div id="ek-mapa-tasks" class="ek-mapa-tasks"></div>';
        echo '<div id="ek-mapa-map" class="ek-mapa-map"></div>';
        echo '</div>';
        echo '<script>window.ekMapaReview = ' . wp_json_encode( $tasks_for_js ) . ';</script>';
        // Custom features inline (rovnako ako vo form mode)
        if ( $features_source === 'custom' ) {
            $custom_json = get_post_meta( $template_id, '_mapquiz_custom_features', true );
            if ( empty( $custom_json ) ) $custom_json = '{"type":"FeatureCollection","features":[]}';
            echo '<script>window.ekMapaCustomFeatures = ' . $custom_json . ';</script>';
        }

        // Textual per-task summary (independent of JS)
        echo '<div class="ek-mapa-eval-list">';
        foreach ( $per_task_results as $r ) {
            $hn       = $r['hn'];
            $pin_name = isset( $r['pin']['name'] ) ? $r['pin']['name'] : '';
            echo '<div class="ek-question">';
            echo '<div class="ek-question-header">';
            echo '<span class="ek-question-num">' . esc_html( $hn ) . '</span>';
            echo '<span class="ek-question-label">' . esc_html( $pin_name ) . '</span>';
            echo '</div>';

            if ( $quiz_type === 'pin' ) {
                if ( ! $r['is_answered'] ) {
                    $this->show_answer( 'Hráč neoznačil miesto na mape — 0 bodov.', 'mapa', 'eventkviz_standard_answer', 'user_result' );
                } else {
                    $dist_txt = number_format( (float) $r['distance_km'], 2, '.', ' ' );
                    if ( $r['points'] > 0 ) {
                        $msg = '✅ Vzdialenosť od správneho miesta: <strong>' . $dist_txt . ' km</strong> — hráč získava <strong>+' . $r['points'] . '</strong> bodov (' . (int) $r['percent'] . ' % z ' . number_format( (float) $r['max_per_task'], 1, '.', ' ' ) . ').';
                        $this->show_answer( $msg, 'mapa', 'eventkviz_standard_answer', 'user_result' );
                    } else {
                        $msg = '❌ Vzdialenosť: <strong>' . $dist_txt . ' km</strong> — mimo všetkých stupňov, 0 bodov.';
                        $this->show_answer( $msg, 'mapa', 'eventkviz_standard_answer', 'user_result' );
                    }
                }
            } else {
                // area/line — binárne hodnotenie
                if ( ! $r['is_answered'] ) {
                    $this->show_answer( 'Hráč neoznačil žiadnu ' . ( $quiz_type === 'line' ? 'feature' : 'oblasť' ) . ' — 0 bodov.', 'mapa', 'eventkviz_standard_answer', 'user_result' );
                } elseif ( $r['is_correct'] ) {
                    $msg = '✅ Správne — hráč získava <strong>+' . $r['points'] . '</strong> bodov.';
                    $this->show_answer( $msg, 'mapa', 'eventkviz_standard_answer', 'user_result' );
                } else {
                    $msg = '❌ Hráčov výber: <strong>' . esc_html( $r['guess_feature'] ) . '</strong> — nesprávne, 0 bodov.';
                    $this->show_answer( $msg, 'mapa', 'eventkviz_standard_answer', 'user_result' );
                }
            }

            // Mini-mapa pre vzdelávací účel — ak admin má toggle A
            // („zobraz_spravne_odpovede") ON a odpoveď nie je perfektná:
            //   - feature mode (area/line): wrong/missing → mini-mapa s feature
            //   - pin mode: not top-tier (points < max_per_task) → mini-mapa s pin
            $show_correct_answers = ! empty( $sub_quiz['zobraz_spravne_odpovede'] );
            $needs_mini_map = false;
            if ( $show_correct_answers ) {
                if ( $quiz_type === 'pin' ) {
                    // Pre pin mode: ak hráč nemal full credit (percent < 100), ukáž mini-mapu
                    $needs_mini_map = ! isset( $r['percent'] ) || (float) $r['percent'] < 100;
                } else {
                    $needs_mini_map = empty( $r['is_correct'] );
                }
            }
            if ( $needs_mini_map ) {
                $correct_name = isset( $r['pin']['name'] ) ? $r['pin']['name'] : '';
                if ( $correct_name !== '' ) {
                    echo '<div class="ek-mapa-mini-wrap">';
                    // Label nad mini-mapou ukazujeme iba pre feature mode (v pin móde
                    // sa „Správna lokalita: X" + photo + description vykreslí cez
                    // show_answer nižšie — duplikát by mätol).
                    if ( $quiz_type !== 'pin' ) {
                        echo '<div class="ek-mapa-mini-label">🎯 Správna lokalita: <strong>' . esc_html( $correct_name ) . '</strong></div>';
                    }
                    if ( $quiz_type === 'pin' ) {
                        $plat = isset( $r['pin']['lat'] ) ? (float) $r['pin']['lat'] : 0;
                        $plon = isset( $r['pin']['lon'] ) ? (float) $r['pin']['lon'] : 0;
                        echo '<div class="ek-mapa-mini" data-pin-lat="' . esc_attr( $plat ) . '" data-pin-lon="' . esc_attr( $plon ) . '" data-region="' . esc_attr( $region ) . '" data-quiz-type="pin"></div>';
                    } else {
                        // Pre area/line: aj guess_feature (= čo hráč klikol nesprávne),
                        // aby mini-mapa nakreslila aj červený polygón / línku hráčovho výberu
                        // vedľa zeleného správneho.
                        $guess_feat = isset( $r['guess_feature'] ) ? (string) $r['guess_feature'] : '';
                        echo '<div class="ek-mapa-mini" data-feature="' . esc_attr( $correct_name ) . '" data-guess-feature="' . esc_attr( $guess_feat ) . '" data-region="' . esc_attr( $region ) . '" data-quiz-type="' . esc_attr( $quiz_type ) . '" data-dataset="' . esc_attr( $dataset_slug_attr ) . '"></div>';
                    }
                    echo '</div>';
                }
            }

            // Vzdelávací box pre PIN mode — popis + foto lokality. Bez „Správna
            // lokalita: X" textu — názov je už v hlavičke úlohy aj v sidebar
            // a duplikoval by sa. Box sa zobrazí len ak existuje description
            // alebo photo (inak nemá čo ukázať).
            if ( $quiz_type === 'pin' ) {
                $desc_txt = isset( $r['pin']['description'] ) ? (string) $r['pin']['description'] : '';
                $photo    = '';
                if ( ! empty( $r['pin']['photo_id'] ) ) {
                    $url = wp_get_attachment_image_url( (int) $r['pin']['photo_id'], 'medium' );
                    if ( $url ) $photo = $url;
                }
                if ( $desc_txt !== '' || $photo !== '' ) {
                    $detail_html = '';
                    if ( $desc_txt !== '' ) $detail_html .= '<div style="font-style:italic">' . esc_html( $desc_txt ) . '</div>';
                    if ( $photo !== '' )    $detail_html .= '<div><img src="' . esc_url( $photo ) . '" alt="" style="max-width:200px;border-radius:6px;margin-top:6px;"></div>';
                    $this->show_answer( $detail_html, 'mapa', 'eventkviz_standard_answer', 'correct_answer' );
                }
            }

            echo '</div>'; // .ek-question
        }
        echo '</div>'; // .ek-mapa-eval-list

        // Friendly scoring info text (eval mode) — human-readable summary
        // ako sa kvíz vyhodnocoval + zostávajúce pokusy. Predchádzajúci verbose
        // box s tiers/percentami bol nahradený jednoduchším textom (user feedback).
        $eval_remaining = isset( $this->zostava_pocet_pokusov ) ? max( 0, (int) $this->zostava_pocet_pokusov - 1 ) : null;
        $this->render_scoring_info( 'mapa', $sub_quiz, 'eval', array(
            'mapa_type'         => $quiz_type,
            'mapa_max_per_task' => $max_per_task,
            'mapa_task_count'   => $task_count,
            'remaining'         => $eval_remaining,
        ) );

        $this->show_total_credits_gained( $gained_credits, $user, $team );

        // Write results to DB (insert — new row per submit, like other quizzes)
        // Write eval result row per sub-kvíz (JSON payload obsahuje mq slug)
        $this->mapa_write_results_to_db( $user, $team, $akcia, $gained_credits, $serialized_set, $mq_slug );

        $this->send_results_by_email( $user, $team, $akcia, $gained_credits, 'mapa' );

        // Threshold check — kód do GeoChallenge / seed sa zobrazí len ak hráč
        // dosiahol min_body_na_postup. Bez prahu (= 0) sa kód zobrazí vždy
        // (legacy správanie pre šablóny bez nakonfigurovaného prahu).
        // Mirror logika z movies/music/knowledge quizov.
        $min_body = isset( $sub_quiz['min_body_na_postup'] ) ? (int) $sub_quiz['min_body_na_postup'] : 0;
        $passed_threshold = ( $min_body <= 0 ) || ( $gained_credits >= $min_body );

        if ( $passed_threshold ) {
            $this->show_seed( $user, $akcia, 'mapa', $team );
            if ( $gained_credits > 0 ) {
                $this->show_geochallenge_return( $gained_credits );
            }
        } else {
            echo '<div class="ek-quiz-message ek-quiz-message--fail" style="margin-top:24px">';
            echo '<p><strong>Nezískali ste dosť bodov na postup.</strong></p>';
            echo '<p>Je potrebné dosiahnuť aspoň <strong>' . esc_html( $min_body ) . '</strong> bodov. Získali ste <strong>' . esc_html( $gained_credits ) . '</strong>.</p>';
            echo '</div>';
        }

        // Vyhodnoť či hráč mal perfektný pokus (žiaden zmysel ponúkať retry).
        //   - pin mode: všetky úlohy v top tier (percent == 100)
        //   - feature mode (area/line): všetky is_correct === true
        $is_perfect_run = ! empty( $per_task_results );
        foreach ( $per_task_results as $r ) {
            if ( $quiz_type === 'pin' ) {
                if ( ! isset( $r['percent'] ) || (float) $r['percent'] < 100 ) { $is_perfect_run = false; break; }
            } else {
                if ( empty( $r['is_correct'] ) ) { $is_perfect_run = false; break; }
            }
        }

        // Retry button — passes previous answers if mark_correctness_on_retry is on
        if ( $is_perfect_run ) {
            // Box štýl ako GeoChallenge return — svetlé pozadie s tmavým textom,
            // dobre čitateľné na ľubovoľnom Elementor backgrounde.
            echo '<div style="margin-top:30px;padding:16px 20px;background:#e8f5e9;border:2px solid #4caf50;border-radius:10px;text-align:center;color:#1b3a23;">';
            echo '<div style="font-size:18px;font-weight:700;color:#1b3a23;">🎉 Perfektný výsledok</div>';
            echo '<div style="font-size:14px;margin-top:4px;color:#1b3a23;">Všetky odpovede správne.</div>';
            echo '</div>';
        } elseif ( empty( $pocet_pokusov_reached ) ) {
            $pocet_pokusov = isset( $sub_quiz['pocet_pokusov'] ) ? (int) $sub_quiz['pocet_pokusov'] : 0;
            // Mirror logic from other quizzes: remaining = pocet - entries_so_far
            // (we just inserted one row; check_number_of_tries above returned true but
            //  we don't have direct entry count here — use $this->zostava_pocet_pokusov - 1
            //  since this submit consumed one try.)
            $remaining = isset( $this->zostava_pocet_pokusov ) ? max( 0, (int) $this->zostava_pocet_pokusov - 1 ) : 0;
            if ( $remaining > 0 ) {
                $mark_on_retry      = ! empty( $sub_quiz['mark_correctness_on_retry'] );
                $new_set_on_retry   = ! empty( $sub_quiz['new_questions_on_retry'] );
                $retry_state        = ( $mark_on_retry && ! $new_set_on_retry ) ? $previous_state : array();
                $retry_url          = $this->build_retry_url( $team, $user, $akcia, '/mapa-quiz/', $mq_slug );
                $label              = 'Opakovať kvíz (zostáva ' . $remaining . ' ' . Eventkviz_Quiz_Class::_n_pokus_label( $remaining ) . ')';
                echo '<div style="margin-top:30px;text-align:center;">';
                $this->render_retry_button( $retry_url, $label, $retry_state );
                echo '</div>';
            } else {
                echo '<p style="margin-top:30px;text-align:center;font-style:italic;">Toto bol váš posledný povolený pokus pre tento kvíz.</p>';
            }
        }

        echo '</div>'; // .ek-quiz-content
        echo '</div>'; // .ek-quiz
    }
}
