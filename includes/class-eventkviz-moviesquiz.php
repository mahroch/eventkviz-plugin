<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_MoviesForm_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {
        
    }


     public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'movies_form_dynamic', array( $plugin, 'eventkviz_movies_form' ) );
    }

    public function eventkviz_movies_form($atts = '') {

        $value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );
        
        $user_code = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' );
        $this->load_basic_event_settings( $akcia_code);

        // GeoChallenge: per-player scoping (URL cp / cookie fallback)
        $gc_user = $this->geo_user_code('form');
        if ($gc_user !== '') $user_code = $gc_user;

        $team_code = $this->set_team_code($user_code, $akcia_code);

         if($this->cAkcia->movies_settings['show_entry_form'] === true){

            if($this->cAkcia->all_quizes_settings['select_from_teams_array'] === true && empty($team_code)) {
                //ukaz vyberovy form 
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;

                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links($att, 'movies');
                die;
            }
        } 


        $check_result = $this->check_number_of_tries($user_code, $akcia_code,'movies',$team_code);
        
        if($check_result === true) {

            $number_of_questions = $this->cAkcia->movies_settings['pocet_otazok_v_sete'];
            $production_counts = isset($this->cAkcia->movies_settings['number_question_in_production']) ? $this->cAkcia->movies_settings['number_question_in_production'] : array();

            // Zisti, ci sa pouziva per-production rozdelenie (aspon 1 produkcia ma > 0)
            $use_per_production = false;
            foreach ($production_counts as $slug => $count) {
                if ((int) $count > 0) { $use_per_production = true; break; }
            }

            $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,'movies',$user_code,$team_code);
            $regenerate_on_retry = !empty($this->cAkcia->movies_settings['new_questions_on_retry']);
            $treat_as_new = !$question_set_exists || $regenerate_on_retry;

            if( $treat_as_new ) {
                if ($use_per_production) {
                    // Vyber otazky per produkcia
                    $selected_ids = array();
                    foreach ($production_counts as $slug => $count) {
                        $count = (int) $count;
                        if ($count <= 0) continue;
                        $args = array(
                            'post_type'   => 'questions-movies',
                            'numberposts' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'production',
                                    'field' => 'slug',
                                    'terms' => $slug
                                )
                            )
                        );
                        $posts_for_production = get_posts( $args );
                        if (!empty($posts_for_production)) {
                            shuffle($posts_for_production);
                            $picked = array_slice($posts_for_production, 0, min($count, count($posts_for_production)));
                            foreach ($picked as $p) {
                                $selected_ids[] = $p->ID;
                            }
                        }
                    }
                    shuffle($selected_ids);
                    $this->questions_set = $selected_ids;
                    $number_of_questions = count($selected_ids);
                    $available_questions = null; // nepotrebujeme
                } else {
                    // Vsetky produkcie su 0 — vyber nahodne zo vsetkych filmov
                    $args = array(
                        'post_type'   => 'questions-movies',
                        'numberposts' => -1,
                    );
                    $available_questions = get_posts( $args );
                    $number_of_available_questions = count($available_questions)-1;
                    $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $number_of_questions);
                }
            }

            $url = home_url('/movies-quiz-dynamic-evaluation/');

            $is_review = !empty($_POST['prev_review']);

            echo '<div class="ek-quiz">';
            echo '<div class="ek-quiz-content">';
            echo '<h1 class="ek-quiz-title">Filmový kvíz</h1>';
            echo '<p class="ek-quiz-subtitle">Pozrite si ukážku a uhádnite názov filmu</p>';
            $this->render_tries_remaining_banner('movies');
            if ($is_review) {
                echo '<div class="ek-review-banner">📝 Vaše predchádzajúce odpovede sú vyplnené — <strong style="color:#6dd58c">zelené</strong> boli správne, <strong style="color:#ff6b6b">červené</strong> nesprávne. Opravte a odošlite znova.</div>';
            }
            echo '<form action="' . esc_url($url) . '" method="post" class="ek-quiz-form" data-quiz-type="movies">';

            for($i=0;$i<$number_of_questions; $i++) {
                $human_number = $i+1;

                if( ($question_set_exists && !$treat_as_new) || $available_questions === null) {
                    $current_question_id = $this->questions_set[$i];
                } else {
                    $current_question_id = $available_questions[$this->questions_set[$i]]->ID;
                }

                $media_id = get_post_meta( $current_question_id, 'media', true );
                $movie_file_url = wp_get_attachment_url( $media_id );

                echo '<div class="ek-question">';
                echo '<div class="ek-question-header">';
                echo '<span class="ek-question-num">' . $human_number . '</span>';
                echo '<span class="ek-question-label">Film ' . $human_number . '</span>';
                echo '</div>';
                echo '<div class="ek-question-audio">';
                $this->show_media_file($movie_file_url);
                echo '</div>';
                echo '<div class="ek-question-fields">';
                echo '<div class="ek-input-group">';
                $prev_value = $is_review ? wp_unslash($_POST['prev_movie' . $human_number] ?? '') : '';
                $prev_key   = $is_review ? wp_unslash($_POST['prev_movie' . $human_number . '_key'] ?? '') : '';
                $prev_correct = $is_review ? ($_POST['prev_movie' . $human_number . '_correct'] ?? null) : null;
                $review_class = '';
                if ($prev_correct === '1') $review_class = ' ek-prev-correct';
                elseif ($prev_correct === '0' && $prev_value !== '') $review_class = ' ek-prev-wrong';

                if ($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
                     echo '<input id="myMovie' . $human_number . '" class="autocomplete3' . $review_class . '" name="movie' . $human_number . '" value="' . esc_attr($prev_value) . '" placeholder="Názov filmu" autocomplete="off">';
                } else {
                    $this->print_form_question($current_question_id, $human_number);
                }
                echo '<input type="hidden" name="movie' . $human_number . '_key" value="' . esc_attr($prev_key) . '">';
                echo '</div>';
                echo '</div>'; // .ek-question-fields
                echo '</div>'; // .ek-question

                $questions[] = $current_question_id;
            }

            echo '<input type="hidden" name="team" value="' . esc_attr($team_code) . '">';
            echo '<input type="hidden" name="user" value="' . esc_attr($user_code) . '">';
            echo '<input type="hidden" name="akcia" value="' . esc_attr($akcia_code) . '">';
            $serialized_question_set = json_encode($questions);

            echo '<input type="hidden" name="set" value="' . esc_attr($serialized_question_set) . '">';
            echo '<input type="hidden" name="set_sig" value="' . esc_attr($this->sign_question_set($serialized_question_set, $akcia_code)) . '">';

            $gc_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
            $gc_cp = isset($_GET['cp']) ? sanitize_text_field($_GET['cp']) : '';
            $gc_return = isset($_GET['return_url']) ? esc_url_raw($_GET['return_url']) : '';
            if (!empty($gc_id) && !empty($gc_cp)) {
                echo '<input type="hidden" name="gc_id" value="' . esc_attr($gc_id) . '">';
                echo '<input type="hidden" name="gc_cp" value="' . esc_attr($gc_cp) . '">';
                echo '<input type="hidden" name="gc_return" value="' . esc_attr($gc_return) . '">';
            }

            echo '<button type="submit" class="ek-quiz-submit">Odoslať odpovede</button>';
            echo '</form>';
            echo '</div>'; // .ek-quiz-content
            echo '</div>'; // .ek-quiz

            if( !$question_set_exists) {
                $this->write_question_set_to_db( $serialized_question_set, $akcia_code,'movies',$user_code,$team_code );
            }
        }
    }
    public function show_media_file($movie_file_url){
        echo '<video controls controlsList="nodownload noplaybackrate" disablePictureInPicture oncontextmenu="return false;" style="width:100%;border-radius:8px;display:block;">';
        echo '<source src="' . esc_url($movie_file_url) . '" type="video/mp4">';
        echo 'Your browser does not support the video element.';
        echo '</video>';
    }

    public function print_form_question($current_question_id, $human_number){


            echo "<div>";
                $choices_for_answer = get_post_meta( $current_question_id, 'choices_for_answer', true );
                if(!empty($choices_for_answer)) {
                    echo '<select name="movies' . $human_number . '">';

                    $options = preg_split("/\R/", $choices_for_answer);  // Split the values into an array
                        echo "<option value=''>Vyber ...</option>\n"; 
                    foreach ($options as $option) {
                        echo "<option value='" . trim(str_replace("'", "", $option)) . "'>" . trim($option) . "</option>\n"; 
                    }
                    echo '</select>';


                } 

            echo '</div>';

    }


}

























class Eventkviz_MoviesEval_Quiz_Class extends Eventkviz_MoviesForm_Quiz_Class{
    
    public function __construct() {

      
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'eval_movies_quiz_dynamic', array( $plugin, 'eventkviz_eval_movies_quiz' ) );
    }

    public function eventkviz_eval_movies_quiz($atts = '') {

        global $correct_answers;
        //global $movies_settings;
        global $gained_credits;
        



        $akcia = $_POST['akcia'];
        $this->load_basic_event_settings($akcia);

        // GeoChallenge: per-player scoping (POST gc_cp or cookie fallback)
        $_POST_user_override = $this->geo_user_code('eval');

        $raw_set = isset($_POST['set']) ? wp_unslash($_POST['set']) : '';
        $raw_sig = isset($_POST['set_sig']) ? wp_unslash($_POST['set_sig']) : '';
        if (!$this->verify_question_set_signature($raw_set, $akcia, $raw_sig)) {
            wp_die(
                esc_html__('Neplatný podpis kvíz formulára. Otvorte si kvíz znova.', 'eventkviz'),
                esc_html__('Neplatný formulár', 'eventkviz'),
                array('response' => 400, 'back_link' => true)
            );
        }

        $questions = json_decode($raw_set, true);
        $user = $_POST['user'];
        $team = $_POST['team'];
        if ($_POST_user_override !== '') $user = $_POST_user_override;
        
        //$this->movies_quiz_settings($akcia,$user,$team);
        $check_result = $this->check_number_of_tries($user, $akcia,'movies',$team);
        
        if($check_result === true) {

            echo '<div class="ek-quiz">';
            echo '<div class="ek-quiz-content">';
            echo '<h1 class="ek-quiz-title">Vyhodnotenie filmového kvízu</h1>';

            for($i=0;$i<count($questions);$i++) {
                if ($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
                    $correct_answers[] = $this->get_related_ids( $questions[$i], 17 );
                } else {
                    $correct_answers[] = $this->get_correct_movies_answers( $questions[$i]);
                }
            }

            $this->retry_state = array();
            for($i=0;$i<count($questions);$i++) {
                $this->evaluate_movie($questions[$i], $i, 1, 'dynamic');
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team);

            if($this->cAkcia->movies_settings['min_body_na_postup'] > 0 && $gained_credits >= $this->cAkcia->movies_settings['min_body_na_postup']) {
                    $is_gc = !empty($this->cAkcia->all_quizes_settings['geochallenge_integration']);
                    echo '<div class="ek-quiz-message ek-quiz-message--success">';
                    if ($is_gc) {
                        echo '<p>Získali ste dosť bodov. Použite kód nižšie v GeoChallenge appke.</p>';
                    } else {
                        echo '<p>Získali ste dosť bodov na postup a zobrazenie ďalšej indície.</p>';
                        $format = $this->cAkcia->movies_settings['format_pri_splneni'] ?? 'obrazok';
                        if ($format === 'text' && !empty($this->cAkcia->movies_settings['text_pri_splneni_kvizu'])) {
                            echo '<div class="eventkviz_splnenie_kvizu">' . wp_kses_post($this->cAkcia->movies_settings['text_pri_splneni_kvizu']) . '</div>';
                        } else {
                            echo '<p>Vaša ďalšia indícia je:</p>';
                            $url = wp_get_attachment_image_src( $this->cAkcia->movies_settings['obrazok_pri_splneni_kvizu'],'large' );
                            if ($url) {
                                echo "<img src='" . esc_url($url[0]) . "' style='width:100%;border-radius:12px;display:block;margin-top:12px;'>";
                            }
                        }
                    }
                    echo '</div>';

                    $this->show_geochallenge_return($gained_credits);

            } else {
                $akcia_tag = $this->akcia_tag;

                $link_to_movies_quiz_url = $this->build_retry_url($team, $user, $akcia_tag, '/merdfghh/');
                echo '<div class="ek-quiz-message ek-quiz-message--fail">';
                echo '<p>Nezískali ste dosť bodov na postup. Je potrebné dosiahnuť aspoň <strong>' . esc_html($this->cAkcia->movies_settings['min_body_na_postup']) . '</strong> bodov.</p>';

                $tries_left_after_this = isset($this->zostava_pocet_pokusov) ? ((int) $this->zostava_pocet_pokusov - 1) : 1;
                if ($tries_left_after_this > 0) {
                    $highlight_ok = !empty($this->cAkcia->movies_settings['mark_correctness_on_retry'])
                        && empty($this->cAkcia->movies_settings['new_questions_on_retry'])
                        && !empty($this->retry_state);
                    $review_state = $highlight_ok ? $this->retry_state : array();
                    $pocet_max = (int) ($this->cAkcia->movies_settings['pocet_pokusov'] ?? 0);
                    $shown = ($pocet_max > 0 && $tries_left_after_this > $pocet_max) ? $pocet_max : $tries_left_after_this;
                    $label = 'Opakovať kvíz (zostáva ' . $shown . ' '
                        . self::_n_pokus_label($shown) . ')';
                    $this->render_retry_button($link_to_movies_quiz_url, $label, $review_state);
                } else {
                    echo '<p><em>Toto bol váš posledný povolený pokus pre tento kvíz.</em></p>';
                }
                echo '</div>';
            }

            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'movies', 'insert');
            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'movies');
            $this->show_seed($user, $akcia, 'movies',$team);

            echo '</div>'; // .ek-quiz-content
            echo '</div>'; // .ek-quiz
        }
    }

    public function get_correct_movies_answers($current_question_id){
        
        $correct_answer = get_post_meta( $current_question_id, 'correct_answer_for_choices', true );
        
        $return_array = array('correct_answer' => $correct_answer);
        return $return_array;
        
    }

    public function get_correct_movie_answer($iteration_no) {
            global $correct_answers;
        return $correct_answers[$iteration_no];
    }

    public function is_in_array_of_correct_movie_answers($id){
        global $correct_answers;
        
        $key = array_search($id, $correct_answers);
        return $key;

    }

    public function get_movie_name($id){
        
        global $wpdb,$table_prefix;
        $user_ID = get_current_user_id();
        $sql = $wpdb->prepare('SELECT original_title FROM '.$table_prefix.'jet_cct_movies WHERE _ID = %d', $id);
        $value = $wpdb->get_var($sql);
        
          if(empty($value)) {
            $value = 'Nezadaná';
        }

        return $value;
    }

    public function check_movie_elswhere($form_movie){
        //global $movies_settings;
        global $gained_credits;
        global $used_movies;
        $credits =  $this->cAkcia->movies_settings['credits'];
        
        if(!empty($form_movie) && is_int($this->is_in_array_of_correct_movie_answers($form_movie)) && !in_array($form_movie, $used_movies)) {
            // add credit for correct song on wrong position
            $gained_credits += $credits['corr_movie_wrong_pos'];
            $this->show_answer("Správny film na zlej pozícii, hráč získava +" . $credits['corr_movie_wrong_pos'] . " bodov", 'movies', 'eventkviz_standard_answer', 'user_result');
            $used_movies[] = $form_movie;
            return 2;
        } elseif(in_array($form_movie, $used_movies)) {
            $this->show_answer("Duplicita, hráč získava +0 bodov", 'movies', 'eventkviz_standard_answer', 'user_result');
            return 1;
        } else {
            return 0;
        }
    }

    public function evaluate_movie($current_question_id, $iteration_no, $add_1_for_human_readable_numbers=0, $type='static') {
            global $correct_answers;
            global $gained_credits;
            global $used_movies;
            //global $movies_settings;
        
            if (!is_array($used_movies)) $used_movies = array();
            $credits =  $this->cAkcia->movies_settings['credits'];

        
        
        if($type == 'static' || $this->cAkcia->movies_settings['movies_quiz_type'] == "choices") {
            $correct_movie = $correct_answers[$iteration_no]['correct_answer'];
        } else {
            //$correct_movie = get_correct_answer($iteration_no, 'movie');
            $correct_movie = $this->get_correct_movie_answer($iteration_no);
        }
        
        $iteration_no_real = $iteration_no+$add_1_for_human_readable_numbers;
        
        

        if($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
            $movie_string = 'movie'.$iteration_no_real . '_key';
            $form_movie = $_POST[$movie_string];
            if (empty($form_movie)) {
                $typed = isset($_POST['movie'.$iteration_no_real]) ? wp_unslash($_POST['movie'.$iteration_no_real]) : '';
                $form_movie = $this->resolve_cct_id_by_name($typed, 'movies', 'original_title');
            }
        } else {
            $movie_string = 'movies'.$iteration_no_real;
            $form_movie = $_POST[$movie_string];
        }
        
        echo '<div class="ek-question">';
        echo '<div class="ek-question-header">';
        echo '<span class="ek-question-num">' . esc_html($iteration_no_real) . '</span>';
        echo '<span class="ek-question-label">Film ' . esc_html($iteration_no_real) . '</span>';
        echo '</div>';

        $media_id = get_post_meta( $current_question_id, 'media', true );
        $movie_file_url = wp_get_attachment_url( $media_id );
        echo '<div class="ek-question-audio">';
        $this->show_media_file($movie_file_url);
        echo '</div>';

        if($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
            $this->show_answer("Správna odpoveď: " . $this->get_movie_name($correct_movie), 'movies', 'eventkviz_standard_answer', 'correct_answer');
        } else {
            $this->show_answer("Správna odpoveď: " . $correct_movie, 'movies', 'eventkviz_standard_answer', 'correct_answer');
        }

        if($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
            $odpoved_hraca = $this->get_movie_name($form_movie);
        } else {
            $odpoved_hraca = $form_movie;
        }
        echo '<div class="ek-user-answer">Vaša odpoveď: ' . esc_html($odpoved_hraca) . '</div>';

        // Capture previous-state per-question for retry review highlight
        $typed_value = isset($_POST['movie' . $iteration_no_real]) ? wp_unslash($_POST['movie' . $iteration_no_real]) : '';
        $is_correct_for_review = ($correct_movie == $form_movie && !empty($form_movie)) ? '1' : '0';
        $this->retry_state['prev_movie' . $iteration_no_real] = $typed_value;
        $this->retry_state['prev_movie' . $iteration_no_real . '_key'] = (string) $form_movie;
        $this->retry_state['prev_movie' . $iteration_no_real . '_correct'] = $is_correct_for_review;

        if($correct_movie == $form_movie ) {
            if(!empty($form_movie) && !in_array($form_movie, $used_movies)) {
                $gained_credits += $credits['corr_movie'];
                $this->show_answer("Film určený správne, hráč získava +" . $credits['corr_movie'] . " bodov", 'movies', 'eventkviz_standard_answer', 'user_result');
                $used_movies[] = $form_movie;
            } elseif(empty($form_movie)) {
                $this->show_answer("Odpoveď nebola zadaná", 'movies', 'eventkviz_standard_answer', 'user_result');
            } else {
                $this->show_answer("Film zarátaný už predtým", 'movies', 'eventkviz_standard_answer', 'user_result');
            }

        } else {
            if (empty($form_movie)) {
                $this->show_answer("Odpoveď nebola zadaná, hráč získava +0 bodov", 'movies', 'eventkviz_standard_answer', 'user_result');
            } else {
                $movieres = $this->check_movie_elswhere($form_movie);
                if ($movieres < 1 ) {
                    $this->show_answer("Film nesprávne určený, hráč získava +0 bodov", 'movies', 'eventkviz_standard_answer', 'user_result');
                }
            }

        }

        echo '</div>'; // .ek-question
    }


}
