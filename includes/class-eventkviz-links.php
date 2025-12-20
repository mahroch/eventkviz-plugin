<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );


class Eventkviz_OneLink_Quiz_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {
       
    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'show_link_to_quiz', array( $plugin, 'show_link_to_quiz' ) );
    }

    public function show_link_to_quiz($atts = '') {
        
        $value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );
        
        $this->load_basic_event_settings($value['akcia']);
        
        //global $all_quizes_settings;
        //global $music_settings;
        //global $movies_settings;
       // global $knowledge_settings;
       // global $sudoku_settings;
        /*
        $this->cAkcia->all_quizes_settings($value['akcia']);
        $this->cAkcia->music_quiz_settings($value['akcia']);
        $this->cAkcia->movies_quiz_settings($value['akcia']);
        $this->cAkcia->knowledge_quiz_settings($value['akcia']);
        $this->cAkcia->sudoku_quiz_settings($value['akcia']);
        */
        if($this->cAkcia->all_quizes_settings['startup_form'] === true){
            
        echo "<h1>Vitajte na " . $this->cAkcia->all_quizes_settings['names_of_places'][$value['type']]  . "</h1>";
        echo "<h2>Prosíme, vyplňte tento formulár, link na váš " . $value['type']  . " kvíz sa zobrazia po odoslaní formulára.</h2>";
        echo "<form name='eventkviz_links'>";    
            
            
            if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
                    echo '<label for="inputField1">Zadaj svoj používateľský kód, alebo meno:</label>';
                    echo '<input type="text" value= "' . $_GET['user'] . '" id="inputField1" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
            }	

            if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false){
                    echo '<label for="inputField2">Zadajte meno vášho tímu:</label>';
                    echo '<input type="text" value= "' . $_GET['team'] . '" id="inputField2" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true){

                echo '<label for="inputField2">Vyberte svoj tím:</label>';
                echo '<select id="inputField2">';

                        $keys = array_keys($this->cAkcia->all_quizes_settings['select_teams']); // Get an array of keys
                        $index = 0;
                        while($index < count($keys)) {
                            $key = $keys[$index];
                            $valu = $this->cAkcia->all_quizes_settings['select_teams'][$key];
                            if($_GET['team'] == $key) {
                                $selected = 'selected';
                            }
                            echo "<option value='" . $key . "' " . $selected . ">" . $valu . "</option>";
                            $index++;
                            $selected = '';
                        }	
                echo '</select>';

            } else {

            }

            echo '<br><button id="myButton">Ukáž mi ' . $value['type']  . ' kvíz</button>';
            echo '</form>';
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
                // 

                if($_SERVER['HTTP_HOST'] == 'localhost:8888') {

                    if($this->cAkcia->music_settings['music_quiz_active'] === true && $value['type'] == 'music') {
                        echo 'const url = "http://localhost:8888/eventkviz/aqljk/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    } 
                    
                    if($this->cAkcia->movies_settings['movies_quiz_active'] === true && $value['type'] == 'movies') {
                        echo 'const url = "http://localhost:8888/eventkviz/merdfghh/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }
                    
                    if($this->cAkcia->knowledge_settings['knowledge_quiz_active'] === true && $value['type'] == 'knowledge') {
                        echo 'const url = "http://localhost:8888/eventkviz/kwersdfzx/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }
                
                    if($this->cAkcia->sudoku_settings['sudoku_quiz_active'] === true && $value['type'] == 'sudoku') {
                        echo 'const url = "http://localhost:8888/eventkviz/sweertydfd/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }
                } else {
                  
                    if($this->cAkcia->music_settings['music_quiz_active'] === true && $value['type'] == 'music') {
                        echo 'const url = "https://eventkviz.sk/aqljk/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    } 
                    
                    if($this->cAkcia->movies_settings['movies_quiz_active'] === true && $value['type'] == 'movies') {
                        echo 'const url = "https://eventkviz.sk/merdfghh/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }
                    
                    if($this->cAkcia->knowledge_settings['knowledge_quiz_active'] === true && $value['type'] == 'knowledge') {
                        echo 'const url = "https://eventkviz.sk/kwersdfzx/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }
                
                    if($this->cAkcia->sudoku_settings['sudoku_quiz_active'] === true && $value['type'] == 'sudoku') {
                        echo 'const url = "https://eventkviz.sk/sweertydfd/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    }

                }
            
                echo 'window.location.href = url;';
                echo ' });';
        
        echo '</script>';

        }
        

    }
}



class Eventkviz_AllLinks_Quiz_Class  extends Eventkviz_Quiz_Class{
    
    public function __construct() {

    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'show_team_links', array( $plugin, 'show_team_links' ) );
    }

    public function show_team_links($atts = '', $single_quiz = '') {

        $preselected_user = '';

        $value = shortcode_atts(array(
            'type'  => '',
            'akcia' => ''
        ), $atts);

        $this->load_basic_event_settings($value['akcia']);

        /*
        $this->cAkcia->all_quizes_settings($value['akcia']);
        $this->cAkcia->music_quiz_settings($value['akcia']);
        $this->cAkcia->movies_quiz_settings($value['akcia']);
        $this->cAkcia->knowledge_quiz_settings($value['akcia']);
        $this->cAkcia->sudoku_quiz_settings($value['akcia']);
*/

        if ($this->cAkcia->all_quizes_settings['startup_form'] === true) {

            echo "<h2>Prosím, vyplňte tento formulár – odkaz na kvíz(y) sa zobrazí po odoslaní formulára.</h2>";

            if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true) {
                echo '<label>Zadaj svoje meno (používateľský kód):</label><br><br>';
                if (!empty($_GET['user'])) {
                    $preselected_user = $_GET['user'];
                }
                echo '<input type="text" value="' . esc_attr($preselected_user) . '" id="inputField1"
                    oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,\'\');checkFields();">';
            }

            if ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true &&
                $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false) {

                echo '<label>Zadajte meno svojho tímu:</label>';
                echo '<input type="text" value="' . esc_attr($_GET['team'] ?? '') . '" id="inputField2"
                    oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,\'\');checkFields();">';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true &&
                    $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true) {

                echo '<label>Vyberte svoj tím:</label><br><br>';
                echo '<center><select id="inputField2" onchange="checkFields();" style="width:30%;"></center>';

                foreach ($this->cAkcia->all_quizes_settings['select_teams'] as $k => $v) {
                    $sel = (($_GET['team'] ?? '') == $k) ? 'selected' : '';
                    echo '<option value="' . esc_attr($k) . '" ' . $sel . '>' . esc_html($v) . '</option>';
                }

                echo '</select>';
            }

            echo '<br><br><button type="button" id="submitBtn" onclick="submitClicked()">Pokračovať</button>';
            echo '<p id="output"></p>';

            echo '<script>
            function checkFields() {
                let user = document.getElementById("inputField1")?.value.trim() || "";
                let team = document.getElementById("inputField2")?.value.trim() || "";
                let btn  = document.getElementById("submitBtn");
                btn.style.opacity = "0.5";';

            if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] &&
                $this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'if(user && team) btn.style.opacity="1";';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera']) {

                echo 'if(user) btn.style.opacity="1";';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'if(team) btn.style.opacity="1";';

            } else {
                echo 'btn.style.opacity="1";';
            }

            echo '}
            function submitClicked() {
                let user = document.getElementById("inputField1")?.value.trim() || "";
                let team = document.getElementById("inputField2")?.value.trim() || "";';

            if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] &&
                $this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'if(!user || !team){alert("Vyplň meno aj tím");return;}';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera']) {

                echo 'if(!user){alert("Vyplň meno");return;}';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'if(!team){alert("Vyplň tím");return;}';
            }

            echo 'displayValues();}

            function displayValues() {

                const user  = document.getElementById("inputField1")?.value || "";
                const team  = document.getElementById("inputField2")?.value || "";
                const akcia = "' . esc_js($value['akcia']) . '";
                const singleQuiz = "' . esc_js($single_quiz) . '";';

            $host_url = ($_SERVER['HTTP_HOST'] == 'localhost:8888')
                ? 'http://localhost:8888/eventkviz'
                : 'https://eventkviz.sk/' . $value['akcia'];

            if ($this->cAkcia->music_settings['music_quiz_active']) {
                echo 'const link1 = "' . $host_url . '/aqljk?team="+encodeURIComponent(team)+"&user="+encodeURIComponent(user)+"&akcia="+encodeURIComponent(akcia);';
            }
            if ($this->cAkcia->movies_settings['movies_quiz_active']) {
                echo 'const link2 = "' . $host_url . '/merdfghh/?team="+encodeURIComponent(team)+"&user="+encodeURIComponent(user)+"&akcia="+encodeURIComponent(akcia);';
            }
            if ($this->cAkcia->knowledge_settings['knowledge_quiz_active']) {
                echo 'const link3 = "' . $host_url . '/kwersdfzx/?team="+encodeURIComponent(team)+"&user="+encodeURIComponent(user)+"&akcia="+encodeURIComponent(akcia);';
            }
            if ($this->cAkcia->sudoku_settings['sudoku_quiz_active']) {
                echo 'const link4 = "' . $host_url . '/sweertydfd/?team="+encodeURIComponent(team)+"&user="+encodeURIComponent(user)+"&akcia="+encodeURIComponent(akcia);';
            }

            echo '
                if(singleQuiz){
                    if(singleQuiz==="music" && typeof link1!=="undefined"){window.location.href=link1;return;}
                    if(singleQuiz==="movies" && typeof link2!=="undefined"){window.location.href=link2;return;}
                    if(singleQuiz==="knowledge" && typeof link3!=="undefined"){window.location.href=link3;return;}
                    if(singleQuiz==="sudoku" && typeof link4!=="undefined"){window.location.href=link4;return;}
                }

                const out=document.getElementById("output");
                out.innerHTML="";';

            if ($this->cAkcia->music_settings['music_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="music"){out.innerHTML+=`<a href="${link1}" target="_blank">Hudobný kvíz</a><br>`;}';
            }
            if ($this->cAkcia->movies_settings['movies_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="movies"){out.innerHTML+=`<a href="${link2}" target="_blank">Filmový kvíz</a><br>`;}';
            }
            if ($this->cAkcia->knowledge_settings['knowledge_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="knowledge"){out.innerHTML+=`<a href="${link3}" target="_blank">Vedomostný kvíz</a><br>`;}';
            }
            if ($this->cAkcia->sudoku_settings['sudoku_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="sudoku"){out.innerHTML+=`<a href="${link4}" target="_blank">Sudoku kvíz</a><br>`;}';
            }

            echo '}
            document.addEventListener("DOMContentLoaded",checkFields);
            </script>

            <style>
            #submitBtn{padding:14px 30px;font-size:18px;cursor:pointer;opacity:.5}
            #submitBtn:hover{opacity:1}
            </style>';
        }
    }

    /*
public function show_team_links($atts = '', $single_quiz='') {
    $preselected_user = '';
    
    $value = shortcode_atts( array(
        'type' => '',
        'akcia' => ''
    ), $atts );
    
    $this->load_basic_event_settings($value['akcia']);

    $this->cAkcia->all_quizes_settings($value['akcia']);
    $this->cAkcia->music_quiz_settings($value['akcia']);
    $this->cAkcia->movies_quiz_settings($value['akcia']);
    $this->cAkcia->knowledge_quiz_settings($value['akcia']);
    $this->cAkcia->sudoku_quiz_settings($value['akcia']);
    
    if($this->cAkcia->all_quizes_settings['startup_form'] === true){
        echo "<h2>Prosím, vyplňte tento formulár – odkaz na kvíz(y) sa zobrazí po odoslaní formulára.</h2>";
        
        if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
            echo '<label for="inputField1">Zadaj svoje meno (používateľský kód):</label><BR><BR>';
            if(!empty($_GET['user'])) {
                $preselected_user = $_GET['user'];
            }
            echo '<input type="text" value="' . esc_attr($preselected_user) . '" id="inputField1" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\'); checkFields();">';
        }	

        if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false){
            echo '<label for="inputField2">Zadajte meno svojho tímu:</label>';
            echo '<input type="text" value="' . esc_attr($_GET['team'] ?? '') . '" id="inputField2" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\'); checkFields();">';
        } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true){
            echo '<label for="inputField2">Vyberte svoj tím:</label><br><br>';
            echo '<select id="inputField2" onchange="checkFields();" style="width: 30%; max-width: 30%; display: inline-block;">';
            $keys = array_keys($this->cAkcia->all_quizes_settings['select_teams']);
            $index = 0;
            while($index < count($keys)) {
                $key   = $keys[$index];
                $valu  = $this->cAkcia->all_quizes_settings['select_teams'][$key];
                $selected = ($_GET['team'] ?? '') == $key ? 'selected' : '';
                echo "<option value='" . esc_attr($key) . "' $selected>" . esc_html($valu) . "</option>";
                $index++;
            }	
            echo '</select>';
        }

        echo '<BR><BR><button type="button" id="submitBtn" onclick="submitClicked()">Zobraz odkaz na kvíz(y)</button>';
        echo '<p id="output"></p>';

        echo '<script>';
        echo 'function checkFields() {';
        echo '    let user = document.getElementById("inputField1") ? document.getElementById("inputField1").value.trim() : "";';
        echo '    let team = document.getElementById("inputField2") ? document.getElementById("inputField2").value.trim() : "";';
        echo '    const btn = document.getElementById("submitBtn");';
        echo '    btn.style.opacity = "0.5";'; // default sivé
        if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
            echo '    if(user && team) btn.style.opacity = "1";';
        } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
            echo '    if(user) btn.style.opacity = "1";';
        } elseif($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
            echo '    if(team) btn.style.opacity = "1";';
        } else {
            echo '    btn.style.opacity = "1";';
        }
        echo '}';

        echo 'function submitClicked() {';
        echo '    let user = document.getElementById("inputField1") ? document.getElementById("inputField1").value.trim() : "";';
        echo '    let team = document.getElementById("inputField2") ? document.getElementById("inputField2").value.trim() : "";';

        // ALERTY – fungujú vždy
        if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
            echo '    if(!user || !team) { alert("Prosím, vyplň svoje meno aj názov tímu!"); return; }';
        } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){
            echo '    if(!user) { alert("Prosím, zadaj svoje meno / kód!"); return; }';
        } elseif($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
            echo '    if(!team) { alert("Prosím, zadaj alebo vyber názov tímu!"); return; }';
        }

        echo '    displayValues();';
        echo '}';

        echo 'function displayValues() {';
            if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
                echo 'const user = document.getElementById("inputField1").value;';
                echo 'const team = document.getElementById("inputField2").value;';
            } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true){
                echo 'const user = "";';
                echo 'const team = document.getElementById("inputField2").value;';
            } elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === false){
                echo 'const user = document.getElementById("inputField1").value;';
                echo 'const team = "";';
            } else {
                echo 'const user = ""; const team = "";';
            }
            
            echo 'const akcia = "' . $value['akcia'] . '";';

            if($_SERVER['HTTP_HOST'] == 'localhost:8888') {
                $host_url = 'http://localhost:8888/eventkviz';
            } else {
                $host_url = 'https://eventkviz.sk/' . $value['akcia'];
            }

            if($this->cAkcia->music_settings['music_quiz_active'] === true ) {
                
                    echo 'const link1 = document.createElement("a");';
                    echo 'link1.href = "' . $host_url . '/aqljk?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    echo 'link1.textContent = "Link na HUDOBNÝ KVÍZ";';
                    echo 'link1.target = "_blank"; link1.style.display = "block"; link1.style.margin = "10px 0";';
               
            } 
            
            if($this->cAkcia->movies_settings['movies_quiz_active'] === true ) {

              
                    echo 'const link2 = document.createElement("a");';
                    echo 'link2.href = "' . $host_url . '/merdfghh/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    echo 'link2.textContent = "Link na FILMOVÝ KVÍZ";';
                    echo 'link2.target = "_blank"; link2.style.display = "block"; link2.style.margin = "10px 0";';
              
            }
            
            if($this->cAkcia->knowledge_settings['knowledge_quiz_active'] === true ) {

                
                    echo 'const link3 = document.createElement("a");';
                    echo 'link3.href = "' . $host_url . '/kwersdfzx/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    echo 'link3.textContent = "Link na VEDOMOSTNÝ KVÍZ";';
                    echo 'link3.target = "_blank"; link3.style.display = "block"; link3.style.margin = "10px 0";';
                
            }
        
            if($this->cAkcia->sudoku_settings['sudoku_quiz_active'] === true ) {

                
                    echo 'const link4 = document.createElement("a");';
                    echo 'link4.href = "' . $host_url . '/sweertydfd/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia);';
                    echo 'link4.textContent = "Link na SUDOKU KVÍZ";';
                    echo 'link4.target = "_blank"; link4.style.display = "block"; link4.style.margin = "10px 0";';
               
            }
        
            echo 'const outputDiv = document.getElementById("output");';
            echo 'outputDiv.innerHTML = "";';

            if($this->cAkcia->music_settings['music_quiz_active'] === true)    {
                if (empty($single_quiz) || $single_quiz == 'music') {
                    echo 'outputDiv.appendChild(link1);';
                }
            }

                if($this->cAkcia->movies_settings['movies_quiz_active'] === true)  {
                if (empty($single_quiz) || $single_quiz == 'movies') {
                    echo 'outputDiv.appendChild(link2);';
                }
            }

            if($this->cAkcia->knowledge_settings['knowledge_quiz_active'] === true) {
                if (empty($single_quiz) || $single_quiz == 'knowledge') {
                    echo 'outputDiv.appendChild(link3);';
                }
            }

            if($this->cAkcia->sudoku_settings['sudoku_quiz_active'] === true) {
                if (empty($single_quiz) || $single_quiz == 'sudoku') {
                    echo 'outputDiv.appendChild(link4);';
                }
            }
        echo '}';

        echo 'document.addEventListener("DOMContentLoaded", checkFields);';
        echo '</script>';

        // Voliteľný pekný štýl tlačidla
        echo '<style>#submitBtn{padding:15px 30px;font-size:18px;cursor:pointer;transition:opacity .3s;opacity:0.5;} #submitBtn:hover{opacity:0.9 !important;}</style>';
    } 
}
    */
}