<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_MusicForm_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {

        
        //nechce to fungovat, natahuje sa to preto zatial zo snippetov, kde to funguje
       // add_action( 'wp_enqueue_scripts', array( $this, 'load_my_scripts' ) );
       // add_action( 'wp_enqueue_scripts', array( $this, 'load_autocomplete_script' ) );
        
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'music_form_dynamic', array( $plugin, 'eventkviz_music_form' ) );
    }

    

    public function eventkviz_music_form( $atts ) {

        $value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );


        //global $music_settings;
            
        $user_code = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' ); 
        $this->load_basic_event_settings( $akcia_code);    
        $team_code = $this->set_team_code($user_code, $akcia_code);

       $this->music_quiz_settings($akcia_code,$user_code,$team_code);

       if($this->cAkcia->music_settings['show_entry_form'] === true){

            if($this->cAkcia->all_quizes_settings['select_from_teams_array'] === true && empty($team_code)) {
                //ukaz vyberovy form 
                require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-links.php' );
                $this->cForm = New Eventkviz_AllLinks_Quiz_Class;

                $att['akcia'] = $akcia_code;
                $this->cForm->show_team_links($att, 'music');
                echo "</main></body></html>";
                die;
            }
        } 

            
        $check_result = $this->check_number_of_tries($user_code, $akcia_code,'music',$team_code);

        if($check_result === true) {

            
            
            $number_of_questions = $this->cAkcia->music_settings['pocet_otazok_v_sete'];

            if($this->cAkcia->music_settings['production'] != 'all') {
                
                $args = array(
                    'post_type'   => 'questions-audio',
                    'numberposts' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'production', 
                            'field' => 'slug',
                            'terms' => $this->cAkcia->music_settings['production'] 
                        )
                    )
                );
            } else {
                $args = array(
                    'post_type'   => 'questions-audio',
                    'numberposts' => -1,
                );
            }

            $available_questions = get_posts( $args );

            $number_of_available_questions = count($available_questions)-1;

            $question_set_exists = $this->check_if_questions_set_exists( $akcia_code,$user_code,'music',$team_code);

            if( !$question_set_exists) {
                $this->questions_set = $this->UniqueRandomNumbersWithinRange($number_of_available_questions, $number_of_questions); 
               
            }

            if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                $url = 'http://localhost:8888/eventkviz/audio-quiz-dynamic-evaluation/';
            } else {
                $url = 'https://eventkviz.sk/audio-quiz-dynamic-evaluation/';
            }

            echo '<form action="'. $url . '"method="post">';


            for($i=0;$i<$number_of_questions; $i++) {
                $human_number = $i+1;

                if( $question_set_exists) {
                    $current_question_id = $this->questions_set[$i];
                } else {
                    $current_question_id = $available_questions[$this->questions_set[$i]]->ID;
                }
               

                $media_id = get_post_meta( $current_question_id, 'media', true );
                $audio_file_url = wp_get_attachment_url( $media_id ); 

                echo "<h3>Pieseň #" . $human_number . "</h3>";
                
                $this->show_media_controls($audio_file_url );

                echo "Meno speváka/kapely:";
                echo '<input id="myArtist' . $human_number . '" class="autocomplete1" name="artist' . $human_number . '">';
                echo '<input type="hidden" name="artist' . $human_number . '_key">';


                echo "Názov piesne:";
                echo '<input id="mySong' . $human_number . '" class="autocomplete2" name="song' . $human_number . '">';
                echo '<input type="hidden" name="song' . $human_number . '_key">';

                $questions[] = $current_question_id;
            }

            echo '<input type="hidden" name="team" value = "' . $team_code . '">';
            echo '<input type="hidden" name="user" value = "' . $user_code . '">';
            echo '<input type="hidden" name="akcia" value = "' . $akcia_code . '">';
            $serialized_question_set = serialize($questions);

            echo '<input type="hidden" name="set" value = "' . $serialized_question_set . '">';
            echo '<br><br><input type="submit" value="Oddšlite svoje odpovede na hudobný kvíz">';
            echo '</form>';
        
            if( !$question_set_exists) {
                $this->write_question_set_to_db($serialized_question_set, $akcia_code,'music',$user_code,$team_code );
            }
        }

    }
    
    public function show_media_controls($audio_file_url ){
        echo "<div>";
        echo "<audio controls>";

        echo '<source src="'. $audio_file_url . '" type="audio/mp3">';
        echo 'Your browser does not support the audio element.';
        echo "</audio></div>";
    }

   
    
}





























class Eventkviz_MusicEval_Quiz_Class extends Eventkviz_MusicForm_Quiz_Class{
    
    public function __construct() {

    }

     public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'eval_music_quiz_dynamic', array( $plugin, 'eventkviz_eval_music_quiz' ) );
    }




    public function eventkviz_eval_music_quiz($atts = '') {
            
        global $correct_answers;
        //global $music_settings;
        global $gained_credits;
        
        $akcia = $_POST['akcia'];
        $this->load_basic_event_settings( $akcia);
        $questions = unserialize($_POST['set']);
        //print_r($questions);
        $user = $_POST['user'];
        $team = $_POST['team'];

        
        
        $this->music_quiz_settings($akcia,$user,$team);
        $check_result = $this->check_number_of_tries($user, $akcia,'music',$team);
        
        if($check_result === true) {

            //echo 'artist1:' . $_POST['artist1'] . '<br>';

            for($i=0;$i<count($questions);$i++) {
                $correct_answers[] = $this->get_correct_answer_according_id($questions[$i]);
            }

            /*
            echo "<pre>";
            print_r($_POST);
            echo "</pre>";
            */

            for($i=0;$i<count($questions);$i++) {
                //echo $questions[$i];

                $this->evaluate_combination_music($i, $questions[$i], 1, 'dynamic');
            }
            if(!$gained_credits) $gained_credits = 0;
            $this->show_total_credits_gained($gained_credits, $user, $team, );
            
            if($this->cAkcia->music_settings['min_body_na_postup'] > 0 && $gained_credits >= $this->cAkcia->music_settings['min_body_na_postup']) {
                    echo "Získali ste dosť bodov na postup a zobrazenie ďalšej indície.<br><br>";
                    echo "Vaša ďalšia indícia je:<br><br>";
                    $url = wp_get_attachment_image_src( $this->cAkcia->music_settings['obrazok_pri_splneni_kvizu'],'large' );
                    echo "<img src='" . $url[0] . "' width='100%'>";
                
            } else {
                $akcia_tag = $this->akcia_tag;

                if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                    $link_to_music_quiz_url = 'http://localhost:8888/eventkviz/' . $akcia_tag . '/aqljk/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                } else {
                    $link_to_music_quiz_url = 'https://eventkviz.sk/aqljk/?team=' . $team . '&user=' . $user . '&akcia=' . $akcia_tag;
                }
                 echo "Nezískali ste dosť bodov na postup a zobrazenie ďalšej indície. Je potrebné dosiahnuť aspoň " . $this->cAkcia->music_settings['min_body_na_postup'] . "bodov.  <a href='" . $link_to_music_quiz_url . "'>Opakujte kvíz kliknutím na túto linku</a>. <br>";
            }

            
            // zapis do databazy bodove hodnotenie uzivatela
           $this-> write_results_to_db($user, $team, $akcia, $gained_credits, $_POST['set'], 'music', 'insert');

            $this->send_results_by_email($user, $team,$akcia, $gained_credits, 'music');

            $this->show_seed($user, $akcia, 'music',$team);
        }
        
    }




    public function get_correct_answer_according_id($current_question_id){
        //echo $current_question_id;
        //$artist = get_post_meta( $current_question_id, 'artist-answer-id', true );
        //$song = get_post_meta( $current_question_id, 'song-answer-id', true );
            

        $related_song_id = $this->get_related_ids( $current_question_id, 14 );
        $related_artist_id = $this->get_related_ids( $current_question_id, 15 );

        //echo "The related song ID for question " . $current_question_id . " is: " . $related_song_id . "<br>";
        //echo "The related artist ID for question " . $current_question_id . " is: " . $related_artist_id . "<br>";
        
        //echo 'Artist:' .  $artist . "<br>"; 
        //echo 'Song:' .  $song . "<br>"; 
        $return_array = array('artist' => $related_artist_id, 'song' => $related_song_id);
        return $return_array;
    }


    public function get_correct_answer($iteration_no, $topic) {
            global $correct_answers;
        return $correct_answers[$iteration_no][$topic];
    }

    public function is_in_array_of_correct_answers($id, $topic){
        global $correct_answers;
  
        $key = array_search($id, array_column($correct_answers, $topic));
        //echo "Key:" . $key . "<br>";
        
        return $key;

    }

    function get_artist_name($id){
        
        global $wpdb,$table_prefix;
        $user_ID = get_current_user_id();
        $sql = 'SELECT artist FROM '.$table_prefix.'jet_cct_artists WHERE _ID = '.$id;
        $value = $wpdb->get_var($sql);
        if(!$value) {
            $value = 'Nezadané';
        }
        return $value;
    }

    function get_song_name($id){
        global $wpdb,$table_prefix;
        $user_ID = get_current_user_id();
        $sql = 'SELECT song FROM '.$table_prefix.'jet_cct_songs WHERE _ID = '.$id;
        $value = $wpdb->get_var($sql);
        
        if(!$value) {
            $value = 'Nezadané';
        }
        return $value;
    }

    function check_song_elswhere($form_song){
            global $gained_credits;
            global $used_songs;
            //global $music_settings;
            $credits = $this->cAkcia->music_settings['credits'];
        
            if(!empty($form_song) && is_int($this->is_in_array_of_correct_answers($form_song, 'song')) && !in_array($form_song, $used_songs)) {
                // add credit for correct song on wrong position
                $gained_credits += $credits['corr_song_in_array'];
               $this->show_answer("Correct song on wrong position, user gets +" . $credits['corr_song_in_array'] . " points", 'music');
                $used_songs[] = $form_song;
                return 2;
            } elseif(in_array($form_song, $used_songs)) {
                $this->show_answer("Duplicate, user gets +0 points", 'music');
                return 1;
            } else {
                return 0;
            }
        
    }
        
    function check_artist_elswhere($form_artist){
            
            global $gained_credits;
            global $used_artists;
        
            //global $music_settings;
            $credits = $this->cAkcia->music_settings['credits'];
        
            if(!empty($form_artist) && is_int($this->is_in_array_of_correct_answers($form_artist, 'artist'))  && !in_array($form_artist, $used_artists)) {
                // add credit for correct artist on wrong position
                $gained_credits += $credits['corr_art_in_array'];
                $this->show_answer("Correct artist on wrong position, user gets +" . $credits['corr_art_in_array'] . " points", 'music');
                $used_artists[] = $form_artist;
                return 2;
            } elseif(in_array($form_artist, $used_artists)) {
                $this->show_answer("Duplicate, user gets +0 points", 'music');
                return 1;
            } else {
                return 0;
            }

        
    }

    public function evaluate_combination_music($iteration_no, $current_question_id, $add_1_for_human_readable_numbers=0, $type='static' ) {
            global $correct_answers;
            global $gained_credits;
            global $used_artists;
            global $used_songs;
            //global $music_settings;
            $credits = $this->cAkcia->music_settings['credits'];
        //print_r($credits);
            if (!is_array($used_artists)) $used_artists = array();
            if (!is_array($used_songs)) $used_songs = array();
        
        if($type == 'static') {
            $correct_artist = $correct_answers[$iteration_no]['artist'];
            $correct_song = $correct_answers[$iteration_no]['song'];
        } else {
            $correct_artist = $this->get_correct_answer($iteration_no, 'artist');
            $correct_song = $this->get_correct_answer($iteration_no, 'song');
        }
        
        $iteration_no_real = $iteration_no+$add_1_for_human_readable_numbers;
        
        $artist_string = 'artist'.$iteration_no_real . '_key';
        $song_string = 'song'.$iteration_no_real.'_key';

        $form_artist = $_POST[$artist_string];
        $form_song = $_POST[$song_string ];
        
        echo "<h2>Odpoveď pre pieseň číslo " . $iteration_no_real . "</h2>";
        
        $media_id = get_post_meta( $current_question_id, 'media', true );
        $audio_file_url = wp_get_attachment_url( $media_id ); 
        $this->show_media_controls($audio_file_url );

        $this->show_answer("Správna odpoveď: " .  $this->get_artist_name($correct_artist) . ' - ' . $this->get_song_name($correct_song), 'music');
        echo "<div class=''>Vaša odpoveď: " . $this->get_artist_name($form_artist) . ' - ' . $this->get_song_name($form_song)  . '</div>';
        
        if(!empty($form_artist) && $form_artist == $correct_artist && $form_song == $correct_song) {
            // artist & song on correct position
            // add credit for correct artist song
            
            if(!in_array($form_artist, $used_artists) || !in_array($form_song, $used_songs)) {
                $gained_credits += $credits['corr_art_corr_pos_corr_song_corr_pos'];
            
                $this->show_answer("Spevák/skupina boli určené správne, získavate +" . $credits['corr_art_corr_pos_corr_song_corr_pos'] . " bodov", 'music');

                $used_artists[] = $form_artist;
                $used_songs[] = $form_song;
            } elseif (empty($form_artist) ) {
                $this->show_answer("Odpoveď nebola zadaná", 'music');
            } else {
                $this->show_answer("Umelec, alebo pieseň už bola započítané predtým.", 'music');
            }
            
            
        } elseif(!empty($form_artist) &&  $form_artist == $correct_artist && $form_song != $correct_song) {
            // only correct artist on correct position
            
            // add credit for correct artist on correct place only
            
            if(!in_array($form_artist, $used_artists)) {
                $gained_credits += $credits['corr_art_corr_pos_incorr_song'];
                $this->show_answer("Only correct artist on correct position, user gets +" . $credits['corr_art_corr_pos_incorr_song'] . " points", 'music');
                $used_artists[] = $form_artist;
            }
            // check if song is elswhere in  array of correct answers
            
            $this->check_song_elswhere($form_song);
            
            
        } elseif(!empty($form_song) && $form_artist != $correct_artist && $form_song == $correct_song) {
            // only correct song on correct position
            
            // add credit for correct song on correct place
            if( !in_array($form_song, $used_songs)) {
                $gained_credits += $credits['incorr_art_corr_song_corr_pos'];
                $this->show_answer("Only correct song on correct position, user gets +" . $credits['incorr_art_corr_song_corr_pos'] . " points", 'music');
                $used_songs[] = $form_song;
            }
            
            // check if artist is elswhere in  array of correct answers

            
            $this->check_artist_elswhere($form_artist);
                
            
        } else {
            $songres = $this->check_song_elswhere($form_song);
            //echo $songres;
            
            $artisres = $this->check_artist_elswhere($form_artist);
            //echo $artisres;
            
            if ($songres < 1 &&  $artisres < 1) {
                $this->show_answer("Nesprávna pieseň, nesprávny umelec, získavate +0 bodov", 'music');
            }
            
            
        }
            
    }

       public function load_autocomplete_script() {
        //wp_enqueue_script( 'jquery-ui-autocomplete' );
        //wp_add_inline_script( 'jquery-ui-autocomplete', $this->load_autocomplete_js() );
    }

    public function load_autocomplete_js(){

        $script = "$(function () {";
        $script .= "var artists = {";	
        global $wpdb;
        $table_name = 'pmgonijet_cct_artists';
        $artists_array = $wpdb->get_results( "SELECT * FROM $table_name" );

                
                /*foreach ($artists_array as $item){ 
                    $script .= '"' . addslashes($item->artist) . '":' . $item->_ID . ',';
                    
                    }*/

        $script .= '"Maros" : 2,';
        $script .= '"Jarko" : 4';
        $script .= "};";
            
        $script .= "var songs = {";
        $table_name = 'pmgonijet_cct_songs';
        $songs_array = $wpdb->get_results( "SELECT * FROM $table_name" );
/*
                    foreach ($songs_array as $item){ 
                        $script .= '"' . addslashes($item->song) . '":' . $item->_ID . ',';
                        }
                        */
        $script .= '"Someone" : 2,';
        $script .= '"Ymca" : 4';
        $script .= "};";
                
        $script .= "var movies = {";
        $table_name = 'pmgonijet_cct_movies';
        $movies_array = $wpdb->get_results( "SELECT * FROM $table_name" );
                    /*
                    foreach ($movies_array as $item){ 
                        $script .= '"' . addslashes($item->original_title) . '":' . $item->_ID . ',';
                        }
                        */
        $script .= '"Rio" : 2,';
        $script .= '"Batman" : 4';

        $script .= "};";
                
        $script .= '$(".autocomplete1").autocomplete({';
        $script .= 'source: Object.keys(artists),';
        $script .= 'select: function( event, ui ) {';
        $script .= 'var key = ui.item.value;';
        $script .= 'var value = artists[key];';
        $script .= '$(this).next("input[type=\'hidden\']").val(artists[key]);';
        $script .= '}';
        $script .= "});";

        $script .= '$(".autocomplete2").autocomplete({';
        $script .= 'source: Object.keys(songs),';
        $script .= 'select: function( event, ui ) {';
        $script .= 'var key = ui.item.value;';
        $script .= 'var value = songs[key];';
        $script .= '$(this).next("input[type=\'hidden\']").val(songs[key]);';
        $script .= '}';
        $script .= "});";
            
        $script .= '$(".autocomplete3").autocomplete({';
        $script .= 'source: Object.keys(movies),';
        $script .= 'select: function( event, ui ) {';
        $script .= 'var key = ui.item.value;';
        $script .= 'var value = movies[key];';
        $script .= '$(this).next("input[type=\'hidden\']").val(movies[key]);';
                //$(this).next("input[type='hidden']").val(key);';
        $script .= '}';
        $script .= "});";

        $script .= "});";
    }

  

    public function load_my_scripts() {
        wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
        //wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-1.12.4.js', array(), '1.12.4', true );
        wp_enqueue_script( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array( 'jquery' ), '1.12.1', true );
    }


}
/*


//Load artists and songs to autocomplete SELECT
add_action( 'wp_head', function () { ?>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <script>
        $(function () {
            var artists = {
					<?php		
					global $wpdb;
					$table_name = 'pmgonijet_cct_artists';
					$artists_array = $wpdb->get_results( "SELECT * FROM $table_name" );

					foreach ($artists_array as $item){ 
						echo '"' . addslashes($item->artist) . '":' . $item->_ID . ',';
						}

					?>
					};
			
			 var songs = {
					<?php		
					
					$table_name = 'pmgonijet_cct_songs';
					$songs_array = $wpdb->get_results( "SELECT * FROM $table_name" );

					foreach ($songs_array as $item){ 
						echo '"' . addslashes($item->song) . '":' . $item->_ID . ',';
						}

					?>
					};
			
			var movies = {
					<?php		
					
					$table_name = 'pmgonijet_cct_movies';
					$movies_array = $wpdb->get_results( "SELECT * FROM $table_name" );

					foreach ($movies_array as $item){ 
						echo '"' . addslashes($item->original_title) . '":' . $item->_ID . ',';
						}

					?>
					};
			
            $(".autocomplete1").autocomplete({
                source: Object.keys(artists),
                select: function( event, ui ) {
                    var key = ui.item.value;
                    var value = artists[key];
                    $(this).next("input[type='hidden']").val(artists[key]);
                    //$(this).next("input[type='hidden']").val(key);
                }
            });
            $(".autocomplete2").autocomplete({
                source: Object.keys(songs),
                select: function( event, ui ) {
                    var key = ui.item.value;
                    var value = songs[key];
                    $(this).next("input[type='hidden']").val(songs[key]);
					//$(this).next("input[type='hidden']").val(key);
                }
            });
			$(".autocomplete3").autocomplete({
                source: Object.keys(movies),
                select: function( event, ui ) {
                    var key = ui.item.value;
                    var value = movies[key];
                    $(this).next("input[type='hidden']").val(movies[key]);
					//$(this).next("input[type='hidden']").val(key);
                }
            });
        });
    </script>
<?php } );
*/


 