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
        $team_code = $this->set_team_code($user_code, $akcia_code);
        //$this->cAkcia->knowledge_quiz_settings($akcia_code, $user_code, $team_code);

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

                    if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                        $url = 'http://localhost:8888/eventkviz/knowledge-quiz-evaluation-dynamic/';
                    } else {
                        $url = 'https://eventkviz.sk/knowledge-quiz-evaluation-dynamic/';
                    }
                        

                    echo '<form action="'. $url . '"method="post">';

                    $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,'knowledge',$user_code,$team_code);

                    if( $question_set_exists) {
                        $quantity = count($this->questions_set);
                        for($i=0;$i<$quantity; $i++) {
                            $human_number = $i+1;
                            $current_question_id = $this->questions_set[$i];
                            
                            $this->print_form_question($current_question_id, $human_number);
                        }
                        $questions = $this->questions_set;
                    } else {
                        $k = 0;
                        foreach ($this->cAkcia->knowledge_settings['number_question_in_topic'] as $topic => $quantity){
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
                                //$questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $quantity);

                                $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $quantity);
                        


                                for($i=0;$i<$quantity; $i++) {
                                    $human_number = $k+1;
                                    $current_question_id = $available_topic_questions[$this->questions_set[$i]]->ID;
                                    
                                    $this->print_form_question($current_question_id, $human_number);

                                    $questions[] = $current_question_id;
                                    $k++;
                                }
                            }
                            //$k++;
                        }
                    }

                    echo '<input type="hidden" name="team" value = "' . $team_code . '">';
                    echo '<input type="hidden" name="user" value = "' . $user_code . '">';
                    echo '<input type="hidden" name="akcia" value = "' . $akcia_code . '">';
                    $serialized_question_set = json_encode($questions);

                    echo '<input type="hidden" name="set" value = "' . esc_attr($serialized_question_set) . '">';
                    echo '</br></br>';
                    echo '<input type="submit" value="Odoslať">';
                    echo '</form>';

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
                echo '<img src="' . $featured_image_url . '" alt="' . $this->post_title . '" style="width:80%;max: height 20%;">';
            } 

            echo '<div>' . $post_content . '</div>';

            if(!empty($this->meta_fields['hint'][0]) && $show_hint) {
                echo '<div style="font-style: italic; margin-top:20px;margin-bottom:20px;">Nápoveda k správnemu zadaniu odpovede: ' . $this->meta_fields['hint'][0] . '</div>';
            }
     }


    public function print_form_question($current_question_id, $human_number){

        $this->post_title = get_the_title( $current_question_id );

        echo '<div>';
        echo '<br><h3>Otázka #' . $human_number . ': ' . $this->post_title . '</h3>';

        $this->show_media_file($current_question_id, true);    


            echo "<div>";
                echo "Tvoja odpoveď:";

                if(!empty($this->meta_fields['choices-for-correct-answer'][0])) {
                    echo '<select name="knowledge' . $human_number . '">';

                    $options = explode(";", $this->meta_fields['choices-for-correct-answer'][0]); // Split the values into an array
                    
                    if(count($options) == 1) {
                       $options = explode(",", $this->meta_fields['choices-for-correct-answer'][0]); // Split the values into an array 
                    }
                        echo "<option value=''>Vyber ...</option>\n"; 
                    foreach ($options as $option) {
                        echo "<option value='" . trim($option) . "'>" . trim($option) . "</option>\n"; 
                    }
                    echo '</select>';


                } else {
                    echo '<input name="knowledge' . $human_number . '">';
                }

            echo '</div>';
        echo '</div>';
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
        $questions = json_decode(wp_unslash($_POST['set']), true);
        //print_r($questions);
        $user = $_POST['user'];
        $team = $_POST['team'];
        
        //$this->knowledge_quiz_settings($akcia, $user, $team);
        $check_result = $this->check_number_of_tries($user, $akcia,'knowledge',$team);
        
        if($check_result === true) {
            
            for($i=0;$i<count($questions);$i++) {
                $correct_answers[] = $this->get_correct_knowledge_answers( $questions[$i]);
            }


            for($i=0;$i<count($questions);$i++) {
                //echo $questions[$i];

                $this->evaluate_knowledge($questions[$i], $i, 1, 'dynamic');
            }
            if(!$gained_credits) $gained_credits = 0;
            //echo "<h1>Sumár získaných bodov:" . $gained_credits . " bodov (" . $user . ", " . $team .")</h1>";
            $this->show_total_credits_gained($gained_credits, $user, $team);

            if($this->cAkcia->knowledge_settings['min_body_na_postup'] > 0 && $gained_credits >= $this->cAkcia->knowledge_settings['min_body_na_postup']) {
                    echo "Získali ste dosť bodov na postup a zobrazenie ďalšej indície.<br><br>";
                    echo "Vaša ďalšia indícia je:<br><br>";
                    $url = wp_get_attachment_image_src( $this->cAkcia->knowledge_settings['obrazok_pri_splneni_kvizu'],'large' );
                    echo "<img src='" . $url[0] . "' width='100%'>";
                
            } else {
                $akcia_tag = $this->akcia_tag;

                if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                    $link_to_quiz_url = 'http://localhost:8888/eventkviz/' . $akcia_tag . '/kwersdfzx/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                } else {
                    $link_to_quiz_url = 'https://eventkviz.sk/kwersdfzx/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                }
                 echo "Nezískali ste dosť bodov na postup a zobrazenie ďalšej indície. Je potrebné dosiahnuť aspoň " . $this->cAkcia->knowledge_settings['min_body_na_postup'] . "bodov.  <a href='" . $link_to_quiz_url . "'>Opakujte kvíz kliknutím na túto linku</a>. <br>";
            }


           //$this->show_link_back($user, $team);

            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'knowledge', 'insert');

            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'knowledge');

            $this->show_seed($user, $akcia, 'knowledge',$team);
        }
    } 

    public function get_correct_knowledge_answers($current_question_id){
        
        $correct_answer1 = get_post_meta( $current_question_id, 'correct-answer-1', true );
        $correct_answer2 = get_post_meta( $current_question_id, 'correct-answer-2', true );
        
        $return_array = array('correct_answer1' => $correct_answer1, 'correct_answer2' => $correct_answer2);
        return $return_array;
        
    }
        
    public function check_correct_knowledge_answer($answer, $iteration_no){
        global $correct_answers;
        
        $array_of_correct_answers = $correct_answers[$iteration_no];
        
        
        if (!empty($answer) && in_array($answer, $array_of_correct_answers)) {
            return true;
        } else {
            return false;
        }
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

        if(array_key_exists($knowledge_string, $_POST)){
            $form_knowledge = $_POST[$knowledge_string];

            if(empty($form_knowledge)) {
                $form_knowledge = 'Odpoveď nebola zadaná';
            }

            echo "<h2>Odpoveď na vedomostnú otázku číslo " . $iteration_no_real . "</h2>";
            $this->post_title = get_the_title( $current_question_id );
             echo '<div>';
             echo '<br><h3>' . $this->post_title . '</h3>';
            $this->show_media_file($current_question_id, false);   

            $this->show_knowledge_answer( $correct_knowledge, $current_question_id);
            echo "Tvoja odpoveď: " . $form_knowledge . '<br>';
            
            $result = $this->check_correct_knowledge_answer($form_knowledge, $iteration_no);
            
            if($result === true && $this->cAkcia->knowledge_settings['zobraz_spravne_uhadnute_odpovede'] === true ) {
                $gained_credits += $credits['corr_answer'];
                $this->show_answer("Odpoveď je správna, hráč získava +" . $credits['corr_answer'] . " bodov", 'knowledge', 'eventkviz_correct_answer');
            } else {
                $this->show_answer("Odpoveď nie je správna, hráč získava 0 bodov.", 'knowledge', 'eventkviz_incorrect_answer');
            }

        } else {
            echo "<h2>Odpoveď na vedomostnú otázku číslo  " . $iteration_no_real . "</h2>";
            $this->show_answer("Správna odpoveď: " . implode(',',$correct_knowledge) , 'knowledge');
            echo "Hráčova odpoveď: Nezadaná. <br>";
        }
            
    }



}