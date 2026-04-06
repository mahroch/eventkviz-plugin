<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_Seedpage_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {


        
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'show_seed_page', array( $plugin, 'show_seed_page' ) );
    }


    public function show_seed_page($atts = '') {

        
		$value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );
        
        
       // global $all_quizes_settings;
       
        $this->load_basic_event_settings( $value['akcia']);
        //$this->all_quizes_settings($value['akcia']);
        
        $user_code = get_query_var( 'user' );
        $akcia_code = get_query_var( 'akcia' ); 
        
        if(empty($user_code) && empty($akcia_code)){
            // je tu prvy krat, len ide zadat kod
            // 



                if($this->cAkcia->all_quizes_settings['startup_form'] === true){

                echo "<h2>Please fill this form, your code will be shown on submit.</h2>";

                    if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
                            echo '<label for="inputField1">Enter your user code:</label>';
                            echo '<input type="text" id="inputField1" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
                    }	

                    if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false){
                            echo '<label for="inputField2">Enter your team name:</label>';
                            echo '<input type="text" id="inputField2" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
                    } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true){

                        echo '<label for="inputField2">Select your team:</label>';
                        echo '<select id="inputField2">';

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

                    echo '<br><button id="myButton">Show me the code</button>';

                    echo '<script>';
                                echo "const button = document.getElementById('myButton');";
                                echo "button.addEventListener('click', function(event) {";
                                echo 'event.preventDefault();';

                        if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
                            echo 'const user = document.getElementById("inputField1").value;';
                            echo 'const team = document.getElementById("inputField2").value;';
                        } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
                            echo 'const user = "";';
                            echo 'const team = document.getElementById("inputField2").value;';
                        } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === false){
                            echo 'const user = document.getElementById("inputField1").value;';
                            echo 'const team = "";';
                        }

                        echo 'const akcia = "' . $value['akcia'] . '";';


                        // Create a link element to display the input values as arguments
                        $base_url = $this->cAkcia->all_quizes_settings['no_quiz_places_urls'][$value['type']];
                        echo 'const url = "' . $base_url . '?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';

                        echo 'window.location.href = url;';
                        echo ' });';

                echo '</script>';

                }
        } elseif (!empty($user_code)) {
            // zadal formular ukaz mu seed
            
            $team_code = $this->set_team_code($user_code, $akcia_code);
            $this->set_seed_for_user_or_team($user_code, $akcia_code,$team_code);
            $this->show_seed($user_code, $akcia_code, $value['type'],$team_code);

            if(!empty($value['type'])) {
                $gained_credits = $this->cAkcia->all_quizes_settings['credits'][$value['type']];
            } else {
                $gained_credits = $this->cAkcia->all_quizes_settings['credits']['unspecified'];
            }

            $this->show_answer("For this place, user gets +" . $gained_credits . " points", $value['type']);

            $this->write_results_to_db($user_code, $team_code, $akcia_code, $gained_credits, '', $value['type'], 'insert');

            $this->send_results_by_email($user_code, $team_code, $akcia_code, $gained_credits, $value['type']);

        } else {
            // tento stav by nemal nastat
            echo 'There is some problem ...';
        }
        

    }
}