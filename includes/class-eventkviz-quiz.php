<?php
 require_once WP_PLUGIN_DIR . '/eventkviz/public/class-eventkviz-public.php';

class  Eventkviz_Quiz_Class extends Eventkviz_Public{
    public $question_set;

    public function __construct() {
        $this->question_set = '';
		
    }

	public function load_basic_event_settings( $akcia_tag ) {
    if ( empty( $akcia_tag ) ) {
        error_log( 'EventKviz: Chýbajúci akcia_tag – fallback na default' );
        $akcia_tag = 'default';
    }

    // Nájdi event podľa slugu
    $event_query = new WP_Query( array(
        'post_type'      => 'eventkviz_event',
        'post_status'    => 'publish',
        'name'           => $akcia_tag,
        'posts_per_page' => 1,
    ) );

    if ( ! $event_query->have_posts() ) {
        error_log( 'EventKviz: Event "' . $akcia_tag . '" nenájdený – používam default settings' );
        $this->set_default_event_settings();
    } else {
        $event_id = $event_query->posts[0]->ID;
        $all_meta = get_post_meta( $event_id );

        // === ALL QUIZES SETTINGS (general) ===
        $this->all_quizes_settings = array();

        $bool_keys = [
            'startup_form',
            'identifikacia_kodom_usera',
            'verify_users_in_db',
            'identifikacia_userov_timu',
            'select_from_teams_array',
            'use_seed',
            'show_link_back_to_all_quizes'
        ];

        foreach ( $bool_keys as $key ) {
            $meta_key = 'event_general_' . $key;
            $this->all_quizes_settings[$key] = isset( $all_meta[$meta_key][0] ) && $all_meta[$meta_key][0] === '1';
        }

        // select_teams – dynamicky z DB, fallback default
        $default_select_teams = array(
            ''      => 'Select ...',
            'team1' => 'Team 1',
            'team2' => 'Team 2',
            'team3' => 'Team 3',
            'team4' => 'Team 4',
            'team5' => 'Team 5',
            'team6' => 'Team 6',
            'team7' => 'Team 7',
            'team8' => 'Team 8',
            'team9' => 'Team 9',
            'team10'=> 'Team 10'
        );

        $saved_select_teams = isset( $all_meta['event_general_select_teams'][0] ) ? maybe_unserialize( $all_meta['event_general_select_teams'][0] ) : array();
        $this->all_quizes_settings['select_teams'] = is_array( $saved_select_teams ) && ! empty( $saved_select_teams ) ? $saved_select_teams : $default_select_teams;

        // places – dynamicky z DB, fallback default
        $default_places = array(
            0 => array( 'sudoku', 'Sudoku quiz' ),
            1 => array( 'movies', 'Movies quiz' ),
            2 => array( 'music', 'Music quiz' ),
            3 => array( 'knowledge', 'Knowledge quiz' )
        );

        $saved_places = isset( $all_meta['event_general_places'][0] ) ? maybe_unserialize( $all_meta['event_general_places'][0] ) : array();
        $this->all_quizes_settings['places'] = is_array( $saved_places ) && ! empty( $saved_places ) ? $saved_places : $default_places;

        // names_of_places – dynamicky z DB, fallback default
        $default_names_of_places = array(
            'sudoku'    => 'Sudoku quiz',
            'movies'    => 'Movies quiz',
            'music'     => 'Music quiz',
            'knowledge' => 'Knowledge quiz'
        );

        $saved_names_of_places = isset( $all_meta['event_general_names_of_places'][0] ) ? maybe_unserialize( $all_meta['event_general_names_of_places'][0] ) : array();
        $this->all_quizes_settings['names_of_places'] = is_array( $saved_names_of_places ) && ! empty( $saved_names_of_places ) ? $saved_names_of_places : $default_names_of_places;

		$this->all_quizes_settings['minimal_number_of_correct_seeds'] = isset( $all_meta['event_general_minimal_number_of_correct_seeds'][0] ) ? (int) $all_meta['event_general_minimal_number_of_correct_seeds'][0] : 3;

		$this->all_quizes_settings['final_place_pocet_pokusov'] = isset( $all_meta['event_general_final_place_pocet_pokusov'][0] ) ? (int) $all_meta['event_general_final_place_pocet_pokusov'][0] : 3;

        // === KVÍZ SPECIFICKÉ SETTINGS ===
        $quiz_types = array(
            'music'     => 'music_settings',
            'movies'    => 'movies_settings',
            'knowledge' => 'knowledge_settings',
            'sudoku'    => 'sudoku_settings'
        );

        foreach ( $quiz_types as $type => $property ) {
            $this->{$property} = array();

            foreach ( $all_meta as $meta_key => $value_array ) {
                if ( strpos( $meta_key, 'event_' . $type . '_' ) === 0 ) {
                    $key = str_replace( 'event_' . $type . '_', '', $meta_key );
                    $value = maybe_unserialize( $value_array[0] );

                    // Bool konverzia
                    if ( in_array( $key, ['sudoku_quiz_active','knowledge_quiz_active','movies_quiz_active', 'music_quiz_active','show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede', 'moze_si_vybrat_difficulty'] ) ) {
                        $value = $value === '1' || $value === 'yes' || $value === true;
                    } elseif ( in_array( $key, ['pocet_otazok_v_sete', 'pocet_pokusov', 'min_body_na_postup', 'obrazok_pri_splneni_kvizu'] ) ) {
                        $value = (int) $value;
                    }

                    $this->{$property}[$key] = $value;
                }
            }

            // === CREDITS – dynamicky z DB, ale v presnej pôvodnej štruktúre array ===
            if ( $type === 'music' ) {
                $this->{$property}['credits'] = array(
                    'corr_art_corr_pos_corr_song_corr_pos' => isset( $this->{$property}['credits_corr_art_corr_pos_corr_song_corr_pos'] ) ? (int) $this->{$property}['credits_corr_art_corr_pos_corr_song_corr_pos'] : 0,
                    'corr_art_corr_pos_incorr_song'       => isset( $this->{$property}['credits_corr_art_corr_pos_incorr_song'] ) ? (int) $this->{$property}['credits_corr_art_corr_pos_incorr_song'] : 0,
                    'incorr_art_corr_song_corr_pos'       => isset( $this->{$property}['credits_incorr_art_corr_song_corr_pos'] ) ? (int) $this->{$property}['credits_incorr_art_corr_song_corr_pos'] : 0,
                    'corr_art_in_array'                   => isset( $this->{$property}['credits_corr_art_in_array'] ) ? (int) $this->{$property}['credits_corr_art_in_array'] : 0,
                    'corr_song_in_array'                  => isset( $this->{$property}['credits_corr_song_in_array'] ) ? (int) $this->{$property}['credits_corr_song_in_array'] : 0,
                );
            }

            if ( $type === 'movies' ) {
                $this->{$property}['credits'] = array(
                    'corr_movie' => isset( $this->{$property}['credits_corr_movie'] ) ? (int) $this->{$property}['credits_corr_movie'] : 0,
                );

				$this->{$property}['number_question_in_production'] = array(
					'skcz'       => isset( $this->{$property}['number_question_in_production_skcz'] ) ? (int) $this->{$property}['number_question_in_production_skcz'] : 2,
					'zahranicne' => isset( $this->{$property}['number_question_in_production_zahranicne'] ) ? (int) $this->{$property}['number_question_in_production_zahranicne'] : 8,
				);
            }

            if ( $type === 'knowledge' ) {
                $this->{$property}['credits'] = array(
                    'corr_answer' => isset( $this->{$property}['credits_corr_answer'] ) ? (int) $this->{$property}['credits_corr_answer'] : 0,
                );
            }

            if ( $type === 'sudoku' ) {
                $this->{$property}['credits'] = array(
                    'easy'   => isset( $this->{$property}['credits_easy'] ) ? (int) $this->{$property}['credits_easy'] : 10,
                    'medium' => isset( $this->{$property}['credits_medium'] ) ? (int) $this->{$property}['credits_medium'] : 20,
                    'hard'   => isset( $this->{$property}['credits_hard'] ) ? (int) $this->{$property}['credits_hard'] : 35,
                );
            }

            // === KNOWLEDGE number_question_in_topic – dynamicky z DB do array (topic slug ako kľúč)
            if ( $type === 'knowledge' ) {
                $this->{$property}['number_question_in_topic'] = array();

                foreach ( $this->{$property} as $key => $value ) {
                    if ( strpos( $key, 'number_question_in_topic_' ) === 0 ) {
                        $topic_key = str_replace( 'number_question_in_topic_', '', $key );
                        $this->{$property}['number_question_in_topic'][$topic_key] = (int) $value;

                        unset( $this->{$property}[$key] );
                    }
                }
            }

			// Credits za stanovištia
			$this->all_quizes_settings['credits'] = isset( $all_meta['event_general_credits'][0] ) ? maybe_unserialize( $all_meta['event_general_credits'][0] ) : array(
				'horse'          => 10,
				'racing'         => 20,
				'stadium'        => 40,
				'bridge'         => 50,
				'hotel'          => 30,
				'danube'         => 60,
				'final'          => 20,
				'chest_success'  => 100,
				'unspecified'    => 30
			);

            // Formát pri splnení
            $this->{$property}['format_pri_splneni'] = isset( $this->{$property}['format_pri_splneni'] ) ? $this->{$property}['format_pri_splneni'] : 'obrazok';
        }
    }

    // === FINÁLNA ŠTRUKTÚRA ===
    $this->akcia_tag = $akcia_tag;
    $class_name = 'Eventkviz_' . $akcia_tag . '_Class';

    $this->cAkcia = class_exists( $class_name ) ? new $class_name() : new stdClass();

    $this->cAkcia->all_quizes_settings = $this->all_quizes_settings;

    $this->cAkcia->music_settings     = $this->music_settings ?? array();
    $this->cAkcia->movies_settings    = $this->movies_settings ?? array();
    $this->cAkcia->knowledge_settings = $this->knowledge_settings ?? array();
    $this->cAkcia->sudoku_settings    = $this->sudoku_settings ?? array();
}

	public function show_total_credits_gained($gained_credits='', $user='', $team=''){
		if(!empty($user) && $team=="none"){
			echo "<h1>Sumár získaných bodov:<br>" . $gained_credits . " bodov <br>(spočítané pre hráča <b>" . $user . "</b>)</h1>";
		} elseif(empty($user) && !empty($team)){
			echo "<h1>Sumár získaných bodov:<br><span class='eventkviz_gained_points'>" . $gained_credits . " bodov</span> <br>(spočítané pre team <b>" . $team ."</b>)</h1>";
		} else {
			echo "<h1>Sumár získaných bodov:<br>" . $gained_credits . " bodov </h1>";
		}

		$this->show_link_back($user, $team);
	}

	public function show_link_back($user='', $team=''){

		$akcia_tag = $this->akcia_tag;

		if($this->cAkcia->all_quizes_settings['show_link_back_to_all_quizes'] != false) {
			$links_url = 'https://eventkviz.sk/' . $akcia_tag . '/all-team-links-' . $akcia_tag . '/?team=' . $team . '&user=' . $user;
			echo '<br><br><a href="' . $links_url . '">Späť na linky s kvízmi</a><br><br>';

		}


	}
		
	public function set_team_code($user_code, $akcia_code){
		//global $all_quizes_settings;
		//$this->all_quizes_settings($akcia_code);
		
		if($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === false) {

			if($this->cAkcia->all_quizes_settings['verify_users_in_db'] === true) {

				// Query the 'participants' CPT to find the participant with the matching user ID
				$participants_query = new WP_Query( array(
					'post_type' => 'participants',
					'meta_query' => array(
						array(
							'key' => 'user-code',
							'value' => $this->standardize($user_code),
							'compare' => '=',
							'akcia'=> $akcia_code
						),
					),
				) );

				// If a participant is found, get their team number
				if ( $participants_query->have_posts() ) {
					$participant_id = $participants_query->post->ID;
					
					//$related_team_id = $this->get_related_ids( $participant_id, 22 );
					$terms = wp_get_post_terms( $participant_id, 'team' );

					if ( $terms[0]->term_id ) {
						//$team_code = get_the_title($related_team_id);
						$team_code = $terms[0]->name;
					} else {
						echo 'The user with ID ' . $user_code . ' is not on any team';
						die;
					}
				} else {
					echo 'The user with ID ' . $user_code . ' is not a participant';
					die;
				}
			} else {
				$team_code = 'none';
			}
			
			
			
		} else {
			$team_code = get_query_var( 'team' );
		}
		//echo 'set_team_code-> team code:' . $team_code;
		return $team_code;
	}

	public function check_number_of_tries($user_code, $akcia_code,$place, $team_code, $alternative_text = ''){
		global $wpdb;
		//global $all_quizes_settings;
		global $pocet_pokusov_reached;
		
		//$this->all_quizes_settings($akcia_code);

			if($place == 'music'){
				//global $music_settings;
				//$this->music_quiz_settings($akcia_code);
				$pocet_pokusov = $this->cAkcia->music_settings['pocet_pokusov'];
			} elseif($place == 'movies'){
				//global $movies_settings;
				//$this->movies_quiz_settings($akcia_code);
				$pocet_pokusov = $this->cAkcia->movies_settings['pocet_pokusov'];
			}elseif($place == 'knowledge'){
				//global $knowledge_settings;
				//$this->knowledge_quiz_settings($akcia_code);
				$pocet_pokusov = $this->cAkcia->knowledge_settings['pocet_pokusov'];
			}elseif($place == 'sudoku'){
				//global $sudoku_settings;
				//$this->sudoku_quiz_settings($akcia_code);
				$pocet_pokusov = $this->cAkcia->sudoku_settings['pocet_pokusov'];
			}elseif($place == 'final'){
				$pocet_pokusov = $this->cAkcia->all_quizes_settings['final_place_pocet_pokusov'];
			}else {
				
			}
			
			if(empty($user_code) && empty($team_code)) {
				return true;
			} else {

				if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && !empty($user_code)){
					$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM pmgonijet_cct_results WHERE user = %s AND akcia = %s AND quiz_type = %s", $this->standardize($user_code), $akcia_code, $place));
					//echo 1;
				} elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && !empty($team_code)){
					$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM pmgonijet_cct_results WHERE team = %s AND akcia = %s AND quiz_type = %s", $this->standardize($team_code), $akcia_code, $place));
					//echo 2;
				} else {
					$results = array();
					//echo 3;
				}

				//print_r($results);

				$entries = count($results);

				if($entries >= $pocet_pokusov+1){
					if(!empty($alternative_text)) {
						echo $alternative_text;
					} else {
						//echo 'Limit of tries for this quiz was reached (Allowed:' . $pocet_pokusov . ', Realized: ' . $entries . '). ';
						echo 'Limit of tries for this quiz was reached (Allowed:' . $pocet_pokusov . '). ';
					}
					
					
					$pocet_pokusov_reached = true;
					//echo 'Pocet prvy:' . $pocet_pokusov_reached;
					return false;

				} else {
					//echo 'Mozme pokracovat, lebo pocet pokusov je len ' . $entries;
					$this->zostava_pocet_pokusov = $pocet_pokusov+1-$entries;
					return true;
				}
			}
	}

	public function show_seed($user_code='', $akcia='',$place='',$team_code='') {
		//global $all_quizes_settings;
		if( $this->cAkcia->all_quizes_settings['use_seed'] === true) {
			$seed = $this->get_seed_for_place($user_code, $place, $akcia, $team_code);
			echo '<div class="seed_block"><h1>Your code for this place (' . $this->cAkcia->all_quizes_settings['names_of_places'][$place] . ') is: <br><br>';
			echo '<span class="seed_for_place">' . $seed .'</span>' ;
			echo '</h1></div>';
		}
	}
	public function standardize($string){
		return strtolower($string);
	}

	public function get_all_seeds($user_code, $akcia, $team_code=''){
		global $wpdb;
		if($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true && !empty($user_code)){
			$results = $wpdb->get_results($wpdb->prepare("SELECT seeds FROM pmgonijet_cct_seeds WHERE user = %s AND akcia = %s", $this->standardize($user_code), $akcia));
		} elseif($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false && $this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] === true && !empty($team_code)){
			$results = $wpdb->get_results($wpdb->prepare("SELECT seeds FROM pmgonijet_cct_seeds WHERE team = %s AND akcia = %s", $this->standardize($team_code), $akcia));
		} else {
			$results = array();
		}
		
		if(!empty($results[0]->seeds)) {
			$seeds = unserialize($results[0]->seeds);
		} else {
			$seeds = array();
		}
		
		return $seeds;
	}

	public function get_seed_for_place($user_code, $place, $akcia, $team_code=''){
		
		//global $all_quizes_settings;

		$seeds = $this->get_all_seeds($user_code, $akcia, $team_code);

		
		//echo 'seed: ' . $seeds;
		return $seeds[$place];
	}


	public function set_seed_for_user_or_team($user_code='',$akcia='', $team_code=''){
		//global $all_quizes_settings;	
		//$this->all_quizes_settings($akcia);
		
		if($this->cAkcia->all_quizes_settings['use_seed'] === true){
			$number_of_seeds = count($this->cAkcia->all_quizes_settings['places']);
			global $wpdb;

			if(empty($user_code) && empty($team_code)) {
				return;
			} else {
				

				if(!empty($user_code)){
					$results = $wpdb->get_results($wpdb->prepare("SELECT seeds FROM pmgonijet_cct_seeds WHERE user = %s AND akcia = %s", $this->standardize($user_code), $akcia));
				} elseif(empty($user_code) && !empty($team_code)){
					$results = $wpdb->get_results($wpdb->prepare("SELECT seeds FROM pmgonijet_cct_seeds WHERE team = %s AND akcia = %s", $this->standardize($team_code), $akcia));
				} else {

				}

				if(!empty($results)){
					//echo 'Seeds exists ... continue ... ';
					return; // seeds uz existuju
				} else {
					$seeds = $this->create_seeds($number_of_seeds);

					if(!empty($user_code)){
						$data = array(
							'user' => $this->standardize($user_code),
							'seeds' => serialize($seeds),
							'akcia' => $akcia,
							'team' => $team_code,
						);
					} elseif(empty($user_code) && !empty($team_code)){
							$data = array(
							'team' => $this->standardize($team_code),
							'seeds' => serialize($seeds),
							'akcia' => $akcia,
							'user' => '',
						);
					} else {
						echo 'Problem ...';
						//die;
					}

					$result = $wpdb->insert('pmgonijet_cct_seeds', $data);

					if (!$result) {
						echo 'Error when creating seeds: ' . $wpdb->last_error;
						//die;
					}
				}
			}
		}
		
	}

	public function create_seeds($number_of_seeds){
		//global $all_quizes_settings;
		
		//echo 'Creating seeds ... ';
			for($i=0;$i<$number_of_seeds;$i++){
				$place = $this->cAkcia->all_quizes_settings['places'][$i];
				//echo 'Place:' . $place;
				$seeds[$place[0]] = $this->get_random_string(4);
			}
		return $seeds;
	}


	public function get_random_string($length) {
		$random_string = '';
		$valid_chars = 'abcdefghijklmnopqrstuvwxyz';

		//Count the number of chars in the valid chars string so we know how many choices we have
		$num_valid_chars = strlen($valid_chars);

		//Repeat the steps until we've created a string of the right length
		for($i=0;$i<$length;$i++) {
			//Pick a random number from 1 up to the number of valid chars
			$random_pick = mt_rand(1, $num_valid_chars);

			//Take the random character out of the string of valid chars
			//Subtract 1 from $random_pick because strings are indexed starting at 0, and we started picking at 1
			$random_char = $valid_chars[$random_pick-1];
			$random_string .= $random_char;
		}

		return $random_string;
	}  

	public function show_answer($text, $quiz_type='', $class='eventkviz_standard_answer'){
		
		global $remember_answer;
		
		if($quiz_type == 'music'){
			//global $music_settings;
			$show = $this->cAkcia->music_settings['zobraz_spravne_odpovede'];
			$show_jeho = $this->cAkcia->music_settings['zobraz_spravne_uhadnute_odpovede'];
		} elseif($quiz_type == 'movies'){
			//global $movies_settings;
			$show = $this->cAkcia->movies_settings['zobraz_spravne_odpovede'];
			$show_jeho = $this->cAkcia->movies_settings['zobraz_spravne_uhadnute_odpovede'];
		} elseif($quiz_type == 'knowledge'){
			//global $knowledge_settings;
			$show = $this->cAkcia->knowledge_settings['zobraz_spravne_odpovede'];
			$show_jeho = $this->cAkcia->knowledge_settings['zobraz_spravne_uhadnute_odpovede'];
		} elseif($quiz_type == 'sudoku'){
			//global $sudoku_settings;
			$show = $this->cAkcia->sudoku_settings['zobraz_spravne_odpovede'];
			$show_jeho = $this->cAkcia->sudoku_settings['zobraz_spravne_uhadnute_odpovede'];	
		} else {
			$show = true;
		}
		if($show === true || $show_jeho === true) {
			echo '<div class="' . $class . '">' . $text . '</div><br>';
		} else {
			if(!$remember_answer) {
				echo "<div  class='eventkviz_warning_correct_answers'>Správne odpovede na nevyplnené, alebo nesprávne zodpovedané otázky sa nezobrazujú z dôvodov určených organizátormi.</div><br>";
				$remember_answer = 'already_showed';
			} 
			
		}
	}


	public function get_random_ids($max_value, $x){
			$range_array = array();
			for( $i = 0; $i <= $max_value; $i++){
				$range_array[] .= $i*2 + 1;
			}
			echo "Range array: " . print_r($range_array) . "<br>";
			shuffle( $range_array );
			echo "Shufled: " . print_r($range_array) . "<br>";
			array_slice( $range_array, 0, $x );
			echo "Result: " . print_r($range_array) . "<br>";
			return $range_array;
		}
		
	public function UniqueRandomNumbersWithinRange($max, $quantity) {
			$numbers = range(0, $max);
			shuffle($numbers);
			return array_slice($numbers, 0, $quantity);
		}



	public function 	write_results_to_db($user, $team, $akcia, $gained_credits, $question_set, $quiz_type = '', $db_action = 'update'){
		global $wpdb;
		global $pocet_pokusov_reached;
		
		if($pocet_pokusov_reached === true) {
			return;
		} else {
			$table_name = $wpdb->prefix . 'jet_cct_results'; 
			

			

			if($db_action == 'update') {

				
				$data = array(
					'points' => $gained_credits,
					'question_set' => $question_set,
				);

				if(!empty($user)){
					$where = array(
						'user' => $this->standardize($user),
						'akcia' => $akcia,
						'quiz_type' => $quiz_type,
					);
				} elseif(empty($user) && !empty($team)){
					$where = array(
						'team' => $this->standardize($team),
						'akcia' => $akcia,
						'quiz_type' => $quiz_type,
					);
				} else {
					echo 'Warning: Data not recorded to DB. Something went wrong ...';
					die;
				}

				$result = $wpdb->update( $table_name, $data, $where );
			} else {

				$data = array(
					'team' => $this->standardize($team),
					'user' => $this->standardize($user),
					'points' => $gained_credits,
					'question_set' => $question_set,
					'akcia' => $akcia,
					'quiz_type' => $quiz_type,
				);

				$result = $wpdb->insert($table_name, $data);
			}
			

			if(!$result) {
				//echo 'Warning: Data not recorded to DB.';
				// ak nie je co updatovat, napr. ze sa nezmeni pocet bodov, tak je result = 0 a vyhadzuje chybu
			} else {
				//echo 'Info: Data recorded to DB.';
			}
		
		}
		
		
		
	}

	public function get_related_ids( $question_id,$rel_id ) {
		
		$post = get_post( $question_id ); // Replace $post_id with the ID of the Post/Page you have queried
		$object_id = $post->ID;
		
		//this is to get Relation object by its ID:
		$relation = jet_engine()->relations->get_active_relations( $rel_id ); 

		//these are to get parents/children of the Post/CCT/Term/User by ID:
		$related_ids = $relation->get_parents( $object_id, 'ids' );
		//$related_ids = $relation->get_children( $object_id, 'ids' );
		
		return $related_ids[0];
	}


	public function send_results_by_email($user='', $team='', $akcia = '', $gained_credits='', $quiz_type=''){
		
		if($quiz_type == 'music') {
			//global $music_settings;
			//$this->music_quiz_settings($akcia,$user,$team);
			$send_email = $this->cAkcia->music_settings['poslat_vysledok_usera_mailom'];
			$title = 'Music quiz';
			$email =$this->cAkcia->music_settings['admin_mail'];
		} elseif($quiz_type == 'movies') {
			//global $movies_settings;
			//$this->movies_quiz_settings($akcia,$user,$team);
			$send_email = $this->cAkcia->movies_settings['poslat_vysledok_usera_mailom'];
			$title = 'Movies quiz';
			$email = $this->cAkcia->movies_settings['admin_mail'];
		} elseif($quiz_type == 'knowledge') {
			//global $knowledge_settings;
			//$this->knowledge_quiz_settings($akcia,$user,$team);
			$send_email = $this->cAkcia->knowledge_settings['poslat_vysledok_usera_mailom'];
			$title = 'Knowledge quiz';
			$email = $this->cAkcia->knowledge_settings['admin_mail'];
		} elseif($quiz_type == 'sudoku') {
			//global $sudoku_settings;
			//$this->sudoku_quiz_settings($akcia,$user,$team);
			$send_email = $this->cAkcia->sudoku_settings['poslat_vysledok_usera_mailom'];
			$title = 'Sudoku quiz';
			$email = $this->cAkcia->sudoku_settings['admin_mail'];
		} else {
			$send_email = $this->cAkcia->all_quizes_settings['poslat_vysledok_usera_mailom'];
			$title = 'PLace: ' . $quiz_type;
			$email = $this->cAkcia->all_quizes_settings['admin_mail'];
		}
	
		if($send_email === true) {
			$subject = $title . ' - Team: ' . $team . ", User: " . $user . ", Credits: " . $gained_credits . ' points';
			$body = 'The team ' . $team . ', User: ' . $user . ', gained ' .  $gained_credits . ' points.';
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail( $email, $subject, $body, $headers );
		} 
	}

	public function all_quizes_settings($event='samorin'){
		/*
		global $all_quizes_settings;
		
		$functionName ='all_quizes_settings';
		$fname = $functionName."_".$event;
		$result = "{$fname}";
		call_user_func($result);
		*/

		if(empty($event)) {
			echo 'Someting went wrong ... setting general tag to avoid errors ...';
			$event = 'event';
			//die;
		}


		$className = 'Eventkviz_' . $event . '_Class'; 
		$methodName = 'all_quizes_settings';
		$this->cAkcia = new $className(); 
		$this->cAkcia->$methodName(); 



	}

	public function music_quiz_settings($event='', $user_code='', $team_code=''){
		/*
		global $music_settings;
		
		$functionName ='music_quiz_settings';
		$fname = $functionName."_".$event;
		$result = "{$fname}";
		call_user_func($result);
		*/

		//$className = 'Eventkviz_' . $event . '_Class'; 
		//$methodName = 'music_quiz_settings';
		//$cAkcia = new $className(); 
		//$this->cAkcia->$methodName(); 
		$this->set_seed_for_user_or_team($user_code, $event,$team_code);
	}

	public function movies_quiz_settings($event='', $user_code='', $team_code=''){
		//global $movies_settings;
		
		//$methodName ='movies_quiz_settings';
		//$fname = $functionName."_".$event;
		//$result = "{$fname}";
		//call_user_func($result);
		//$this->cAkcia->$methodName(); 
		$this->set_seed_for_user_or_team($user_code, $event,$team_code);
	}
		
	public function knowledge_quiz_settings($event='', $user_code='', $team_code=''){
		//global $knowledge_settings;
		
		//$methodName ='knowledge_quiz_settings';
		//$this->cAkcia->$methodName(); 
		$this->set_seed_for_user_or_team($user_code, $event,$team_code);
	}

	public function sudoku_quiz_settings($event='', $user_code='', $team_code=''){
		//global $sudoku_settings;
		
		//$methodName ='sudoku_quiz_settings';
		//$this->cAkcia->$methodName(); 
		$this->set_seed_for_user_or_team($user_code, $event,$team_code);
	}

	public function check_if_questions_set_exists( $akcia_code,$quiz_type,$user_code = '', $team_code = ''){
		global $wpdb;
		if(!empty($user_code)){
			$results = $wpdb->get_results($wpdb->prepare("SELECT question_set FROM pmgonijet_cct_results WHERE user = %s AND akcia = %s AND quiz_type = %s", $this->standardize($user_code), $akcia_code, $quiz_type));
		} elseif(empty($user_code) && !empty($team_code)){
			$results = $wpdb->get_results($wpdb->prepare("SELECT question_set FROM pmgonijet_cct_results WHERE team = %s AND akcia = %s AND quiz_type = %s", $this->standardize($team_code), $akcia_code, $quiz_type));
		} else {

		}
		if(!empty($results[0]->question_set)) {
			$this->questions_set = unserialize($results[0]->question_set);
		} else {
			$this->questions_set = '';
		}

		if(is_array($this->questions_set)) {
			return true;
		} else {
			return false;
		}

	}

	public function write_question_set_to_db( $questions_set, $akcia_code,$quiz_type,$user_code = '',$team_code = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'jet_cct_results'; 
			$data = array(
				'_ID' => '',
				'cct_status' => 'publish',
				'team' => $this->standardize($team_code),
				'user' => $this->standardize($user_code),
				'akcia' => $akcia_code,
				'quiz_type' => $quiz_type,
				'points' => 0,
				'question_set' => $questions_set,
			);

			$result = $wpdb->insert( $table_name, $data );
			if(!$result) {
				echo 'Warning: Data not recorded to DB.';
			} else {
				//echo 'Info: Data recorded to DB.';
			}
	}

	private function set_default_event_settings() {
    // General – presne podľa pôvodného kódu
    $this->all_quizes_settings = array(
        'startup_form'                  => true,
        'identifikacia_kodom_usera'     => false,
        'verify_users_in_db'            => false,
        'identifikacia_userov_timu'     => true,
        'select_from_teams_array'       => true,
        'select_teams'                  => array(
            ''      => 'Select ...',
            'team1' => 'Team 1',
            'team2' => 'Team 2',
            'team3' => 'Team 3',
            'team4' => 'Team 4',
            'team5' => 'Team 5',
            'team6' => 'Team 6',
            'team7' => 'Team 7',
            'team8' => 'Team 8',
            'team9' => 'Team 9',
            'team10'=> 'Team 10'
        ),
        'use_seed'                      => false,
        'places'                        => array(
            array( 'sudoku', 'Sudoku quiz' ),
            array( 'movies', 'Movies quiz' ),
            array( 'music', 'Music quiz' ),
            array( 'knowledge', 'Knowledge quiz' )
        ),
        'names_of_places'               => array(
            'sudoku'    => 'Sudoku quiz',
            'movies'    => 'Movies quiz',
            'music'     => 'Music quiz',
            'knowledge' => 'Knowledge quiz'
        ),
        'show_link_back_to_all_quizes'  => false,
        'minimal_number_of_correct_seeds' => 3,
        'final_place_pocet_pokusov'    => 3
    );

    // Music – defaults podľa pôvodného
    $this->music_settings = array(
        'music_quiz_active'               => true,
        'show_entry_form'                 => true,
        'credits'                         => array(
            'corr_art_corr_pos_corr_song_corr_pos' => 100,
            'corr_art_corr_pos_incorr_song'       => 50,
            'incorr_art_corr_song_corr_pos'       => 50,
        ),
        'pocet_otazok_v_sete'             => 10,
        'production'                      => 'all',
        'poslat_vysledok_usera_mailom'    => false,
        'admin_mail'                      => 'mahroch@gmail.com',
        'zobraz_spravne_odpovede'         => false,
        'zobraz_spravne_uhadnute_odpovede'=> true,
        'pocet_pokusov'                   => 10,
        'min_body_na_postup'              => 400,
        'obrazok_pri_splneni_kvizu'       => 1852,
    );

    // Movies – defaults podľa pôvodného
    $this->movies_settings = array(
        'movies_quiz_active'              => true,
        'show_entry_form'                 => true,
        'movies_quiz_type'                => 'full',
        'credits'                         => array(
            'corr_movie' => 100,
            'corr_movie_wrong_pos' => 0,
        ),
        'pocet_otazok_v_sete'             => 10,
        'production'                      => 'all',
        'poslat_vysledok_usera_mailom'    => false,
        'admin_mail'                      => 'mahroch@gmail.com',
        'zobraz_spravne_odpovede'         => false,
        'zobraz_spravne_uhadnute_odpovede'=> true,
        'pocet_pokusov'                   => 10,
        'min_body_na_postup'              => 400,
        'obrazok_pri_splneni_kvizu'       => 1852,
    );

    // Knowledge – defaults podľa pôvodného
    $this->knowledge_settings = array(
        'knowledge_quiz_active'           => true,
        'show_entry_form'                 => true,
        'credits'                         => array(
            'corr_answer' => 100
        ),
        'pocet_otazok_v_sete'             => 0,
        'topic'                           => 'all',
        'number_question_in_topic'        => array(
            'visual'       => 0,
            'mathematical' => 0,
            'geography'    => 0,
            'general'      => 0,
            'movies'       => 0,
            'viglas'       => 15
        ),
        'poslat_vysledok_usera_mailom'    => false,
        'admin_mail'                      => 'mahroch@gmail.com',
        'zobraz_spravne_odpovede'         => false,
        'zobraz_spravne_uhadnute_odpovede'=> true,
        'pocet_pokusov'                   => 10,
        'min_body_na_postup'              => 400,
        'obrazok_pri_splneni_kvizu'       => 1851,
    );

    // Sudoku – defaults podľa pôvodného
    $this->sudoku_settings = array(
        'sudoku_quiz_active'              => false,
        'show_entry_form'                 => false,
        'credits'                         => array(
            'easy'   => 10,
            'medium' => 20,
            'hard'   => 35,
        ),
        'pocet_otazok_v_sete'             => 1,
        'moze_si_vybrat_difficulty'       => 'yes',
        'default_difficulty'              => 'hard',
        'poslat_vysledok_usera_mailom'    => false,
        'admin_mail'                      => 'mahroch@gmail.com',
        'zobraz_spravne_odpovede'         => true,
        'zobraz_spravne_uhadnute_odpovede'=> true,
        'pocet_pokusov'                   => 1,
    );
}

     
}

