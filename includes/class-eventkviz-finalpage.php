<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_Finalpage_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {

        
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'show_final_page', array( $plugin, 'show_final_page' ) );
    }


    public function show_final_page($atts = '') {
        $value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );
        
        $akcia_code = $value['akcia']; 
        
        $this->load_basic_event_settings( $akcia_code);

       
        
        if(is_array($_POST) && !empty($_POST['user'])) {
            $user_code  = $this->standardize($_POST['user']);
        } else {
            $user_code  = '';
        }
        
         if(is_array($_POST) && !empty($_POST['team'])) {
            $team_code  = $this->standardize($_POST['team']);
        } elseif (!empty($user_code) &&  !$this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {
            $team_code = $this->set_team_code($user_code, $akcia_code);
        } else {
            $team_code = '';
        }
            
        if(empty($user_code) && empty($team_code) ){
            // je tu prvy krat, len ide zadat kod
            /*
            echo "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->";
            echo '<video autoplay id="my-video" controls>';
                    echo '<source src="' . BASE_URL . 'wp-content/uploads/2023/05/correct2.mp4" type="video/mp4">';
                    echo 'Your browser does not support the video tag.';
                 echo '</video>';

            echo '<script>';
            echo 'var video = document.getElementById("my-video");';
            echo 'video.autoplay = true;';
            echo 'video.load();';
            echo '</script>';
*/

            echo "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->";
            echo '<video autoplay muted id="my-video" controls>';
                echo '<source src="' . get_site_url() . '/wp-content/uploads/2023/05/correct2.mp4" type="video/mp4">';
                echo 'Your browser does not support the video tag.';
            echo '</video>';

            echo '<script>';
            echo 'var video = document.getElementById("my-video");';
            echo 'video.autoplay = true;';
            echo 'video.muted = true;'; // muted je potrebné pre autoplay v moderných browseroch (Chrome/Firefox atď.)
            echo 'video.load();';
            echo 'video.play();'; // extra play pre istotu
            echo '</script>';


                echo "<h2>Please fill this form:</h2>";
                $base_url = $this->cAkcia->all_quizes_settings['no_quiz_places_urls'][$value['type']];
                echo '<form action="' . $base_url . '" method="post">';
            
                    if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
                            echo '<label for="user">Enter your user code:</label>';
                            echo '<input type="text" id="user" name="user" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
                    }	

                    if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false){
                            echo '<label for="team">Enter your team name:</label>';
                            echo '<input type="text" id="team" name = "team" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
                    } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true){

                        echo '<label for="team">Select your team:</label>';
                        echo '<select id="team">';

                                $keys = array_keys($this->cAkcia->all_quizes_settings['select_teams']); // Get an array of keys
                                $index = 0;
                                while($index < count($keys)) {
                                    $key = $keys[$index];
                                    $valu = $this->cAkcia->all_quizes_settings['select_teams'][$key];
                                    echo "<option value='" . $key . "'>" . $valu . "</option>";
                                    $index++;
                                }	
                        echo '</select>';

                    } else {

                    }
                    echo '<br><br><h2>Now enter the seeds you found at each place.</h2>';
                    echo '<p>There is no need to fill all the seeds. Enter the ones you gained and leave the rest empty. However, you should enter at least 3 seeds to try to open the treasure chest. Please, be sure to enter the seeds at their correct input fields, otherwise they will not be accepted as correct.</p>';
            
                    foreach($this->cAkcia->all_quizes_settings['places'] as $place) {
                        echo 'Code for place: <b>' . $place[1] . '</b>';
                        echo '<input = type="text" name="' . $place[0]. '"><br><br>';
                    }
                    echo '<input type="hidden" name="akcia" value = "' . $akcia_code . '">';
                    echo '<br><br><input type="submit" value = "Open the treasure ....">';
                    echo '</form>';


                
        } elseif (!empty($user_code) || !empty($team_code)) {
            // zadal formular over ci su seedy spravne

            $check_result = $this->check_number_of_tries($user_code, $akcia_code,'final',$team_code, '<h1>We are so sorry :( </h1>Limit of tries to enter correct seeds was reached. ');

            if($check_result) {

                $seeds = $this->get_all_seeds($user_code, $akcia_code, $team_code);
                $correct_codes = 0;
                
                foreach($seeds as $place => $seed) {
                    if ($this->standardize($_POST[$place]) == $this->standardize($seed)) {
                        $places_status[$place] = array (true, $this->standardize($_POST[$place])) ;
                        $correct_codes++;
                    } else {
                        $places_status[$place] = array (false, $this->standardize($_POST[$place])) ;
                    }
                }

                if($correct_codes >= $this->cAkcia->all_quizes_settings['minimal_number_of_correct_seeds']) {
                    //echo 'Spravne.';
                    //https://www.youtube.com/watch?v=BCJ6tGBZiH8
                    //https://www.youtube.com/shorts/I-j0HpGm5OA 
                    /*The scene with the treasure chest in "Pirates of the Caribbean: The Curse of the Black Pearl" occurs at approximately 18 minutes and 30 seconds into the movie. 
                    echo "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->";
                    echo '<video autoplay controls="controls">';
                        echo '<source src="' . get_site_url()  . 'wp-content/uploads/2023/05/correct1.mp4" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                    echo '</video>';
                    */

                     echo "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->";
                    echo '<video autoplay muted  id="my-video" controls>';
                        echo '<source src="' . get_site_url() . '/wp-content/uploads/2023/05/correct1.mp4" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                    echo '</video>';

                    echo '<script>';
                    echo 'var video = document.getElementById("my-video");';
                    echo 'video.autoplay = true;';
                    echo 'video.muted = true;'; // muted je potrebné pre autoplay v moderných browseroch (Chrome/Firefox atď.)
                    echo 'video.load();';
                    echo 'video.play();'; // extra play pre istotu
                    echo '</script>';


                     echo '<h2>Yoooohooooo!!!! <br><br> You opened the Jack Sparrow\'s chest. <br><br>CONGRATULATION :) </h2>';
                     echo '<h2>Now run to get some quality pirate rum and celebrate it with your team mates. </h2>';

                    $gained_credits_final = $this->cAkcia->all_quizes_settings['credits']['final'];
                    $gained_credits_chest_success= $this->cAkcia->all_quizes_settings['credits']['chest_success'];
                    

                    $this->show_answer("For reaching final place, user gets +" . $gained_credits_final . " points", 'final');
                    $this->show_answer("For opening the treasure, user gets +" . $gained_credits_chest_success . " points", 'treasure_open');
                    $total_points =$gained_credits_final+$gained_credits_chest_success;

                    $check_result = $this->check_number_of_tries($user_code, $akcia_code,'final',$team_code);

                    if($check_result) {
                        $this->write_results_to_db($user_code, $team_code, $akcia_code, $total_points, '', 'final', 'insert');

                        $this->send_results_by_email($user_code, $team_code, $akcia_code, $total_points, 'final_chest_open');
                    }
                    


                } else {
                    /*
                    echo 'Nespravne.';
                    echo "<script>";
                    echo " var timer = setTimeout(function() {";
                        $url = 'http://localhost:8888/eventkviz/samorin/pidsfsdffss/?user=' . $user_code . '&team=' . $team_code . '&show=results'
                            echo "window.location='" . $url . "'";
                        echo "}, 3000);";
                    echo "</script>";
                        */
                    

                     echo "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->";
                    echo '<video autoplay muted  id="my-video" controls>';
                        echo '<source src="' . get_site_url() . '/wp-content/uploads/2023/05/incorrect.mp4" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                    echo '</video>';

                    echo '<script>';
                    echo 'var video = document.getElementById("my-video");';
                    echo 'video.autoplay = true;';
                    echo 'video.muted = true;'; // muted je potrebné pre autoplay v moderných browseroch (Chrome/Firefox atď.)
                    echo 'video.load();';
                    echo 'video.play();'; // extra play pre istotu
                    echo '</script>';


                    echo '<h2>The chest will not open. You did not provide enough correct seeds.</h2>';

                    foreach ($places_status as $plac => $status_array) {
                        if($status_array[0] == true) {
                            echo 'Seed for point of <b>' . $this->cAkcia->all_quizes_settings['names_of_places'][$plac] . ':</b> ' . strtoupper($status_array[1]) . ' - <span class = "seed_correct">correct</span><br><br>'; 
                        } else {
                            if(!empty($status_array[1])) {
                                echo 'Seed for point of <b>' . $this->cAkcia->all_quizes_settings['names_of_places'][$plac] . ':</b> ' . strtoupper($status_array[1]) . ' - <span class = "seed_incorrect">incorrect</span><br><br>';
                            } else {
                                echo 'Seed for point of <b>' . $this->cAkcia->all_quizes_settings['names_of_places'][$plac] . ':</b> ' . strtoupper($status_array[1]) . ' - <span class = "seed_notprovided">not provided</span><br><br>';
                            }
                            
                        }
                    }

                    $gained_credits_final = $this->cAkcia->all_quizes_settings['credits']['final'];
                    $this->show_answer("For reaching final place, user gets +" .  $gained_credits_final  . " points", 'final_chest_not_open');

                    $check_result = $this->check_number_of_tries($user_code, $akcia_code,'final',$team_code);

                    

                    if($check_result) {
                         echo '<h2>You can correct the seeds or get some more and come back again. You still have ' . $this->zostava_pocet_pokusov-1 . ' more tries. </h2>';
                        $this->write_results_to_db($user_code, $team_code, $akcia_code, $gained_credits_final, '', 'final', 'insert');

                        $this->send_results_by_email($user_code, $team_code, $akcia_code, $gained_credits_final, 'final_chest_closed');

                    }

                }
            }
        } else {
            // tento stav by nemal nastat
            echo 'There is some problem ...';
        }
    }
}