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

        // Fall back to query vars when shortcode atts are not given (hub page usage)
        if (empty($value['akcia']) && get_query_var('akcia')) {
            $value['akcia'] = sanitize_key(get_query_var('akcia'));
        }
        if (empty($value['type']) && get_query_var('type')) {
            $value['type'] = sanitize_key(get_query_var('type'));
        }

        if (empty($value['akcia'])) {
            echo '<p>Akcia nie je špecifikovaná. Použite <code>?akcia=&lt;slug&gt;</code> v URL.</p>';
            return;
        }

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

                $host_base = untrailingslashit( home_url() );
                $type_to_slug = array(
                    'music'     => array( 'aqljk',      $this->cAkcia->music_settings['music_quiz_active'] ?? false ),
                    'movies'    => array( 'merdfghh',   $this->cAkcia->movies_settings['movies_quiz_active'] ?? false ),
                    'knowledge' => array( 'kwersdfzx',  $this->cAkcia->knowledge_settings['knowledge_quiz_active'] ?? false ),
                    'sudoku'    => array( 'sweertydfd', $this->cAkcia->sudoku_settings['sudoku_quiz_active'] ?? false ),
                    // Multi-mapa: žiadny single mapa entry; mapa karty sa generujú per sub-kvíz nižšie.
                );
                if ( isset( $type_to_slug[ $value['type'] ] ) && $type_to_slug[ $value['type'] ][1] === true ) {
                    $quiz_slug = $type_to_slug[ $value['type'] ][0];
                    // Tokenized URL cez REST endpoint (skryje team/user/akcia v URL).
                    // Fallback na plain QS pri REST chybe — vždy funguje.
                    $rest_url = esc_js( esc_url_raw( rest_url( 'eventkviz/v1/link-token' ) ) );
                    $fallback = esc_js( $host_base ) . '/' . esc_js( $quiz_slug ) . '/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia)';
                    echo 'const fbUrl = "' . $fallback . ';';
                    echo 'fetch("' . $rest_url . '?quiz_slug=' . esc_js( $quiz_slug ) . '&akcia=" + encodeURIComponent(akcia) + "&team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user))';
                    echo '.then(r => r.ok ? r.json() : null)';
                    echo '.then(d => { window.location.href = (d && d.url) ? d.url : fbUrl; })';
                    echo '.catch(() => { window.location.href = fbUrl; });';
                } else {
                    echo 'console.warn("Kvíz nie je aktívny pre tento typ.");';
                }
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

        $explicit_atts = is_array($atts) ? $atts : array();
        $explicit_akcia_in_shortcode = isset($explicit_atts['akcia']) && $explicit_atts['akcia'] !== '';

        $value = shortcode_atts(array(
            'type'  => '',
            'akcia' => ''
        ), $atts);

        if (empty($value['akcia']) && get_query_var('akcia')) {
            $value['akcia'] = sanitize_key(get_query_var('akcia'));
        }
        if (empty($value['type']) && get_query_var('type')) {
            $value['type'] = sanitize_key(get_query_var('type'));
        }
        if (!empty($single_quiz)) {
            // explicit caller wins (used internally by quiz form classes)
            $value['type'] = $single_quiz;
        }

        if (empty($value['akcia'])) {
            echo '<p>Akcia nie je špecifikovaná. Použite <code>?akcia=&lt;slug&gt;</code> v URL.</p>';
            return;
        }

        // Hub context = akcia comes from query var, not from explicit shortcode att.
        // In hub context the selector always renders regardless of the legacy
        // `startup_form` flag (which originally controlled per-quiz form behavior).
        $is_hub_context = ! $explicit_akcia_in_shortcode;

        $this->load_basic_event_settings($value['akcia']);

        /*
        $this->cAkcia->all_quizes_settings($value['akcia']);
        $this->cAkcia->music_quiz_settings($value['akcia']);
        $this->cAkcia->movies_quiz_settings($value['akcia']);
        $this->cAkcia->knowledge_quiz_settings($value['akcia']);
        $this->cAkcia->sudoku_quiz_settings($value['akcia']);
*/

        if ($is_hub_context || $this->cAkcia->all_quizes_settings['startup_form'] === true) {

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

                $select_teams = $this->cAkcia->all_quizes_settings['select_teams'];
                $preselected_team = $_GET['team'] ?? '';
                $displayed_label = 'Vyberte svoj tím';
                $is_placeholder = true;
                if ($preselected_team !== '' && isset($select_teams[$preselected_team])) {
                    $displayed_label = $select_teams[$preselected_team];
                    $is_placeholder = false;
                }

                echo '<div class="ek-input-group ek-input-group--select">';
                echo '<div class="ek-dropdown" id="ekDropdown" tabindex="0" role="combobox" aria-haspopup="listbox" aria-expanded="false">';
                echo '<span class="ek-dropdown-value' . ($is_placeholder ? ' is-placeholder' : '') . '" id="ekDropdownValue">' . esc_html($displayed_label) . '</span>';
                echo '<span class="ek-dropdown-chevron" aria-hidden="true">▾</span>';
                echo '</div>';
                echo '<ul class="ek-dropdown-menu" id="ekDropdownMenu" role="listbox">';
                foreach ($select_teams as $k => $v) {
                    $is_sel = ($preselected_team == $k);
                    echo '<li class="ek-dropdown-option' . ($is_sel ? ' is-selected' : '') . '" role="option" data-value="' . esc_attr($k) . '"' . ($is_sel ? ' aria-selected="true"' : '') . '>' . esc_html($v) . '</li>';
                }
                echo '</ul>';
                echo '<input type="hidden" id="inputField2" value="' . esc_attr($preselected_team) . '">';
                echo '</div>';
            }

            echo '<button type="button" id="submitBtn" onclick="submitClicked()" disabled>Pokračovať</button>';
            echo '</form>';
            echo '<div id="output" class="ek-output"></div>';
            echo '</div>'; // .ek-startup-card
            echo '</div>'; // .ek-startup

            // A4: ak je v URL identifikácia (team/user), zisti ktoré kvízy už
            // tento tím / hráč absolvoval (a najlepšie skóre) → JS zobrazí badge
            // pri kartách hraných kvízov.
            $a4_team = isset( $_GET['team'] ) ? sanitize_text_field( wp_unslash( $_GET['team'] ) ) : '';
            $a4_user = isset( $_GET['user'] ) ? sanitize_text_field( wp_unslash( $_GET['user'] ) ) : '';
            $a4_played = array();
            $a4_counts = array();
            if ( $a4_team !== '' || $a4_user !== '' ) {
                global $wpdb;
                if ( $a4_team !== '' ) {
                    $a4_rows = $wpdb->get_results( $wpdb->prepare(
                        "SELECT quiz_type, points, question_set FROM {$wpdb->prefix}jet_cct_results WHERE akcia = %s AND team = %s",
                        $value['akcia'], $a4_team
                    ) );
                } else {
                    $a4_rows = $wpdb->get_results( $wpdb->prepare(
                        "SELECT quiz_type, points, question_set FROM {$wpdb->prefix}jet_cct_results WHERE akcia = %s AND user = %s",
                        $value['akcia'], $a4_user
                    ) );
                }
                foreach ( (array) $a4_rows as $r ) {
                    $key = $r->quiz_type;
                    if ( $r->quiz_type === 'mapa' ) {
                        $qs = json_decode( (string) $r->question_set, true );
                        $mq = ( is_array( $qs ) && ! empty( $qs['mq'] ) ) ? sanitize_key( $qs['mq'] ) : '';
                        $key = 'mapa:' . $mq;
                    }
                    $pts = (int) $r->points;
                    if ( ! isset( $a4_played[ $key ] ) || $pts > $a4_played[ $key ] ) {
                        $a4_played[ $key ] = $pts;
                    }
                    $a4_counts[ $key ] = ( $a4_counts[ $key ] ?? 0 ) + 1;
                }
            }
            echo '<script>window.ekPlayedQuizzes = ' . wp_json_encode( $a4_played ) . ';</script>';

            // A4b: vyčerpané pokusy → karta sa na štartovacej obrazovke zamkne
            // (sivá + 🔒, neklikateľná). Vyčerpané = počet záznamov >= pocet_pokusov + 1,
            // presne tá istá podmienka ako reálny gate v check_number_of_tries() /
            // mapa_check_tries() — karta sa nikdy nezamkne falošne. Počíta sa z tých
            // istých $a4_rows ako badge, takže lock a badge sú vždy konzistentné.
            $a4_exhausted = array();
            foreach ( $a4_counts as $key => $cnt ) {
                $limit = null;
                if ( $key === 'music' ) {
                    $limit = (int) ( $this->cAkcia->music_settings['pocet_pokusov'] ?? 0 );
                } elseif ( $key === 'movies' ) {
                    $limit = (int) ( $this->cAkcia->movies_settings['pocet_pokusov'] ?? 0 );
                } elseif ( $key === 'knowledge' ) {
                    $limit = (int) ( $this->cAkcia->knowledge_settings['pocet_pokusov'] ?? 0 );
                } elseif ( $key === 'sudoku' ) {
                    $limit = (int) ( $this->cAkcia->sudoku_settings['pocet_pokusov'] ?? 0 );
                } elseif ( strpos( $key, 'mapa:' ) === 0 ) {
                    $mq_slug = substr( $key, 5 );
                    if ( ! empty( $this->cAkcia->mapa_quizzes ) && is_array( $this->cAkcia->mapa_quizzes ) ) {
                        foreach ( $this->cAkcia->mapa_quizzes as $sq ) {
                            if ( isset( $sq['slug'] ) && sanitize_key( $sq['slug'] ) === $mq_slug ) {
                                $limit = (int) ( $sq['pocet_pokusov'] ?? 0 );
                                break;
                            }
                        }
                    }
                }
                if ( $limit !== null && $cnt >= $limit + 1 ) {
                    $a4_exhausted[ $key ] = true;
                }
            }
            echo '<script>window.ekExhaustedQuizzes = ' . wp_json_encode( $a4_exhausted ) . ';</script>';

            // A5: sumárny počet bodov + URL na samostatnú štatistiku tímu/hráča.
            $a5_total = array_sum( $a4_played );
            $a5_stats_args = array( 'akcia' => $value['akcia'] );
            if ( $a4_team !== '' ) $a5_stats_args['team'] = $a4_team;
            if ( $a4_user !== '' ) $a5_stats_args['user'] = $a4_user;
            $a5_stats_url = add_query_arg( $a5_stats_args, home_url( '/eventkviz-statistika/' ) );
            echo '<script>'
                . 'window.ekPlayedTotal = ' . (int) $a5_total . ';'
                . 'window.ekStatsLink = ' . wp_json_encode( $a5_stats_url ) . ';'
                . '</script>';

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

            echo '
                // Status (badge skóre + zámok vyčerpaných kvízov) sa počíta na serveri
                // z ?team=/?user= v URL. Pri výbere z dropdownu URL tieto params nemá,
                // tak reloadneme s nimi → server vykreslí karty so správnym statusom.
                // (Pre single-quiz link netreba — displayValues rovno presmeruje na kvíz.)
                var __singleQuiz = "' . esc_js($value['type']) . '";
                if (!__singleQuiz) {
                    var __params = new URLSearchParams(window.location.search);
                    var __needReload = (team && __params.get("team") !== team) || (user && __params.get("user") !== user);
                    if (__needReload) {
                        __params.set("akcia", "' . esc_js($value['akcia']) . '");
                        if (team) { __params.set("team", team); } else { __params.delete("team"); }
                        if (user) { __params.set("user", user); } else { __params.delete("user"); }
                        window.location.search = __params.toString();
                        return;
                    }
                }
                displayValues();
            }

            // Tokenized URL helper — fetch z REST endpoint /eventkviz/v1/link-token.
            // Pri network/REST chybe fallback na plain URL aby kvíz vždy fungoval.
            async function ekTokUrl(quizSlug, akcia, team, user, mq) {
                const fb = "' . esc_js( untrailingslashit( home_url() ) ) . '/" + quizSlug + "/?team=" + encodeURIComponent(team) + "&user=" + encodeURIComponent(user) + "&akcia=" + encodeURIComponent(akcia) + (mq ? "&mq=" + encodeURIComponent(mq) : "");
                try {
                    const u = new URL("' . esc_js( esc_url_raw( rest_url( 'eventkviz/v1/link-token' ) ) ) . '");
                    u.searchParams.set("quiz_slug", quizSlug);
                    u.searchParams.set("akcia", akcia);
                    u.searchParams.set("team", team);
                    u.searchParams.set("user", user);
                    if (mq) u.searchParams.set("mq", mq);
                    const r = await fetch(u.toString());
                    if (!r.ok) return fb;
                    const d = await r.json();
                    return (d && d.url) ? d.url : fb;
                } catch (e) { return fb; }
            }

            async function displayValues() {

                const user  = document.getElementById("inputField1")?.value || "";
                const team  = document.getElementById("inputField2")?.value || "";
                const akcia = "' . esc_js($value['akcia']) . '";
                const singleQuiz = "' . esc_js($value['type']) . '";';

            $host_url = untrailingslashit( home_url() );

            if ($this->cAkcia->music_settings['music_quiz_active']) {
                echo 'const link1 = await ekTokUrl("aqljk", akcia, team, user);';
            }
            if ($this->cAkcia->movies_settings['movies_quiz_active']) {
                echo 'const link2 = await ekTokUrl("merdfghh", akcia, team, user);';
            }
            if ($this->cAkcia->knowledge_settings['knowledge_quiz_active']) {
                echo 'const link3 = await ekTokUrl("kwersdfzx", akcia, team, user);';
            }
            if ($this->cAkcia->sudoku_settings['sudoku_quiz_active']) {
                echo 'const link4 = await ekTokUrl("sweertydfd", akcia, team, user);';
            }
            // Multi-mapa: žiadny single link5; pre každý sub-kvíz samostatná karta nižšie.

            echo '
                if(singleQuiz){
                    if(singleQuiz==="music" && typeof link1!=="undefined"){window.location.href=link1;return;}
                    if(singleQuiz==="movies" && typeof link2!=="undefined"){window.location.href=link2;return;}
                    if(singleQuiz==="knowledge" && typeof link3!=="undefined"){window.location.href=link3;return;}
                    if(singleQuiz==="sudoku" && typeof link4!=="undefined"){window.location.href=link4;return;}
                }

                const out=document.getElementById("output");
                out.innerHTML="";
                // A4: helper — vráti HTML badge "✓ X b" pre už absolvovaný kvíz (key).
                const _ekPlayed = window.ekPlayedQuizzes || {};
                const ekBadge = (key) => (key in _ekPlayed) ? `<span class="ek-quiz-played">✓ ${_ekPlayed[key]} b</span>` : "";
                // A4b: vyčerpané kvízy → karta zamknutá (sivá + 🔒, neklikateľná). Badge ostáva.
                const _ekLocked = window.ekExhaustedQuizzes || {};
                const ekCard = (href, cls, icon, label, key) => {
                    const badge = ekBadge(key);
                    if (key in _ekLocked) {
                        return `<div class="ek-quiz-card ${cls} is-locked" role="link" aria-disabled="true"><span class="ek-quiz-icon">${icon}</span><span class="ek-quiz-label">${label}</span>${badge}<span class="ek-quiz-lock" aria-label="Vyčerpané" title="Vyčerpané pokusy">🔒</span></div>`;
                    }
                    return `<a href="${href}" class="ek-quiz-card ${cls}" target="_blank"><span class="ek-quiz-icon">${icon}</span><span class="ek-quiz-label">${label}</span>${badge}<span class="ek-quiz-arrow">→</span></a>`;
                };';

            if ($this->cAkcia->music_settings['music_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="music"){out.innerHTML+=ekCard(link1,"ek-quiz-music","🎵","Hudobný kvíz","music");}';
            }
            if ($this->cAkcia->movies_settings['movies_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="movies"){out.innerHTML+=ekCard(link2,"ek-quiz-movies","🎬","Filmový kvíz","movies");}';
            }
            if ($this->cAkcia->knowledge_settings['knowledge_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="knowledge"){out.innerHTML+=ekCard(link3,"ek-quiz-knowledge","🧠","Vedomostný kvíz","knowledge");}';
            }
            if ($this->cAkcia->sudoku_settings['sudoku_quiz_active']) {
                echo 'if(!singleQuiz||singleQuiz==="sudoku"){out.innerHTML+=ekCard(link4,"ek-quiz-sudoku","🔢","Sudoku kvíz","sudoku");}';
            }
            // Multi-mapa: pre každý sub-kvíz jedna karta s vlastným mq slug + admin label.
            // Render je async (fetch tokenized URL pre každú karu paralelne).
            if ( ! empty( $this->cAkcia->mapa_quizzes ) && is_array( $this->cAkcia->mapa_quizzes ) ) {
                foreach ( $this->cAkcia->mapa_quizzes as $sq ) {
                    $slug  = isset( $sq['slug'] ) ? sanitize_key( $sq['slug'] ) : '';
                    $label = isset( $sq['label'] ) ? (string) $sq['label'] : 'Mapový kvíz';
                    if ( $slug === '' ) continue;
                    echo 'if(!singleQuiz){const mqLink = await ekTokUrl("mapa-quiz", akcia, team, user, "' . esc_js( $slug ) . '"); out.innerHTML+=ekCard(mqLink,"ek-quiz-mapa","🗺️","' . esc_js( $label ) . '","mapa:' . esc_js( $slug ) . '");}';
                }
            }

            // A5: pod kartami pridať sumár bodov + button na samostatnú štatistiku.
            // Zobrazí sa len ak tím/hráč už niečo absolvoval (ekPlayedTotal > 0).
            echo 'if (!singleQuiz && window.ekPlayedTotal > 0 && window.ekStatsLink) {'
                . '  const sub = (team || user || "");'
                . '  out.innerHTML += `<div class="ek-stats-summary">'
                . '      <div class="ek-stats-summary-row">'
                . '        <span class="ek-stats-summary-label">Tvoje skóre zatiaľ</span>'
                . '        <span class="ek-stats-summary-pts">${window.ekPlayedTotal} b</span>'
                . '      </div>'
                . '      <a href="${window.ekStatsLink}" class="ek-stats-summary-btn" target="_blank">📊 Pozri celú štatistiku${sub ? ` (${sub})` : ""} →</a>'
                . '  </div>`;'
                . '}';

            echo '}
            document.addEventListener("DOMContentLoaded", function(){
                checkFields();
                // Auto-skip startup form pri návrate z kvízu: ak sú údaje
                // predvyplnené z URL (?team=X&user=Y), button je rovno enabled →
                // priamo zobraz linky bez nutnosti klikať "Pokračovať".
                var btn = document.getElementById("submitBtn");
                if (btn && !btn.disabled) submitClicked();
            });

            (function(){
                var dropdown = document.getElementById("ekDropdown");
                if (!dropdown) return;
                var menu = document.getElementById("ekDropdownMenu");
                var valueDisplay = document.getElementById("ekDropdownValue");
                var hidden = document.getElementById("inputField2");
                var options = menu.querySelectorAll(".ek-dropdown-option");
                var activeIndex = -1;

                function open(){
                    dropdown.classList.add("is-open");
                    menu.classList.add("is-open");
                    dropdown.setAttribute("aria-expanded","true");
                }
                function close(){
                    dropdown.classList.remove("is-open");
                    menu.classList.remove("is-open");
                    dropdown.setAttribute("aria-expanded","false");
                    activeIndex = -1;
                    options.forEach(function(o){ o.classList.remove("is-active"); });
                }
                function toggle(){
                    menu.classList.contains("is-open") ? close() : open();
                }
                function pick(opt){
                    hidden.value = opt.getAttribute("data-value");
                    valueDisplay.textContent = opt.textContent;
                    valueDisplay.classList.remove("is-placeholder");
                    options.forEach(function(o){ o.classList.remove("is-selected"); o.removeAttribute("aria-selected"); });
                    opt.classList.add("is-selected");
                    opt.setAttribute("aria-selected","true");
                    close();
                    if (typeof checkFields === "function") checkFields();
                }
                function setActive(i){
                    activeIndex = Math.max(0, Math.min(options.length - 1, i));
                    options.forEach(function(o, idx){ o.classList.toggle("is-active", idx === activeIndex); });
                    options[activeIndex].scrollIntoView({block:"nearest"});
                }

                dropdown.addEventListener("click", toggle);
                options.forEach(function(opt){
                    opt.addEventListener("click", function(e){ e.stopPropagation(); pick(opt); });
                });
                document.addEventListener("click", function(e){
                    if (!dropdown.contains(e.target) && !menu.contains(e.target)) close();
                });
                dropdown.addEventListener("keydown", function(e){
                    if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        if (!menu.classList.contains("is-open")) open();
                        else if (activeIndex >= 0) pick(options[activeIndex]);
                    } else if (e.key === "Escape") {
                        close();
                    } else if (e.key === "ArrowDown") {
                        e.preventDefault();
                        if (!menu.classList.contains("is-open")) { open(); setActive(0); }
                        else setActive(activeIndex + 1);
                    } else if (e.key === "ArrowUp") {
                        e.preventDefault();
                        if (menu.classList.contains("is-open")) setActive(activeIndex - 1);
                    }
                });
            })();
            </script>';
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