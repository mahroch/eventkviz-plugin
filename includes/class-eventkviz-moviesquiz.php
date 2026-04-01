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
        $team_code = $this->set_team_code($user_code, $akcia_code);
        //$this->movies_quiz_settings($akcia_code,$user_code,$team_code);

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
            
            if($this->cAkcia->movies_settings['production'] != 'all') {
                
                $args = array(
                    'post_type'   => 'questions-movies',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'production', 
                            'field' => 'slug',
                            'terms' => $this->cAkcia->movies_settings['production'] 
                        )
                    )
                );
            } else {
                $args = array(
                    'post_type'   => 'questions-movies',
                    'numberposts' => -1,
                );
            }
            
            $available_questions = get_posts( $args );
            //TODO overit vyber filmov, ci sa tam nerobi len jednoducho asociovany array - potrebujeme asi ID filmov ako keys 

            $number_of_available_questions = count($available_questions)-1;

            $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,'movies',$user_code,$team_code);

            if( !$question_set_exists) {
                $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $number_of_questions); 
               
            }

            if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                $url = 'http://localhost:8888/eventkviz/movies-quiz-dynamic-evaluation/';
            } else {
                $url = 'https://eventkviz.sk/movies-quiz-dynamic-evaluation/';
            }

            echo '<form action="'. esc_url($url) . '" method="post">';


            for($i=0;$i<$number_of_questions; $i++) {
                $human_number = $i+1;

                if( $question_set_exists) {
                    $current_question_id = $this->questions_set[$i];
                } else {
                    $current_question_id = $available_questions[$this->questions_set[$i]]->ID;
                }

                $media_id = get_post_meta( $current_question_id, 'media', true );
                $movie_file_url = wp_get_attachment_url( $media_id ); 

                echo "<h3>Film #" . $human_number . "</h3>";
                //echo "<h3>Movie ID" . $current_question_id . "</h3>";
                
                
                $this->show_media_file($movie_file_url);

                

                echo "<br><br>Názov filmu: <br><br>";
                //echo $current_question_id;
                if ($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
                     echo '<input id="myMovie' . $human_number . '" class="autocomplete3" name="movie' . $human_number . '">';
                } else {
                    $this->print_form_question($current_question_id, $human_number);
                }
                echo '<input type="hidden" name="movie' . $human_number . '_key">';

                $questions[] = $current_question_id;
            }

            echo '<input type="hidden" name="team" value = "' . esc_attr($team_code) . '">';
            echo '<input type="hidden" name="user" value = "' . esc_attr($user_code) . '">';
            echo '<input type="hidden" name="akcia" value = "' . esc_attr($akcia_code) . '">';
            $serialized_question_set = json_encode($questions);

            echo '<input type="hidden" name="set" value = "' . esc_attr($serialized_question_set) . '">';
            
            echo '</br></br>';
            echo '<input type="submit" value="Odoslať odpovede na vyhodnotenie">';
            echo '</form>';

            if( !$question_set_exists) {
                $this->write_question_set_to_db( $serialized_question_set, $akcia_code,'movies',$user_code,$team_code );
            }
        }
    }
    public function show_media_file($movie_file_url){
        echo "<div>";
        echo "<video width='500' controls>";
        echo '<source src="'. $movie_file_url . '" type="video/mp4">';
        echo 'Your browser does not support the video element.';
        echo "</video></div>";
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

        $questions = json_decode(wp_unslash($_POST['set']), true);
        //print_r($questions);
        $user = $_POST['user'];
        $team = $_POST['team'];
        
        //$this->movies_quiz_settings($akcia,$user,$team);
        $check_result = $this->check_number_of_tries($user, $akcia,'movies',$team);
        
        if($check_result === true) {	
            //echo 'artist1:' . $_POST['artist1'] . '<br>';

            for($i=0;$i<count($questions);$i++) {

                 if ($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
                    $correct_answers[] = $this->get_related_ids( $questions[$i], 17 );
                } else {
                    $correct_answers[] = $this->get_correct_movies_answers( $questions[$i]);
                }
            }


            for($i=0;$i<count($questions);$i++) {
                //echo $questions[$i];

                $this->evaluate_movie($questions[$i], $i, 1, 'dynamic');
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team);

            if($this->cAkcia->movies_settings['min_body_na_postup'] > 0 && $gained_credits >= $this->cAkcia->movies_settings['min_body_na_postup']) {
                    echo "Získali ste dosť bodov na postup a zobrazenie ďalšej indície.<br><br>";
                    echo "Vaša ďalšia indícia je:<br><br>";
                    $url = wp_get_attachment_image_src( $this->cAkcia->movies_settings['obrazok_pri_splneni_kvizu'],'large' );
                    echo "<img src='" . esc_url($url[0]) . "' width='100%'>";
                
            } else {
                $akcia_tag = $this->akcia_tag;

                if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                    $link_to_music_quiz_url = 'http://localhost:8888/eventkviz/' . $akcia_tag . '/merdfghh/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                } else {
                    $link_to_music_quiz_url = 'https://eventkviz.sk/merdfghh/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                }
                 echo "Nezískali ste dosť bodov na postup a zobrazenie ďalšej indície. Je potrebné dosiahnuť aspoň " . $this->cAkcia->movies_settings['min_body_na_postup'] . "bodov.  <a href='" . $link_to_music_quiz_url . "'>Opakujte kvíz kliknutím na túto linku</a>. <br>";
            }



            // zapis do databazy bodove hodnotenie uzivatela
            $this->write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'movies', 'insert');
            $this->send_results_by_email($user, $team, $akcia, $gained_credits, 'movies');	
            $this->show_seed($user, $akcia, 'movies',$team);
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
            $this->show_answer("Správny fipm na zlej pozícii, hráč získava +" . $credits['corr_movie_wrong_pos'] . " bodov", 'movies');
            $used_movies[] = $form_movie;
            return 2;
        } elseif(in_array($form_movie, $used_movies)) {
            $this->show_answer("Duplicita, hráč získava +0 bodov", 'movies');
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
        } else {
            $movie_string = 'movies'.$iteration_no_real;
            $form_movie = $_POST[$movie_string];
        }
        
        echo "<h2>Odpoveď pre film číslo  " . $iteration_no_real . "</h2>";

        $media_id = get_post_meta( $current_question_id, 'media', true );
        $movie_file_url = wp_get_attachment_url( $media_id );  
        $this->show_media_file($movie_file_url);

        if($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
            $this->show_answer("Správna odpoveď: " . $this->get_movie_name($correct_movie), 'movies');
        } else {
            $this->show_answer("Správna odpoveď: " . $correct_movie, 'movies');
        }
        echo "Odpoveď hráča: ";


        if($this->cAkcia->movies_settings['movies_quiz_type'] == "full") {
            $odpoved_hraca = $this->get_movie_name($form_movie) . '<br><br>';
        } else {
            $odpoved_hraca = addslashes($form_movie) . '<br><br>';
        }
        
        echo $odpoved_hraca;
        
      
         

        if($correct_movie == $form_movie ) {
            // movie  correct 
            // add credit for correct movie
            
            //TODO: asi vsade nielen movie - ak je uz film zapocitany ale tu sa objavi na spravnom mieste, tak je to za viac bodov a mali by dostat tychto viac bodov
            if(!empty($form_movie) && !in_array($form_movie, $used_movies)) {
                $gained_credits += $credits['corr_movie'];
                $this->show_answer("Film určený správne, hráč získava +" . $credits['corr_movie'] . " bodov", 'movies');
                $used_movies[] = $form_movie;
            } elseif(empty($form_movie)) {
                
                $this->show_answer("Odpoveď nebola zadaná", 'movies');
            } else {
                $this->show_answer("Film zarátaný už predtým", 'movies');
            }
            
        } else {
            if (empty($form_movie)) {
                $this->show_answer("Odpoveď nebola zadaná, hráč získava +0 bodov", 'movies');
            } else {
                $movieres = $this->check_movie_elswhere($form_movie);
                if ($movieres < 1 ) {
                    $this->show_answer("Film nesprávne určený, hráč získava +0 bodov", 'movies');
                }
            }
            
        }
            
    }


}
