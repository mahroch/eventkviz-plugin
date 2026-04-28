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
                    echo '<input type="text" value= "' . esc_attr($_GET['user'] ?? '') . '" id="inputField1" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
            }	

            if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false){
                    echo '<label for="inputField2">Zadajte meno vášho tímu:</label>';
                    echo '<input type="text" value= "' . esc_attr($_GET['team'] ?? '') . '" id="inputField2" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, \'\')">';
            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true){

                echo '<label for="inputField2">Vyberte svoj tím:</label>';
                echo '<select id="inputField2">';

                        $keys = array_keys($this->cAkcia->all_quizes_settings['select_teams']); // Get an array of keys
                        $index = 0;
                        while($index < count($keys)) {
                            $key = $keys[$index];
                            $valu = $this->cAkcia->all_quizes_settings['select_teams'][$key];
                            if(($_GET['team'] ?? '') == $key) {
                                $selected = 'selected';
                            }
                            echo "<option value='" . esc_attr($key) . "' " . $selected . ">" . esc_html($valu) . "</option>";
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

            echo '<div class="ek-startup">';
            echo '<div class="ek-startup-card">';
            echo '<h1 class="ek-startup-title">Pripravte sa na kvíz</h1>';
            echo '<p class="ek-startup-subtitle">Vyplňte údaje a otvoríme vám kvíz</p>';
            echo '<form class="ek-startup-form" onsubmit="return false;">';

            if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true) {
                if (!empty($_GET['user'])) {
                    $preselected_user = $_GET['user'];
                }
                echo '<div class="ek-input-group">';
                echo '<input type="text" id="inputField1" placeholder="Vaše meno alebo kód" value="' . esc_attr($preselected_user) . '" oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,\'\');checkFields();">';
                echo '</div>';
            }

            if ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true &&
                $this->cAkcia->all_quizes_settings['select_from_teams_array'] === false) {

                echo '<div class="ek-input-group">';
                echo '<input type="text" id="inputField2" placeholder="Názov tímu" value="' . esc_attr($_GET['team'] ?? '') . '" oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,\'\');checkFields();">';
                echo '</div>';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true &&
                    $this->cAkcia->all_quizes_settings['select_from_teams_array'] === true) {

                echo '<div class="ek-input-group ek-input-group--select">';
                echo '<select id="inputField2" onchange="checkFields();">';
                echo '<option value="" disabled selected>Vyberte svoj tím</option>';

                foreach ($this->cAkcia->all_quizes_settings['select_teams'] as $k => $v) {
                    $sel = (($_GET['team'] ?? '') == $k) ? 'selected' : '';
                    echo '<option value="' . esc_attr($k) . '" ' . $sel . '>' . esc_html($v) . '</option>';
                }

                echo '</select>';
                echo '</div>';
            }

            echo '<button type="button" id="submitBtn" onclick="submitClicked()" disabled>Pokračovať</button>';
            echo '</form>';
            echo '<div id="output" class="ek-output"></div>';
            echo '</div>'; // .ek-startup-card
            echo '</div>'; // .ek-startup

            echo '<script>
            function checkFields() {
                let user = document.getElementById("inputField1")?.value.trim() || "";
                let team = document.getElementById("inputField2")?.value.trim() || "";
                let btn  = document.getElementById("submitBtn");
                let valid = false;';

            if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] &&
                $this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'valid = !!(user && team);';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera']) {

                echo 'valid = !!user;';

            } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu']) {

                echo 'valid = !!team;';

            } else {
                echo 'valid = true;';
            }

            echo 'btn.disabled = !valid;
            }
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
                echo 'if(!singleQuiz||singleQuiz==="music"){out.innerHTML+=`<a href="${link1}" class="ek-quiz-card ek-quiz-music" target="_blank"><span class="ek-quiz-icon">🎵</span><span class="ek-quiz-label">Hudobný kvíz</span><span class="ek-quiz-arrow">→</span></a>`;}';
            }
            if ($this->cAkcia->movies_settings['movies_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="movies"){out.innerHTML+=`<a href="${link2}" class="ek-quiz-card ek-quiz-movies" target="_blank"><span class="ek-quiz-icon">🎬</span><span class="ek-quiz-label">Filmový kvíz</span><span class="ek-quiz-arrow">→</span></a>`;}';
            }
            if ($this->cAkcia->knowledge_settings['knowledge_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="knowledge"){out.innerHTML+=`<a href="${link3}" class="ek-quiz-card ek-quiz-knowledge" target="_blank"><span class="ek-quiz-icon">🧠</span><span class="ek-quiz-label">Vedomostný kvíz</span><span class="ek-quiz-arrow">→</span></a>`;}';
            }
            if ($this->cAkcia->sudoku_settings['sudoku_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="sudoku"){out.innerHTML+=`<a href="${link4}" class="ek-quiz-card ek-quiz-sudoku" target="_blank"><span class="ek-quiz-icon">🔢</span><span class="ek-quiz-label">Sudoku kvíz</span><span class="ek-quiz-arrow">→</span></a>`;}';
            }

            echo '}
            document.addEventListener("DOMContentLoaded",checkFields);
            </script>

            <style>
            .ek-startup{
                min-height: 70vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 24px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
                position: relative;
                overflow: hidden;
            }
            .ek-startup::before{
                content: "";
                position: absolute;
                inset: 0;
                background:
                    radial-gradient(circle at 20% 20%, rgba(255,255,255,0.15) 0%, transparent 40%),
                    radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 40%);
                pointer-events: none;
            }
            .ek-startup-card{
                position: relative;
                width: 100%;
                max-width: 480px;
                padding: 48px 40px;
                background: rgba(255,255,255,0.15);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255,255,255,0.25);
                border-radius: 24px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                color: #fff;
                text-align: center;
                box-sizing: border-box;
            }
            .ek-startup-title{
                font-size: 30px;
                font-weight: 700;
                margin: 0 0 8px;
                color: #fff;
                letter-spacing: -0.5px;
                line-height: 1.2;
            }
            .ek-startup-subtitle{
                font-size: 15px;
                margin: 0 0 28px;
                opacity: 0.85;
                line-height: 1.5;
                color: #fff;
            }
            .ek-startup-form{
                display: flex;
                flex-direction: column;
                gap: 14px;
                margin: 0;
            }
            .ek-input-group input,
            .ek-input-group select{
                width: 100%;
                box-sizing: border-box;
                padding: 14px 16px;
                font-size: 16px;
                font-family: inherit;
                background: rgba(255,255,255,0.2);
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 12px;
                color: #fff;
                transition: all 0.2s ease;
            }
            .ek-input-group input::placeholder{
                color: rgba(255,255,255,0.7);
            }
            .ek-input-group select option{
                color: #333;
            }
            .ek-input-group input:focus,
            .ek-input-group select:focus{
                outline: none;
                background: rgba(255,255,255,0.3);
                border-color: rgba(255,255,255,0.6);
                box-shadow: 0 0 0 3px rgba(255,255,255,0.15);
            }
            #submitBtn{
                width: 100%;
                padding: 16px 24px;
                margin-top: 6px;
                font-size: 16px;
                font-weight: 600;
                font-family: inherit;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: #fff;
                border: none;
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.25s ease;
                box-shadow: 0 4px 16px rgba(245,87,108,0.4);
            }
            #submitBtn:hover:not(:disabled){
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(245,87,108,0.5);
            }
            #submitBtn:disabled{
                opacity: 0.5;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }
            .ek-output{
                margin-top: 24px;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .ek-output:empty{
                margin-top: 0;
            }
            .ek-quiz-card{
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 16px 20px;
                background: rgba(255,255,255,0.18);
                border: 1px solid rgba(255,255,255,0.25);
                border-radius: 14px;
                text-decoration: none !important;
                color: #fff !important;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.25s ease;
            }
            .ek-quiz-card:hover{
                transform: translateX(4px);
                background: rgba(255,255,255,0.28);
                border-color: rgba(255,255,255,0.4);
                color: #fff !important;
                text-decoration: none !important;
            }
            .ek-quiz-icon{
                font-size: 26px;
                line-height: 1;
            }
            .ek-quiz-label{
                flex: 1;
                text-align: left;
            }
            .ek-quiz-arrow{
                font-size: 20px;
                opacity: 0.7;
                transition: all 0.2s ease;
            }
            .ek-quiz-card:hover .ek-quiz-arrow{
                opacity: 1;
                transform: translateX(4px);
            }
            @media (max-width: 480px){
                .ek-startup{ padding: 24px 12px; min-height: 60vh; }
                .ek-startup-card{ padding: 36px 24px; }
                .ek-startup-title{ font-size: 24px; }
            }
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