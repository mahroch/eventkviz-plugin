<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_KnowledgeForm_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {

        
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'knowledge_form_dynamic', array( $plugin, 'eventkviz_knowledge_form' ) );
    }


    public function eventkviz_knowledge_form($atts = '') {
	

        $user_code = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' );
        $this->load_basic_event_settings( $akcia_code);

        // GeoChallenge: per-participant scoping via cp query arg
        $gc_user = $this->geo_user_code('form');
        if ($gc_user !== '') $user_code = $gc_user;

        $team_code = $this->set_team_code($user_code, $akcia_code);

        if($this->cAkcia->knowledge_settings['show_entry_form'] === true){

            if($this->cAkcia->all_quizes_settings['select_from_teams_array'] === true && empty($team_code)) {
                //ukaz vyberovy form 
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;

                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links($att, 'knowledge');
                echo "</body></html>";
                die;
            }
        } 

        
                $check_result = $this->check_number_of_tries($user_code, $akcia_code,'knowledge',$team_code);
                
                if($check_result === true) {	

                    $url = home_url('/knowledge-quiz-evaluation-dynamic/');
                        

                    $is_review = !empty($_POST['prev_review']);

                    echo '<div class="ek-quiz">';
                    echo '<div class="ek-quiz-content">';
                    echo '<h1 class="ek-quiz-title">Vedomostný kvíz</h1>';
                    echo '<p class="ek-quiz-subtitle">Odpovedzte na otázky a získajte body</p>';
                    if ($is_review) {
                        echo '<div class="ek-review-banner">📝 Vaše predchádzajúce odpovede sú vyplnené — <strong style="color:#6dd58c">zelené</strong> boli správne, <strong style="color:#ff6b6b">červené</strong> nesprávne. Opravte a odošlite znova.</div>';
                    }
                    echo '<form action="' . esc_url($url) . '" method="post" class="ek-quiz-form" data-quiz-type="knowledge">';

                    $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,'knowledge',$user_code,$team_code);
                    $regenerate_on_retry = !empty($this->cAkcia->knowledge_settings['new_questions_on_retry']);
                    $treat_as_new = !$question_set_exists || $regenerate_on_retry;

                    $questions = array();
                    if( $question_set_exists && !$treat_as_new) {
                        $quantity = count($this->questions_set);
                        for($i=0;$i<$quantity; $i++) {
                            $human_number = $i+1;
                            $current_question_id = $this->questions_set[$i];

                            $this->print_form_question($current_question_id, $human_number);
                        }
                        $questions = $this->questions_set;
                    } else {
                        $topic_counts = isset($this->cAkcia->knowledge_settings['number_question_in_topic'])
                            ? $this->cAkcia->knowledge_settings['number_question_in_topic']
                            : array();

                        $use_per_topic = false;
                        foreach ($topic_counts as $count) {
                            if ((int) $count > 0) { $use_per_topic = true; break; }
                        }

                        if ($use_per_topic) {
                            $k = 0;
                            foreach ($topic_counts as $topic => $quantity){
                                if($quantity > 0) {
                                    $args = array(
                                        'post_type'   => 'questions-knowledge',
                                        'numberposts' => -1,
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => 'topic',
                                                'field' => 'slug',
                                                'terms' => $topic
                                            )
                                        )
                                    );

                                    $available_topic_questions = get_posts( $args );
                                    $number_of_available_questions = count($available_topic_questions)-1;
                                    $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $quantity);

                                    for($i=0;$i<$quantity; $i++) {
                                        $human_number = $k+1;
                                        $current_question_id = $available_topic_questions[$this->questions_set[$i]]->ID;

                                        $this->print_form_question($current_question_id, $human_number);

                                        $questions[] = $current_question_id;
                                        $k++;
                                    }
                                }
                            }
                        } else {
                            // Vsetky topic counts su 0 → rovnomerne (round-robin) rozdelime
                            // pocet_otazok_v_sete medzi vsetky temy ktore maju otazky.
                            // Princip: prv kazda tema dostane 1, potom druha, kym sa nedoplni N.
                            // Pri 6 temach + N=10 → 4 temy maju 2 otazky, 2 temy maju 1.
                            $number_of_questions = isset($this->cAkcia->knowledge_settings['pocet_otazok_v_sete'])
                                ? (int) $this->cAkcia->knowledge_settings['pocet_otazok_v_sete']
                                : 0;

                            if ($number_of_questions > 0) {
                                $topic_terms = get_terms(array(
                                    'taxonomy'   => 'topic',
                                    'hide_empty' => true,
                                ));

                                $pools = array();
                                if (is_array($topic_terms)) {
                                    foreach ($topic_terms as $term) {
                                        $posts = get_posts(array(
                                            'post_type'   => 'questions-knowledge',
                                            'numberposts' => -1,
                                            'tax_query'   => array(
                                                array(
                                                    'taxonomy' => 'topic',
                                                    'field'    => 'slug',
                                                    'terms'    => $term->slug,
                                                ),
                                            ),
                                        ));
                                        if (!empty($posts)) {
                                            shuffle($posts);
                                            $pools[$term->slug] = $posts;
                                        }
                                    }
                                }

                                // Randomize topic order tak, aby "extra" otazky (pri nedelitelnom N)
                                // padali zakazdym na ine temy.
                                $pool_keys = array_keys($pools);
                                shuffle($pool_keys);

                                $picked = array();
                                while (count($picked) < $number_of_questions) {
                                    $any = false;
                                    foreach ($pool_keys as $slug) {
                                        if (count($picked) >= $number_of_questions) break;
                                        if (!empty($pools[$slug])) {
                                            $picked[] = array_pop($pools[$slug]);
                                            $any = true;
                                        }
                                    }
                                    if (!$any) break;
                                }

                                shuffle($picked);

                                for ($i = 0; $i < count($picked); $i++) {
                                    $human_number = $i + 1;
                                    $current_question_id = $picked[$i]->ID;

                                    $this->print_form_question($current_question_id, $human_number);

                                    $questions[] = $current_question_id;
                                }
                            }
                        }
                    }

                    echo '<input type="hidden" name="team" value = "' . esc_attr($team_code) . '">';
                    echo '<input type="hidden" name="user" value = "' . esc_attr($user_code) . '">';
                    echo '<input type="hidden" name="akcia" value = "' . esc_attr($akcia_code) . '">';
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
                        $this->write_question_set_to_db( $serialized_question_set,$akcia_code,'knowledge',$user_code,$team_code,  'insert');
                    }

                }

    }

     public function show_media_file($current_question_id, $show_hint = true){
        $featured_image_url = get_the_post_thumbnail_url( $current_question_id );

        $post_content = get_the_content( '', false, $current_question_id );

        if(empty($this->post_title)) {
            $this->post_title = get_the_title( $current_question_id );
        }

        $this->meta_fields = get_post_meta( $current_question_id );

        if(!empty($featured_image_url)) {
            echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($this->post_title) . '" loading="lazy" style="width:100%;border-radius:8px;display:block;margin-bottom:12px;">';
        }

        if (!empty($post_content)) {
            echo '<div class="ek-question-text">' . $post_content . '</div>';
        }

        if(!empty($this->meta_fields['hint'][0]) && $show_hint) {
            echo '<div class="ek-question-hint">Nápoveda: ' . esc_html($this->meta_fields['hint'][0]) . '</div>';
        }
     }


    public function print_form_question($current_question_id, $human_number){

        $this->post_title = get_the_title( $current_question_id );

        echo '<div class="ek-question">';
        echo '<div class="ek-question-header">';
        echo '<span class="ek-question-num">' . $human_number . '</span>';
        echo '<span class="ek-question-label">' . esc_html($this->post_title) . '</span>';
        echo '</div>';

        echo '<div class="ek-question-audio">';
        $this->show_media_file($current_question_id, true);
        echo '</div>';

        echo '<div class="ek-question-fields">';
        echo '<div class="ek-input-group">';

        $is_review = !empty($_POST['prev_review']);
        $prev_value = $is_review ? wp_unslash($_POST['prev_knowledge' . $human_number] ?? '') : '';
        $prev_correct = $is_review ? ($_POST['prev_knowledge' . $human_number . '_correct'] ?? null) : null;
        $review_class = '';
        if ($prev_correct === '1') $review_class = ' ek-prev-correct';
        elseif ($prev_correct === '0' && $prev_value !== '') $review_class = ' ek-prev-wrong';

        if(!empty($this->meta_fields['choices-for-correct-answer'][0])) {
            echo '<select name="knowledge' . $human_number . '" class="' . esc_attr(trim($review_class)) . '">';
            $options = explode(";", $this->meta_fields['choices-for-correct-answer'][0]);
            if(count($options) == 1) {
                $options = explode(",", $this->meta_fields['choices-for-correct-answer'][0]);
            }
            echo "<option value=''>Vyberte odpoveď</option>\n";
            foreach ($options as $option) {
                $opt_val = trim($option);
                $sel = ($prev_value !== '' && $prev_value === $opt_val) ? ' selected' : '';
                echo "<option value='" . esc_attr($opt_val) . "'" . $sel . ">" . esc_html($opt_val) . "</option>\n";
            }
            echo '</select>';
        } else {
            echo '<input name="knowledge' . $human_number . '" class="' . esc_attr(trim($review_class)) . '" value="' . esc_attr($prev_value) . '" placeholder="Vaša odpoveď" autocomplete="off">';
        }

        echo '</div>';
        echo '</div>'; // .ek-question-fields
        echo '</div>'; // .ek-question
    }
}



























class Eventkviz_KnowledgeEval_Quiz_Class extends Eventkviz_KnowledgeForm_Quiz_Class{
    
    public function __construct() {

      
    }

     public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'eval_knowledge_quiz_dynamic', array( $plugin, 'eventkviz_eval_knowledge_quiz' ) );
    }

    public function eventkviz_eval_knowledge_quiz($atts = '') {
        global $correct_answers;
        //global $this->cAkcia->knowledge_settings;
        global $gained_credits;
        
        $akcia = $_POST['akcia'];
        $this->load_basic_event_settings( $akcia);

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

        // GeoChallenge: per-participant scoping via gc_cp POST field
        $gc_user = $this->geo_user_code('eval');
        if ($gc_user !== '') $user = $gc_user;

        $check_result = $this->check_number_of_tries($user, $akcia,'knowledge',$team);
        
        if($check_result === true) {

            echo '<div class="ek-quiz">';
            echo '<div class="ek-quiz-content">';
            echo '<h1 class="ek-quiz-title">Vyhodnotenie vedomostného kvízu</h1>';

            for($i=0;$i<count($questions);$i++) {
                $correct_answers[] = $this->get_correct_knowledge_answers( $questions[$i]);
            }

            $this->retry_state = array();
            for($i=0;$i<count($questions);$i++) {
                $this->evaluate_knowledge($questions[$i], $i, 1, 'dynamic');
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team);

            if($this->cAkcia->knowledge_settings['min_body_na_postup'] > 0 && $gained_credits >= $this->cAkcia->knowledge_settings['min_body_na_postup']) {
                    echo '<div class="ek-quiz-message ek-quiz-message--success">';
                    echo '<p>Získali ste dosť bodov na postup a zobrazenie ďalšej indície.</p>';
                    echo '<p>Vaša ďalšia indícia je:</p>';
                    $url = wp_get_attachment_image_src( $this->cAkcia->knowledge_settings['obrazok_pri_splneni_kvizu'],'large' );
                    echo "<img src='" . esc_url($url[0]) . "' style='width:100%;border-radius:12px;display:block;margin-top:12px;'>";
                    echo '</div>';

                    $this->show_geochallenge_return($gained_credits);

            } else {
                $akcia_tag = $this->akcia_tag;

                $link_to_quiz_url = $this->build_retry_url($team, $user, $akcia_tag, '/kwersdfzx/');
                echo '<div class="ek-quiz-message ek-quiz-message--fail">';
                echo '<p>Nezískali ste dosť bodov na postup. Je potrebné dosiahnuť aspoň <strong>' . esc_html($this->cAkcia->knowledge_settings['min_body_na_postup']) . '</strong> bodov.</p>';

                $tries_left_after_this = isset($this->zostava_pocet_pokusov) ? ((int) $this->zostava_pocet_pokusov - 1) : 1;
                if ($tries_left_after_this > 0) {
                    $highlight_ok = !empty($this->cAkcia->knowledge_settings['mark_correctness_on_retry'])
                        && empty($this->cAkcia->knowledge_settings['new_questions_on_retry'])
                        && !empty($this->retry_state);
                    $review_state = $highlight_ok ? $this->retry_state : array();
                    $this->render_retry_button($link_to_quiz_url, 'Opakovať kvíz', $review_state);
                } else {
                    echo '<p><em>Toto bol váš posledný povolený pokus pre tento kvíz.</em></p>';
                }
                echo '</div>';
            }

            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'knowledge', 'insert');
            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'knowledge');
            $this->show_seed($user, $akcia, 'knowledge',$team);

            echo '</div>'; // .ek-quiz-content
            echo '</div>'; // .ek-quiz
        }
    }

    public function get_correct_knowledge_answers($current_question_id){
        $correct_answer1 = get_post_meta( $current_question_id, 'correct-answer-1', true );
        $correct_answer2 = get_post_meta( $current_question_id, 'correct-answer-2', true );

        // Each meta field may contain pipe-separated synonyms (Bratislava|BA|hlavné mesto)
        $variants = array_merge(
            $this->split_answer_variants($correct_answer1),
            $this->split_answer_variants($correct_answer2)
        );

        return array(
            'correct_answer1' => $correct_answer1,
            'correct_answer2' => $correct_answer2,
            'variants'        => $variants,
        );
    }

    private function split_answer_variants($s){
        $s = (string) $s;
        if ($s === '') return array();
        $parts = array_map('trim', explode('|', $s));
        return array_values(array_filter($parts, 'strlen'));
    }

    private function normalize_for_compare($s){
        if (class_exists('Eventkviz_Rest_Search')) {
            return Eventkviz_Rest_Search::normalize($s);
        }
        return strtolower(trim((string) $s));
    }

    public function check_correct_knowledge_answer($answer, $iteration_no){
        global $correct_answers;

        if (empty($answer)) return false;

        $entry = isset($correct_answers[$iteration_no]) ? $correct_answers[$iteration_no] : array();

        $variants = isset($entry['variants']) ? $entry['variants'] : array();
        if (empty($variants)) {
            // Legacy fallback for entries without the variants key
            $variants = array_filter(array(
                isset($entry['correct_answer1']) ? $entry['correct_answer1'] : '',
                isset($entry['correct_answer2']) ? $entry['correct_answer2'] : '',
            ), 'strlen');
        }

        $needle = $this->normalize_for_compare($answer);
        foreach ($variants as $v) {
            if ($this->normalize_for_compare($v) === $needle) {
                return true;
            }
        }
        return false;
    }
    public function show_knowledge_answer($correct_knowledge, $current_question_id){

		global $remember_answer;

		$show = $this->cAkcia->knowledge_settings['zobraz_spravne_odpovede'];

		if($show === true) {
            $this->meta_fields = get_post_meta( $current_question_id );
            if(!empty($this->meta_fields['explanation-of-correct-answer'][0])) {
                echo '<br><div class = "explanation-of-correct-answer">' . $this->meta_fields['explanation-of-correct-answer'][0] . '</div>';
            }

			echo '<div class="eventkviz_standard_answer">Správna odpoveď je ' . $correct_knowledge['correct_answer1'];

            if(!empty($correct_knowledge['correct_answer2'])) {
            echo ' a jej druhá alternatíva ' . $correct_knowledge['correct_answer2'];
            }
            echo '.</div><br>';
            

            

		} else {
			if(!$remember_answer) {
				echo "<div  class='eventkviz_warning_correct_answers'>Správne odpovede sa zámerne nezobrazia. Ak sa vám to nepozdáva, prosím, informujte sa u organizátora podujatia.</div><br>";
				$remember_answer = 'already_showed';
			} 
			
		}
    }

    public function evaluate_knowledge($current_question_id, $iteration_no, $add_1_for_human_readable_numbers=0, $type='static') {
        global $correct_answers;
        global $gained_credits;
        //global $this->cAkcia->knowledge_settings;

        $credits = $this->cAkcia->knowledge_settings['credits'];

        $correct_knowledge = $correct_answers[$iteration_no];

        
        $iteration_no_real = $iteration_no+$add_1_for_human_readable_numbers;
        
        $knowledge_string = 'knowledge'.$iteration_no_real;

        $this->post_title = get_the_title( $current_question_id );

        echo '<div class="ek-question">';
        echo '<div class="ek-question-header">';
        echo '<span class="ek-question-num">' . esc_html($iteration_no_real) . '</span>';
        echo '<span class="ek-question-label">' . esc_html($this->post_title) . '</span>';
        echo '</div>';

        if(array_key_exists($knowledge_string, $_POST)){
            $form_knowledge = $_POST[$knowledge_string];
            if(empty($form_knowledge)) {
                $form_knowledge = 'Odpoveď nebola zadaná';
            }

            echo '<div class="ek-question-audio">';
            $this->show_media_file($current_question_id, false);
            echo '</div>';

            $this->show_knowledge_answer( $correct_knowledge, $current_question_id);
            echo '<div class="ek-user-answer">Vaša odpoveď: ' . esc_html($form_knowledge) . '</div>';

            $result = $this->check_correct_knowledge_answer($form_knowledge, $iteration_no);

            // Capture per-question state for retry review highlight
            $typed_value = isset($_POST[$knowledge_string]) ? wp_unslash($_POST[$knowledge_string]) : '';
            $this->retry_state['prev_knowledge' . $iteration_no_real] = $typed_value;
            $this->retry_state['prev_knowledge' . $iteration_no_real . '_correct'] = ($result === true) ? '1' : '0';

            if($result === true && $this->cAkcia->knowledge_settings['zobraz_spravne_uhadnute_odpovede'] === true ) {
                $gained_credits += $credits['corr_answer'];
                $this->show_answer("Odpoveď je správna, hráč získava +" . $credits['corr_answer'] . " bodov", 'knowledge', 'eventkviz_correct_answer');
            } else {
                $this->show_answer("Odpoveď nie je správna, hráč získava 0 bodov.", 'knowledge', 'eventkviz_incorrect_answer');
            }

        } else {
            $this->show_answer("Správna odpoveď: " . implode(',',$correct_knowledge) , 'knowledge');
            echo '<div class="ek-user-answer">Vaša odpoveď: Nezadaná</div>';
        }

        echo '</div>'; // .ek-question
    }



}