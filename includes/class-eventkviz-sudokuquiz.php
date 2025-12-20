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

             if( !$question_set_exists) {
                $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $number_of_questions); 
               
            }

            if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                $url = 'http://localhost:8888/eventkviz/sudoku-quiz-evaluation-dynamic/';
            } else {
                $url = 'https://eventkviz.sk/sudoku-quiz-evaluation-dynamic/';
            }

            echo '<form action="'. $url . '"method="post">';
        
            for($i=0;$i<$number_of_questions; $i++) {
                $human_number = $i+1;

                if( $question_set_exists) {
                    $current_question_id = $this->questions_set[$i];
                } else {
                    $current_question_id = $available_questions[$this->questions_set[$i]]->ID;
                }

                // Get the featured image URL
                $featured_image_url = get_the_post_thumbnail_url( $current_question_id );

                // Get the post title
                $post_title = get_the_title( $current_question_id );

                echo '<div>';
                    echo '<br><h3>Sudoku #' . $human_number . '</h3>';

                    echo '<br>Solve this sudoku and find out the numbers in marked cells.<br><br>';

                        echo '<img src="' . $featured_image_url . '" alt="' . $post_title . '" style="width:400px;height: auto;">';


                    echo "<div>";
                        echo "Your answer:";
                        echo '<input name="sudoku' . $human_number . '">';
                        echo '<br>Hint: Write the correct numbers in their order into input field. Separate numbers by comma. Use only numbers and commas, using any other characters will be considered as wrong answer.<br><br>';

                    echo '</div>';
                echo '</div>';


                $questions[] = $current_question_id;
            }

            echo '<input type="hidden" name="team" value = "' . $team_code . '">';
            echo '<input type="hidden" name="user" value = "' . $user_code . '">';
            echo '<input type="hidden" name="akcia" value = "' . $akcia_code . '">';
            $serialized_question_set = serialize($questions);

            echo '<input type="hidden" name="set" value = "' . $serialized_question_set . '">';


            echo ' <input type="submit">';
            echo '</form>';

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
        $questions = unserialize($_POST['set']);
        //print_r($questions);
        $user = $_POST['user'];
        $team = $_POST['team'];
        
        //$this->sudoku_quiz_settings($akcia, $user, $team);
        $check_result = $this->check_number_of_tries($user, $akcia,'sudoku',$team);
        
        if($check_result === true) {

            for($i=0;$i<count($questions);$i++) {
                $correct_answers[] = $this->get_correct_sudoku_answers( $questions[$i]);
            }


            for($i=0;$i<count($questions);$i++) {
                //echo $questions[$i];

                $this->evaluate_sudoku($i, 1, 'dynamic', $questions[$i]);
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team);

            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'sudoku', 'insert');

            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'sudoku');

            $this->show_seed($user, $akcia, 'sudoku',$team);
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


        
        echo "<h2>Answers for sudoku question no. " . $iteration_no_real . "</h2>";
        $this->show_media_file($current_question_id, false);   

        $this->show_sudoku_answer( $correct_sudoku, $current_question_id);
        //$this->show_answer("Correct answer: " . $correct_sudoku , 'sudoku');
        echo "User answer: " . $form_sudoku . '<br>';
        
        if($form_sudoku == $correct_sudoku) {
            
            $credits_for_difficulty = $this->get_credits_for_difficulty($iteration_no);
            
            $gained_credits += $credits_for_difficulty;
            $this->show_answer("Answer was correct, user gets +" . $credits_for_difficulty . " points", 'sudoku');
        }
            
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