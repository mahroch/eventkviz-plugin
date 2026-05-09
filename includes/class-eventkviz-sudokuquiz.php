<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_SudokuForm_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {

        
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'sudoku_form_dynamic', array( $plugin, 'eventkviz_sudoku_form' ) );
    }


    public function eventkviz_sudoku_form($atts = '') {
        
        $user_code = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' ); 
         $this->load_basic_event_settings( $akcia_code);
        $team_code = $this->set_team_code($user_code, $akcia_code,);
        //$this->sudoku_quiz_settings($akcia_code, $user_code, $team_code);

        if($this->cAkcia->sudoku_settings['show_entry_form'] === true){

            if($this->cAkcia->all_quizes_settings['select_from_teams_array'] === true && empty($team_code)) {
                //ukaz vyberovy form 
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;

                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links($att, 'sudoku');
                die;
            }
        } 


        $check_result = $this->check_number_of_tries($user_code, $akcia_code,'sudoku',$team_code);
        
        if($check_result === true) {

            $number_of_questions = $this->cAkcia->sudoku_settings['pocet_otazok_v_sete'];

            $args = array(
                    'post_type'   => 'questions-sudoku',
                    'numberposts' => 0
            );

            $available_questions = get_posts( $args );

            $number_of_available_questions = count($available_questions)-1;
            $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,'sudoku',$user_code,$team_code);
            $regenerate_on_retry = !empty($this->cAkcia->sudoku_settings['new_questions_on_retry']);
            $treat_as_new = !$question_set_exists || $regenerate_on_retry;

            if( $treat_as_new ) {
                $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $number_of_questions);
            }

            $url = home_url('/sudoku-quiz-evaluation-dynamic/');

            echo '<div class="ek-quiz">';
            echo '<div class="ek-quiz-content">';
            echo '<h1 class="ek-quiz-title">Sudoku kvíz</h1>';
            echo '<p class="ek-quiz-subtitle">Vyriešte sudoku a zapíšte čísla z označených políčok</p>';
            echo '<form action="' . esc_url($url) . '" method="post" class="ek-quiz-form" data-quiz-type="sudoku">';

            for($i=0;$i<$number_of_questions; $i++) {
                $human_number = $i+1;

                if( $question_set_exists && !$treat_as_new) {
                    $current_question_id = $this->questions_set[$i];
                } else {
                    $current_question_id = $available_questions[$this->questions_set[$i]]->ID;
                }

                $featured_image_url = get_the_post_thumbnail_url( $current_question_id );
                $post_title = get_the_title( $current_question_id );

                echo '<div class="ek-question">';
                echo '<div class="ek-question-header">';
                echo '<span class="ek-question-num">' . $human_number . '</span>';
                echo '<span class="ek-question-label">Sudoku ' . $human_number . '</span>';
                echo '</div>';

                echo '<div class="ek-question-audio">';
                echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr($post_title) . '" style="width:100%;border-radius:8px;display:block;">';
                echo '</div>';

                echo '<div class="ek-question-fields">';
                echo '<div class="ek-input-group">';
                echo '<input name="sudoku' . $human_number . '" placeholder="Čísla z označených políčok (oddelené čiarkou)" autocomplete="off">';
                echo '</div>';
                echo '<div class="ek-question-hint">Tip: zapíšte čísla v správnom poradí, oddeľte ich čiarkou. Použite iba číslice a čiarky.</div>';
                echo '</div>'; // .ek-question-fields
                echo '</div>'; // .ek-question

                $questions[] = $current_question_id;
            }

            echo '<input type="hidden" name="team" value="' . esc_attr($team_code) . '">';
            echo '<input type="hidden" name="user" value="' . esc_attr($user_code) . '">';
            echo '<input type="hidden" name="akcia" value="' . esc_attr($akcia_code) . '">';
            $serialized_question_set = json_encode($questions);

            echo '<input type="hidden" name="set" value="' . esc_attr($serialized_question_set) . '">';

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
                $this->write_question_set_to_db( $serialized_question_set, $akcia_code,'sudoku',$user_code,$team_code );
            }
        }
}


}


class Eventkviz_SudokuEval_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {

      
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'eval_sudoku_quiz_dynamic', array( $plugin, 'eventkviz_eval_sudoku_quiz' ) );
    }

    public function eventkviz_eval_sudoku_quiz($atts = '') {
	
        global $correct_answers;
        //global $sudoku_settings;
        global $gained_credits;
        
        $akcia = $_POST['akcia'];
         $this->load_basic_event_settings( $akcia);
        $questions = json_decode(wp_unslash($_POST['set']), true);
        //print_r($questions);
        $user = $_POST['user'];
        $team = $_POST['team'];
        
        //$this->sudoku_quiz_settings($akcia, $user, $team);
        $check_result = $this->check_number_of_tries($user, $akcia,'sudoku',$team);
        
        if($check_result === true) {

            echo '<div class="ek-quiz">';
            echo '<div class="ek-quiz-content">';
            echo '<h1 class="ek-quiz-title">Vyhodnotenie sudoku kvízu</h1>';

            for($i=0;$i<count($questions);$i++) {
                $correct_answers[] = $this->get_correct_sudoku_answers( $questions[$i]);
            }

            for($i=0;$i<count($questions);$i++) {
                $this->evaluate_sudoku($i, 1, 'dynamic', $questions[$i]);
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team);

            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'sudoku', 'insert');

            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'sudoku');

            $this->show_seed($user, $akcia, 'sudoku',$team);

            if ($gained_credits > 0) {
                $this->show_geochallenge_return($gained_credits);
            }

            echo '</div>'; // .ek-quiz-content
            echo '</div>'; // .ek-quiz
        }
    }

    function get_correct_sudoku_answers($current_question_id){
        
        $correct_answer = get_post_meta( $current_question_id, 'correct-answer', true );
        $difficulty = get_the_terms( $current_question_id, 'difficulty' ); 

        $correct_array = array ($correct_answer, $difficulty[0]->slug);
        return $correct_array;
        
    }
        
    function get_credits_for_difficulty($iteration_no){
       // global $sudoku_settings;
        global $correct_answers;

        $dif = $correct_answers[$iteration_no][1]; 
        
        return $this->cAkcia->sudoku_settings['credits'][$dif];
    }

    function evaluate_sudoku($iteration_no, $add_1_for_human_readable_numbers=0, $type='static', $current_question_id='') {
            global $correct_answers;
            global $gained_credits;
            //global $sudoku_settings;

            $credits = $this->cAkcia->sudoku_settings['credits'];

            $correct_sudoku = $correct_answers[$iteration_no][0];

        
        $iteration_no_real = $iteration_no+$add_1_for_human_readable_numbers;
        
        $sudoku_string = 'sudoku'.$iteration_no_real;

        $form_sudoku = $_POST[$sudoku_string];


        
        echo '<div class="ek-question">';
        echo '<div class="ek-question-header">';
        echo '<span class="ek-question-num">' . esc_html($iteration_no_real) . '</span>';
        echo '<span class="ek-question-label">Sudoku ' . esc_html($iteration_no_real) . '</span>';
        echo '</div>';
        echo '<div class="ek-question-audio">';
        $this->show_media_file($current_question_id, false);
        echo '</div>';

        $this->show_sudoku_answer( $correct_sudoku, $current_question_id);
        echo '<div class="ek-user-answer">Vaša odpoveď: ' . esc_html($form_sudoku) . '</div>';

        if($form_sudoku == $correct_sudoku) {
            $credits_for_difficulty = $this->get_credits_for_difficulty($iteration_no);
            $gained_credits += $credits_for_difficulty;
            $this->show_answer("Odpoveď bola správna, hráč získava +" . $credits_for_difficulty . " bodov", 'sudoku');
        }

        echo '</div>'; // .ek-question
    }
    public function show_sudoku_answer($correct_sudoku, $current_question_id){

		global $remember_answer;

		$show = $this->cAkcia->sudoku_settings['zobraz_spravne_odpovede'];
		if($show === true) {
            $this->meta_fields = get_post_meta( $current_question_id );
            if(!empty($this->meta_fields['explanation-of-correct-answer'][0])) {
                //echo '<br><div class = "explanation-of-correct-answer">' . $this->meta_fields['explanation-of-correct-answer'][0] . '</div>';
                $image = wp_get_attachment_image_src($this->meta_fields['explanation-of-correct-answer'][0], 'full');
                echo '<br><br>Solved sudoku<br><br>'; 
                echo '<img src="' . $image[0] . '" alt="Image">';
            }

			echo '<div class="eventkviz_standard_answer">Correct answer is ' . $correct_sudoku ;
            echo '.</div><br>';
            

            

		} else {
			if(!$remember_answer) {
				echo "<div  class='eventkviz_warning_correct_answers'>Correct answers are not to be displayed. Please refer to the event organizator.</div><br>";
				$remember_answer = 'already_showed';
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
                echo '<img src="' . $featured_image_url . '" alt="' . $this->post_title . '" style="width:50%;max: height 20%;">';
            } 

            echo '<div>' . $post_content . '</div>';

            if(!empty($this->meta_fields['hint'][0]) && $show_hint) {
                echo '<div style="font-style: italic; margin-top:20px;margin-bottom:20px;">Hint for correct syntax of the answer: ' . $this->meta_fields['hint'][0] . '</div>';
            }
     }

}