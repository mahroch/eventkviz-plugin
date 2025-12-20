<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Eventkviz
 * @subpackage Eventkviz/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Eventkviz
 * @subpackage Eventkviz/admin
 * @author     Your Name <email@example.com>
 */
class Eventkviz_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $eventkviz    The ID of this plugin.
	 */
	private $eventkviz;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $eventkviz       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $eventkviz, $version ) {

		$this->eventkviz = $eventkviz;
		$this->version = $version;

		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'init', array( $this, 'register_event_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_event_meta_boxes' ) );
		add_action( 'save_post_eventkviz_event', array( $this, 'save_event_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		add_action( 'save_post_eventkviz_event', array( $this, 'create_event_pages' ), 10, 3 );
		// Pre trash (event ide do koša)
add_action( 'trashed_post', array( $this, 'delete_event_pages' ) );

// Pre permanent delete (vyprázdnenie koša alebo force delete)
add_action( 'deleted_post', array( $this, 'delete_event_pages' ), 10, 2 ); // 2 parametre pre deleted_post
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventkviz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventkviz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->eventkviz, plugin_dir_url( __FILE__ ) . 'css/eventkviz-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventkviz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventkviz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->eventkviz, plugin_dir_url( __FILE__ ) . 'js/eventkviz-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
 * Registrácia Custom Post Type pre Eventy
 */
public function register_event_cpt() {
    $labels = array(
        'name'               => __( 'Eventy', 'eventkviz' ),
        'singular_name'      => __( 'Event', 'eventkviz' ),
        'menu_name'          => __( 'Eventy', 'eventkviz' ),
        'add_new'            => __( 'Pridaj event', 'eventkviz' ),
        'add_new_item'       => __( 'Pridaj nový event', 'eventkviz' ),
        'edit_item'          => __( 'Uprav event', 'eventkviz' ),
        'new_item'           => __( 'Nový event', 'eventkviz' ),
        'view_item'          => __( 'Zobraziť event', 'eventkviz' ),
        'search_items'       => __( 'Hľadať eventy', 'eventkviz' ),
        'not_found'          => __( 'Žiadne eventy nenájdené', 'eventkviz' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,  // Nie verejný (len v admin)
        'show_ui'            => true,
        'show_in_menu'       => false,  // Skryjeme default menu, použijeme naše
        'supports'           => array( 'title' ),  // Len názov (ostatné v metaboxoch)
        'capability_type'    => 'post',
        'capabilities'       => array( 'create_posts' => 'manage_options' ),
        'map_meta_cap'       => true,
    );

    register_post_type( 'eventkviz_event', $args );
}

/**
 * Pridanie hlavného menu a submenu
 */
public function add_plugin_admin_menu() {
    // Hlavná záložka
    add_menu_page(
        __( 'EventKviz', 'eventkviz' ),
        __( 'EventKviz', 'eventkviz' ),
        'manage_options',
        'edit.php?post_type=eventkviz_event',
        '',
        'dashicons-games',
        21  // Pozícia hneď za Pages (Pages je na 20)
    );

    // Submenu: Zoznam eventov (default WP)
    add_submenu_page(
        'edit.php?post_type=eventkviz_event',
        __( 'Zoznam eventov', 'eventkviz' ),
        __( 'Zoznam eventov', 'eventkviz' ),
        'manage_options',
        'edit.php?post_type=eventkviz_event'
    );

    // Submenu: Pridaj event (default WP add new)
    add_submenu_page(
        'edit.php?post_type=eventkviz_event',
        __( 'Pridaj event', 'eventkviz' ),
        __( 'Pridaj event', 'eventkviz' ),
        'manage_options',
        'post-new.php?post_type=eventkviz_event'
    );
}

/**
 * Enqueue scriptov pre tabs na edit stránke
 */
public function enqueue_tabs_scripts( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    global $post;
    if ( 'eventkviz_event' !== $post->post_type ) {
        return;
    }

    wp_enqueue_style( 'eventkviz-tabs', plugin_dir_url( __FILE__ ) . '../admin/css/tabs.css', array(), $this->version );  // Voliteľný CSS
    wp_enqueue_script( 'eventkviz-tabs', plugin_dir_url( __FILE__ ) . '../admin/js/tabs.js', array( 'jquery' ), $this->version, true );
}

/**
 * Pridanie metaboxu s tabmi
 */
public function add_event_meta_boxes() {
    add_meta_box(
        'eventkviz_settings_tabs',
        __( 'Nastavenia eventu', 'eventkviz' ),
        array( $this, 'render_settings_tabs' ),
        'eventkviz_event',
        'normal',
        'high'
    );
}
/**
 * Render tabs s nastaveniami
 */
public function render_settings_tabs( $post ) {
    wp_nonce_field( 'eventkviz_save_meta', 'eventkviz_meta_nonce' );

    $meta = get_post_meta( $post->ID );

    ?>
    <div class="eventkviz-tabs">
        <ul class="tabs-nav">
            <li class="active"><a href="#tab-general">General</a></li>
            <li><a href="#tab-music">Music</a></li>
            <li><a href="#tab-movies">Movies</a></li>
            <li><a href="#tab-knowledge">Knowledge</a></li>
            <li><a href="#tab-sudoku">Sudoku</a></li>
        </ul>

        <?php
        // Volanie samostatných metód pre každý tab
        $this->render_general_tab( $post, $meta );
        $this->render_music_tab( $post, $meta );
        $this->render_movies_tab( $post, $meta );
        $this->render_knowledge_tab( $post, $meta );
        $this->render_sudoku_tab( $post, $meta );
        ?>
    </div>

    <script>
    jQuery(function($) {
        // Tabs switching
        $('.tabs-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $('.tab-content').hide();
            $(target).show();
            $('.tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
        });
        $('.tabs-nav li:first a').trigger('click');

        // Conditional show/hide (pre General tab – presunuté do sub-metódy, ale JS ostáva tu)
        function toggleTeams() {
            if ($('#select_from_teams_array_cb').is(':checked')) {
                $('#select_teams_container').show();
            } else {
                $('#select_teams_container').hide();
            }
        }
        function togglePlaces() {
            if ($('#use_seed_cb').is(':checked')) {
                $('#places_container').show();
            } else {
                $('#places_container').hide();
            }
        }
        $('#select_from_teams_array_cb').on('change', toggleTeams);
        $('#use_seed_cb').on('change', togglePlaces);
        toggleTeams();
        togglePlaces();
    });
    </script>

    <style>
    .eventkviz-tabs .tabs-nav { list-style: none; display: flex; margin: 0; padding: 0; border-bottom: 2px solid #ccc; background: #f1f1f1; }
    .eventkviz-tabs .tabs-nav li { margin: 0; }
    .eventkviz-tabs .tabs-nav li a { padding: 10px 20px; display: block; text-decoration: none; color: #333; }
    .eventkviz-tabs .tabs-nav li.active a { background: #fff; border-bottom: 2px solid #0073aa; font-weight: bold; }
    .tab-content { display: none; padding: 20px; background: #fff; border: 1px solid #ccc; border-top: none; }
    </style>
    <?php
}

/**
 * Uloženie meta dát eventu
 */
public function save_event_meta( $post_id ) {
    if ( ! isset( $_POST['eventkviz_meta_nonce'] ) || ! wp_verify_nonce( $_POST['eventkviz_meta_nonce'], 'eventkviz_save_meta' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options', $post_id ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // === GENERAL TAB ===
    if ( isset( $_POST['event_general'] ) && is_array( $_POST['event_general'] ) ) {
        $general_fields = $_POST['event_general'];

        // Bool polia (checkboxy) – zoznam všetkých bool premenných
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
            $value = isset( $general_fields[$key] ) && $general_fields[$key] == '1' ? '1' : '0';
            update_post_meta( $post_id, 'event_general_' . $key, $value );
        }

        // Špeciálne JSON array polia (select_teams, places, names_of_places)
        $json_fields = [
            'select_teams_json' => 'event_general_select_teams',
            'places_json' => 'event_general_places',
            'names_of_places_json' => 'event_general_names_of_places'
        ];

        foreach ( $json_fields as $post_key => $meta_key ) {
            if ( isset( $general_fields[$post_key] ) ) {
                $json = stripslashes( $general_fields[$post_key] );
                $array = json_decode( $json, true );
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $array ) ) {
                    update_post_meta( $post_id, $meta_key, $array );
                } else {
                    // Ak JSON nie je valid, vymaž (aby sa nepokazilo)
                    delete_post_meta( $post_id, $meta_key );
                }
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }

    // === OSTATNÉ TABS (music, movies, knowledge, sudoku) ===
    $quiz_types = ['music', 'movies', 'knowledge', 'sudoku'];
    foreach ( $quiz_types as $type ) {
        if ( isset( $_POST['event_' . $type] ) && is_array( $_POST['event_' . $type] ) ) {
            foreach ( $_POST['event_' . $type] as $key => $value ) {
                $sanitized_value = sanitize_text_field( $value );
                update_post_meta( $post_id, 'event_' . $type . '_' . sanitize_key( $key ), $sanitized_value );
            }
        }
    }

    // === ŠPECIÁLNA LOGIKA PRE VŠETKY KVÍZ TABS: Vymazanie opačnej hodnoty pri splnení kvízu ===
    foreach ( $quiz_types as $type ) {
        if ( isset( $_POST['event_' . $type]['format_pri_splneni'] ) ) {
            $format = sanitize_text_field( $_POST['event_' . $type]['format_pri_splneni'] );

            if ( $format === 'obrazok' ) {
                // Ak je vybraný obrázok, vymaž text
                delete_post_meta( $post_id, 'event_' . $type . '_text_pri_splneni_kvizu' );
            } elseif ( $format === 'text' ) {
                // Ak je vybraný text, vymaž obrázok ID
                delete_post_meta( $post_id, 'event_' . $type . '_obrazok_pri_splneni_kvizu' );
            }
        }
    }
    // Explicit handling pre checkboxy v kvízoch (ak nie sú v POST, ulož '0')
    $quiz_checkbox_keys = [
        'music'     => ['music_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede'],
        'movies'    => ['movies_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede'],
        'knowledge' => ['knowledge_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede'],
        'sudoku'    => ['sudoku_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede']
    ];

    foreach ( $quiz_checkbox_keys as $type => $keys ) {
        if ( isset( $_POST['event_' . $type] ) ) {
            foreach ( $keys as $key ) {
                $meta_key = 'event_' . $type . '_' . $key;
                if ( ! isset( $_POST['event_' . $type][$key] ) ) {
                    update_post_meta( $post_id, $meta_key, '0' );
                }
            }
        }
    }
}

	/**
 * Render General tabu
 */
private function render_general_tab( $post, $meta ) {
    ?>
    <div id="tab-general" class="tab-content" style="display: block;">
        <h3>Všeobecné nastavenia eventu</h3>
        <table class="form-table" role="presentation">

			<!-- Zobrazenie slugu eventu (read-only) -->
			<tr>
				<th><label>Slug eventu</label></th>
				<td>
					<?php if ( $post->post_name ) : ?>
						<input type="text" value="<?php echo esc_attr( $post->post_name ); ?>" disabled class="regular-text" />
						<p class="description">
							Slug eventu (automaticky vygenerovaný z názvu). Používa sa napr. v shortcodoch alebo URL (napr. ?eventkviz_event=<?php echo esc_attr( $post->post_name ); ?>).<br>
							Ak chceš zmeniť slug, uprav názov eventu a ulož (WP ho pregeneruje, ak nie je duplicitný).
						</p>
					<?php else : ?>
						<p><em>Bude automaticky vygenerovaný z názvu eventu po prvom uložení.</em></p>
						<p class="description">Slug sa vytvorí z názvu (title) – malé písmená, pomlčky namiesto medzier.</p>
					<?php endif; ?>
				</td>
			</tr>

            <!-- startup_form -->
            <tr>
                <th><label>Zobraz startup formulár (user + tím)</label></th>
                <td>
                    <input type="checkbox" name="event_general[startup_form]" value="1" <?php checked( $meta['event_general_startup_form'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        Možnosť vstúpiť do kvízu na základe konkrétnej URL (bez zadávania dodatočných kódov), alebo pomocou formulára na zadanie kódu používateľa a tímu.<br>
                        <strong>true</strong> = zobraz formulár na zadanie usera a tímu<br>
                        <strong>false</strong> = údaje usera a tímu sa načítajú z URL
                    </p>
                </td>
            </tr>

            <!-- identifikacia_kodom_usera -->
            <tr>
                <th><label>Identifikácia kódom usera</label></th>
                <td>
                    <input type="checkbox" name="event_general[identifikacia_kodom_usera]" value="1" <?php checked( $meta['event_general_identifikacia_kodom_usera'][0] ?? '0', '1' ); ?> />
                    <p class="description">true/false - možnosť identifikovať používateľa kódom</p>
                </td>
            </tr>

            <!-- verify_users_in_db -->
            <tr>
                <th><label>Verifikovať userov v DB</label></th>
                <td>
                    <input type="checkbox" name="event_general[verify_users_in_db]" value="1" <?php checked( $meta['event_general_verify_users_in_db'][0] ?? '0', '1' ); ?> />
                    <p class="description">true/false - možnosť verifikovať používateľa v predvyplnenej databáze</p>
                </td>
            </tr>

            <!-- identifikacia_userov_timu -->
            <tr>
                <th><label>Identifikácia viacerých userov v tíme</label></th>
                <td>
                    <input type="checkbox" name="event_general[identifikacia_userov_timu]" value="1" <?php checked( $meta['event_general_identifikacia_userov_timu'][0] ?? '1', '1' ); ?> />
                    <p class="description">
                        true/false - možnosť identifikovania viacerých používateľov v rámci jedného tímu.<br>
                        FALSE = ak je zoznam userov s priradenými tímami, tím sa vyberie automaticky.
                    </p>
                </td>
            </tr>

            <!-- select_from_teams_array + conditional textarea -->
            <tr>
                <th><label>Výber tímu zo zoznamu</label></th>
                <td>
                    <input type="checkbox" id="select_from_teams_array_cb" name="event_general[select_from_teams_array]" value="1" <?php checked( $meta['event_general_select_from_teams_array'][0] ?? '1', '1' ); ?> />
                    <p class="description">true/false - má sa tím vybrať z vopred zadaného zoznamu, zoznam nižšie.</p>

                    <div id="select_teams_container" style="margin-top: 10px; <?php echo ( $meta['event_general_select_from_teams_array'][0] ?? '1' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                        <label><strong>Zoznam tímov (JSON formát objektu):</strong></label><br>
                        <textarea name="event_general[select_teams_json]" rows="10" class="large-text" placeholder='{"": "Select ...", "team1": "Team 1", "team2": "Team 2"}'><?php 
                            $teams = get_post_meta( $post->ID, 'event_general_select_teams', true );
                            echo esc_textarea( json_encode( $teams ?: array('' => 'Select ...', 'team1' => 'Team 1', 'team2' => 'Team 2', 'team3' => 'Team 3', 'team4' => 'Team 4', 'team5' => 'Team 5', 'team6' => 'Team 6', 'team7' => 'Team 7', 'team8' => 'Team 8', 'team9' => 'Team 9', 'team10' => 'Team 10'), JSON_PRETTY_PRINT ) );
                        ?></textarea>
                        <p class="description">Formát: {"key": "Názov tímu", ...} – key je hodnota, názov sa zobrazí.</p>
                    </div>
                </td>
            </tr>

            <!-- use_seed + conditional textareas -->
            <tr>
                <th><label>Použiť seed (kódy pre stanovištia)</label></th>
                <td>
                    <input type="checkbox" id="use_seed_cb" name="event_general[use_seed]" value="1" <?php checked( $meta['event_general_use_seed'][0] ?? '0', '1' ); ?> />
                    <p class="description">true/false - pri získaní stanovišťa sa používateľovi ukáže kód za dané stanovište</p>

                    <div id="places_container" style="margin-top: 10px; <?php echo ( $meta['event_general_use_seed'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                        <label><strong>Poradie stanovísk (places – JSON indexed array):</strong></label><br>
                        <textarea name="event_general[places_json]" rows="8" class="large-text" placeholder='[[ "sudoku", "Sudoku quiz" ], [ "movies", "Movies quiz" ]]'><?php 
                            $places = get_post_meta( $post->ID, 'event_general_places', true );
                            echo esc_textarea( json_encode( $places ?: array(
                                array('sudoku', 'Sudoku quiz'),
                                array('movies', 'Movies quiz'),
                                array('music', 'Music quiz'),
                                array('knowledge', 'Knowledge quiz')
                            ), JSON_PRETTY_PRINT ) );
                        ?></textarea>
                        <p class="description">Používa sa len ak sa používajú seeds. Formát: [["typ", "Názov"], ...] – indexované pole.</p>

                        <label><strong>Názvy stanovísk (names_of_places – JSON objekt):</strong></label><br>
                        <textarea name="event_general[names_of_places_json]" rows="6" class="large-text" placeholder='{"sudoku": "Sudoku quiz", "movies": "Movies quiz"}'><?php 
                            $names = get_post_meta( $post->ID, 'event_general_names_of_places', true );
                            echo esc_textarea( json_encode( $names ?: array(
                                'sudoku' => 'Sudoku quiz',
                                'movies' => 'Movies quiz',
                                'music' => 'Music quiz',
                                'knowledge' => 'Knowledge quiz'
                            ), JSON_PRETTY_PRINT ) );
                        ?></textarea>
                        <p class="description">Používa sa len ak sa používajú seeds. Formát: {"typ": "Názov", ...}</p>
                    </div>
                </td>
            </tr>

            <!-- show_link_back_to_all_quizes -->
            <tr>
                <th><label>Zobraziť link späť na všetky kvízy</label></th>
                <td>
                    <input type="checkbox" name="event_general[show_link_back_to_all_quizes]" value="1" <?php checked( $meta['event_general_show_link_back_to_all_quizes'][0] ?? '0', '1' ); ?> />
                    <p class="description">true/false - zobraz linku na preklik späť na všetky kvízy</p>
                </td>
            </tr>

        </table>
    </div>
    <?php
}
/**
 * Render Music tabu
 */
private function render_music_tab( $post, $meta ) {
    // Načítaj aktuálne hodnoty
    $image_id = isset( $meta['event_music_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_music_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_music_format_pri_splneni'][0] ) ? $meta['event_music_format_pri_splneni'][0] : 'obrazok'; // default obrazok
    $text_pri_splneni = isset( $meta['event_music_text_pri_splneni_kvizu'][0] ) ? $meta['event_music_text_pri_splneni_kvizu'][0] : '';

    // Načítaj credits hodnoty
    $credits = array(
        'corr_art_corr_pos_corr_song_corr_pos' => isset( $meta['event_music_credits_corr_art_corr_pos_corr_song_corr_pos'][0] ) ? (int) $meta['event_music_credits_corr_art_corr_pos_corr_song_corr_pos'][0] : 100,
        'corr_art_corr_pos_incorr_song'       => isset( $meta['event_music_credits_corr_art_corr_pos_incorr_song'][0] ) ? (int) $meta['event_music_credits_corr_art_corr_pos_incorr_song'][0] : 50,
        'incorr_art_corr_song_corr_pos'       => isset( $meta['event_music_credits_incorr_art_corr_song_corr_pos'][0] ) ? (int) $meta['event_music_credits_incorr_art_corr_song_corr_pos'][0] : 50,
    );

    // Aktívny kvíz – pre conditional
    $music_active = isset( $meta['event_music_music_quiz_active'][0] ) ? $meta['event_music_music_quiz_active'][0] : '1';
    ?>
    <div id="tab-music" class="tab-content">
        <h3>Music kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <!-- Aktívny Music kvíz (vždy viditeľný) -->
            <tr>
                <th><label>Aktívny Music kvíz</label></th>
                <td>
                    <input type="checkbox" id="music_quiz_active_cb" name="event_music[music_quiz_active]" value="1" <?php checked( $music_active, '1' ); ?> />
                    <p class="description">true/false - či je Music kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <!-- VŠETKY OSTATNÉ POLIA (conditional podľa aktívneho kvízu) -->
            <tbody id="music_fields_container" style="<?php echo $music_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <!-- show_entry_form -->
                <tr>
                    <th><label>Zobraziť entry formulár</label></th>
                    <td>
                        <input type="checkbox" name="event_music[show_entry_form]" value="1" <?php checked( $meta['event_music_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            true/false, používa sa keď potrebujem jednu URL na tento kvíz ukázať viacerým tímom, ktorí si pred kvízom musia vybrať svoj tím.<br>
                            Nie cez all links, ale len pre tento konkrétny kvíz.
                        </p>
                    </td>
                </tr>

                <!-- Bodovanie / kredity -->
                <tr>
                    <th><label>Bodovanie</label></th>
                    <td>
                        <table class="widefat fixed" style="width: auto;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 300px;">Popis</th>
                                    <th style="text-align: left;">Body</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Správny umelec, správna pieseň</td>
                                    <td>
                                        <input type="number" name="event_music[credits_corr_art_corr_pos_corr_song_corr_pos]" value="<?php echo esc_attr( $credits['corr_art_corr_pos_corr_song_corr_pos'] ); ?>" min="0" class="small-text" />
                                        
                                    </td>
                                </tr>
                                <tr>
                                    <td>Správny umelec, nesprávna pieseň</td>
                                    <td>
                                        <input type="number" name="event_music[credits_corr_art_corr_pos_incorr_song]" value="<?php echo esc_attr( $credits['corr_art_corr_pos_incorr_song'] ); ?>" min="0" class="small-text" />
                                        
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nesprávny umelec, správna pieseň</td>
                                    <td>
                                        <input type="number" name="event_music[credits_incorr_art_corr_song_corr_pos]" value="<?php echo esc_attr( $credits['incorr_art_corr_song_corr_pos'] ); ?>" min="0" class="small-text" />
                                        
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">Nastavenie bodov za rôzne kombinácie správnosti umelca a piesne.</p>
                    </td>
                </tr>

                <!-- pocet_otazok_v_sete -->
                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_music[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_music_pocet_otazok_v_sete'][0] ?? '10' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            Číslo - možnosť zvoliť si koľko otázok v sete dostane používateľ.<br>
                   
                        </p>
                    </td>
                </tr>

                <!-- production -->
                <tr>
                    <th><label>Production (typ hudby)</label></th>
                    <td>
                        <select name="event_music[production]">
                            <option value="all" <?php selected( $meta['event_music_production'][0] ?? 'all', 'all' ); ?>>All</option>
                            <option value="skcz" <?php selected( $meta['event_music_production'][0] ?? 'all', 'skcz' ); ?>>SK/CZ</option>
                            <option value="zahranicne" <?php selected( $meta['event_music_production'][0] ?? 'all', 'zahranicne' ); ?>>Zahraničné</option>
                        </select>
                        <p class="description">skcz/zahranicne/all - výber typu hudobnej produkcie</p>
                    </td>
                </tr>

                <!-- poslat_vysledok_usera_mailom + conditional admin_mail -->
                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb" name="event_music[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_music_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container" style="margin-top: 10px; <?php echo ( $meta['event_music_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_music[admin_mail]" value="<?php echo esc_attr( $meta['event_music_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <!-- zobraz_spravne_odpovede -->
                <tr>
                    <th><label>Zobraziť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_music[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_music_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- zobraz_spravne_uhadnute_odpovede -->
                <tr>
                    <th><label>Zobraziť správne uhádnuté odpovede používateľa</label></th>
                    <td>
                        <input type="checkbox" name="event_music[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_music_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia JEHO VLASTNÉ správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- pocet_pokusov -->
                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_music[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_music_pocet_pokusov'][0] ?? '10' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

                <!-- min_body_na_postup -->
                <tr>
                    <th><label>Minimálne body na postup</label></th>
                    <td>
                        <input type="number" name="event_music[min_body_na_postup]" value="<?php echo esc_attr( $meta['event_music_min_body_na_postup'][0] ?? '400' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            počet bodov na splnenie kvízu - používa sa na zobrazenie riadku sudoku, alebo iného kľúča.<br>
                            Ak je 0, tak sa nekontroluje počet.
                        </p>
                    </td>
                </tr>

                <!-- Formát pri splnení kvízu + conditional -->
                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select" name="event_music[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu (po dosiahnutí minimálnych bodov).</p>

                        <!-- Conditional: Obrázok -->
                        <div id="obrazok_container" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_music[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id" data-preview="#obrazok_preview">
                                <?php _e( 'Vybrať obrázok', 'eventkviz' ); ?>
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id" data-preview="#obrazok_preview" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                <?php _e( 'Odstrániť obrázok', 'eventkviz' ); ?>
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

                        <!-- Conditional: Text -->
                        <div id="text_container" style="margin-top: 15px; <?php echo $format_pri_splneni === 'text' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Text pri splnení kvízu:</strong></label><br>
                            <textarea name="event_music[text_pri_splneni_kvizu]" rows="6" class="large-text"><?php echo esc_textarea( $text_pri_splneni ); ?></textarea>
                            <p class="description">Custom text, ktorý sa zobrazí po splnení kvízu (podporuje HTML).</p>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    <script>
    jQuery(function($) {
        // Conditional pre celý Music obsah
        function toggleMusicFields() {
            if ($('#music_quiz_active_cb').is(':checked')) {
                $('#music_fields_container').show();
            } else {
                $('#music_fields_container').hide();
            }
        }
        $('#music_quiz_active_cb').on('change', toggleMusicFields);
        toggleMusicFields();

        // Ostatné conditional (admin_mail, format_pri_splneni, uploader) – ostávajú rovnaké
        function toggleAdminMail() {
            if ($('#poslat_vysledok_mailom_cb').is(':checked')) {
                $('#admin_mail_container').show();
            } else {
                $('#admin_mail_container').hide();
            }
        }
        $('#poslat_vysledok_mailom_cb').on('change', toggleAdminMail);
        toggleAdminMail();

        function toggleFormatPriSplneni() {
            var value = $('#format_pri_splneni_select').val();
            if (value === 'obrazok') {
                $('#obrazok_container').show();
                $('#text_container').hide();
            } else if (value === 'text') {
                $('#obrazok_container').hide();
                $('#text_container').show();
            }
        }
        $('#format_pri_splneni_select').on('change', toggleFormatPriSplneni);
        toggleFormatPriSplneni();

        // Media Uploader
        $('.upload_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));

            var frame = wp.media({
                title: 'Vybrať obrázok',
                button: { text: 'Použiť tento obrázok' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.id);
                preview.html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;" />');
                button.siblings('.remove_obrazok_button').show();
            });

            frame.open();
        });

        $('.remove_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));
            target.val('');
            preview.html('');
            button.hide();
        });
    });
    </script>
    <?php
}

/**
 * Render Movies tabu
 */
private function render_movies_tab( $post, $meta ) {
    // Načítaj aktuálne hodnoty
    $image_id = isset( $meta['event_movies_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_movies_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_movies_format_pri_splneni'][0] ) ? $meta['event_movies_format_pri_splneni'][0] : 'obrazok'; // default obrazok
    $text_pri_splneni = isset( $meta['event_movies_text_pri_splneni_kvizu'][0] ) ? $meta['event_movies_text_pri_splneni_kvizu'][0] : '';

    // Načítaj credits hodnoty (len corr_movie)
    $credits_corr_movie = isset( $meta['event_movies_credits_corr_movie'][0] ) ? (int) $meta['event_movies_credits_corr_movie'][0] : 100;

    // Aktívny kvíz – pre conditional
    $movies_active = isset( $meta['event_movies_movies_quiz_active'][0] ) ? $meta['event_movies_movies_quiz_active'][0] : '1';
    ?>
    <div id="tab-movies" class="tab-content">
        <h3>Movies kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <!-- Aktívny Movies kvíz (vždy viditeľný) -->
            <tr>
                <th><label>Aktívny Movies kvíz</label></th>
                <td>
                    <input type="checkbox" id="movies_quiz_active_cb" name="event_movies[movies_quiz_active]" value="1" <?php checked( $movies_active, '1' ); ?> />
                    <p class="description">true/false - či je Movies kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <!-- VŠETKY OSTATNÉ POLIA (conditional podľa aktívneho kvízu) -->
            <tbody id="movies_fields_container" style="<?php echo $movies_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <!-- show_entry_form -->
                <tr>
                    <th><label>Zobraziť entry formulár</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[show_entry_form]" value="1" <?php checked( $meta['event_movies_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            true/false, používa sa keď potrebujem jednu URL na tento kvíz ukázať viacerým tímom, ktorí si pred kvízom musia vybrať svoj tím.<br>
                            Nie cez all links, ale len pre tento konkrétny kvíz.
                        </p>
                    </td>
                </tr>

                <!-- movies_quiz_type -->
                <tr>
                    <th><label>Typ Movies kvízu</label></th>
                    <td>
                        <select name="event_movies[movies_quiz_type]">
                            <option value="full" <?php selected( $meta['event_movies_movies_quiz_type'][0] ?? 'full', 'full' ); ?>>Full</option>
                            <option value="choices" <?php selected( $meta['event_movies_movies_quiz_type'][0] ?? 'full', 'choices' ); ?>>Choices</option>
                        </select>
                        <p class="description">
                            full/choices - full = na výber zo všetkých filmov po zadaní písmeniek,<br>
                            choices = na výber z 10 predvybraných filmov
                        </p>
                    </td>
                </tr>

                <!-- Bodovanie / kredity - len jedno pole -->
                <tr>
                    <th><label>Bodovanie</label></th>
                    <td>
                        <table class="widefat fixed" style="width: auto;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 300px;">Popis</th>
                                    <th style="text-align: left;">Body</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Správny film</td>
                                    <td>
                                        <input type="number" name="event_movies[credits_corr_movie]" value="<?php echo esc_attr( $credits_corr_movie ); ?>" min="0" class="small-text" />
                                        <p class="description">správny film</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">Nastavenie bodov za správny film.</p>
                    </td>
                </tr>

                <!-- pocet_otazok_v_sete -->
                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_movies[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_movies_pocet_otazok_v_sete'][0] ?? '10' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            0/číslo - možnosť zvoliť si koľko otázok v sete dostane používateľ.<br>
                            0 znamená, že sa vyberá podľa žiadneho množstva v production settingu.
                        </p>
                    </td>
                </tr>

                <!-- production -->
                <tr>
                    <th><label>Production (typ filmov)</label></th>
                    <td>
                        <select name="event_movies[production]">
                            <option value="all" <?php selected( $meta['event_movies_production'][0] ?? 'all', 'all' ); ?>>All</option>
                            <option value="skcz" <?php selected( $meta['event_movies_production'][0] ?? 'all', 'skcz' ); ?>>SK/CZ</option>
                            <option value="zahranicne" <?php selected( $meta['event_movies_production'][0] ?? 'all', 'zahranicne' ); ?>>Zahraničné</option>
                        </select>
                        <p class="description">skcz/zahranicne/all - výber typu filmovej produkcie</p>
                    </td>
                </tr>

                <!-- poslat_vysledok_usera_mailom + conditional admin_mail -->
                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_movies" name="event_movies[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_movies_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_movies" style="margin-top: 10px; <?php echo ( $meta['event_movies_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_movies[admin_mail]" value="<?php echo esc_attr( $meta['event_movies_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <!-- zobraz_spravne_odpovede -->
                <tr>
                    <th><label>Zobraziť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_movies_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- zobraz_spravne_uhadnute_odpovede -->
                <tr>
                    <th><label>Zobraziť správne uhádnuté odpovede používateľa</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_movies_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia JEHO VLASTNÉ správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- pocet_pokusov -->
                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_movies[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_movies_pocet_pokusov'][0] ?? '10' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

                <!-- min_body_na_postup -->
                <tr>
                    <th><label>Minimálne body na postup</label></th>
                    <td>
                        <input type="number" name="event_movies[min_body_na_postup]" value="<?php echo esc_attr( $meta['event_movies_min_body_na_postup'][0] ?? '400' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            počet bodov na splnenie kvízu - používa sa na zobrazenie riadku sudoku, alebo iného kľúča.<br>
                            Ak je 0, tak sa nekontroluje počet.
                        </p>
                    </td>
                </tr>

                <!-- Formát pri splnení kvízu + conditional -->
                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_movies" name="event_movies[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu (po dosiahnutí minimálnych bodov).</p>

                        <!-- Conditional: Obrázok -->
                        <div id="obrazok_container_movies" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_movies[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_movies" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_movies" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_movies" data-preview="#obrazok_preview_movies">
                                <?php _e( 'Vybrať obrázok', 'eventkviz' ); ?>
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_movies" data-preview="#obrazok_preview_movies" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                <?php _e( 'Odstrániť obrázok', 'eventkviz' ); ?>
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

                        <!-- Conditional: Text -->
                        <div id="text_container_movies" style="margin-top: 15px; <?php echo $format_pri_splneni === 'text' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Text pri splnení kvízu:</strong></label><br>
                            <textarea name="event_movies[text_pri_splneni_kvizu]" rows="6" class="large-text"><?php echo esc_textarea( $text_pri_splneni ); ?></textarea>
                            <p class="description">Custom text, ktorý sa zobrazí po splnení kvízu (podporuje HTML).</p>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    <script>
    jQuery(function($) {
        // Conditional pre celý Movies obsah
        function toggleMoviesFields() {
            if ($('#movies_quiz_active_cb').is(':checked')) {
                $('#movies_fields_container').show();
            } else {
                $('#movies_fields_container').hide();
            }
        }
        $('#movies_quiz_active_cb').on('change', toggleMoviesFields);
        toggleMoviesFields();

        // Conditional pre admin_mail
        function toggleAdminMailMovies() {
            if ($('#poslat_vysledok_mailom_cb_movies').is(':checked')) {
                $('#admin_mail_container_movies').show();
            } else {
                $('#admin_mail_container_movies').hide();
            }
        }
        $('#poslat_vysledok_mailom_cb_movies').on('change', toggleAdminMailMovies);
        toggleAdminMailMovies();

        // Conditional pre formát pri splnení
        function toggleFormatPriSplneniMovies() {
            var value = $('#format_pri_splneni_select_movies').val();
            if (value === 'obrazok') {
                $('#obrazok_container_movies').show();
                $('#text_container_movies').hide();
            } else if (value === 'text') {
                $('#obrazok_container_movies').hide();
                $('#text_container_movies').show();
            }
        }
        $('#format_pri_splneni_select_movies').on('change', toggleFormatPriSplneniMovies);
        toggleFormatPriSplneniMovies();

        // Media Uploader
        $('.upload_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));

            var frame = wp.media({
                title: 'Vybrať obrázok',
                button: { text: 'Použiť tento obrázok' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.id);
                preview.html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;" />');
                button.siblings('.remove_obrazok_button').show();
            });

            frame.open();
        });

        $('.remove_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));
            target.val('');
            preview.html('');
            button.hide();
        });
    });
    </script>
    <?php
}

/**
 * Render Knowledge tabu
 */
private function render_knowledge_tab( $post, $meta ) {
    // Načítaj aktuálne hodnoty
    $image_id = isset( $meta['event_knowledge_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_knowledge_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_knowledge_format_pri_splneni'][0] ) ? $meta['event_knowledge_format_pri_splneni'][0] : 'obrazok'; // default obrazok
    $text_pri_splneni = isset( $meta['event_knowledge_text_pri_splneni_kvizu'][0] ) ? $meta['event_knowledge_text_pri_splneni_kvizu'][0] : '';

    // Credits
    $credits_corr_answer = isset( $meta['event_knowledge_credits_corr_answer'][0] ) ? (int) $meta['event_knowledge_credits_corr_answer'][0] : 100;

    // Dynamicky načítaj topics z taxonomie 'topic'
    $topics = get_terms( array(
        'taxonomy'   => 'topic',
        'hide_empty' => false,
    ) );

    // Aktívny kvíz – pre conditional
    $knowledge_active = isset( $meta['event_knowledge_knowledge_quiz_active'][0] ) ? $meta['event_knowledge_knowledge_quiz_active'][0] : '1';
    ?>
    <div id="tab-knowledge" class="tab-content">
        <h3>Knowledge kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <!-- Aktívny Knowledge kvíz (vždy viditeľný) -->
            <tr>
                <th><label>Aktívny Knowledge kvíz</label></th>
                <td>
                    <input type="checkbox" id="knowledge_quiz_active_cb" name="event_knowledge[knowledge_quiz_active]" value="1" <?php checked( $knowledge_active, '1' ); ?> />
                    <p class="description">true/false - či je Knowledge kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <!-- VŠETKY OSTATNÉ POLIA (conditional podľa aktívneho kvízu) -->
            <tbody id="knowledge_fields_container" style="<?php echo $knowledge_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <!-- show_entry_form -->
                <tr>
                    <th><label>Zobraziť entry formulár</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[show_entry_form]" value="1" <?php checked( $meta['event_knowledge_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            true/false, používa sa keď potrebujem jednu URL na tento kvíz ukázať viacerým tímom, ktorí si pred kvízom musia vybrať svoj tím.<br>
                            Nie cez all links, ale len pre tento konkrétny kvíz.
                        </p>
                    </td>
                </tr>

                <!-- Bodovanie / kredity - len jedno pole -->
                <tr>
                    <th><label>Bodovanie</label></th>
                    <td>
                        <table class="widefat fixed" style="width: auto;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 300px;">Popis</th>
                                    <th style="text-align: left;">Body</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Správna odpoveď</td>
                                    <td>
                                        <input type="number" name="event_knowledge[credits_corr_answer]" value="<?php echo esc_attr( $credits_corr_answer ); ?>" min="0" class="small-text" />
                                        <p class="description">správna odpoveď</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">Nastavenie bodov za správnu odpoveď.</p>
                    </td>
                </tr>

                <!-- pocet_otazok_v_sete -->
                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_knowledge[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_knowledge_pocet_otazok_v_sete'][0] ?? '0' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            0/číslo - možnosť zvoliť si koľko otázok v sete dostane používateľ.<br>
                            0 znamená, že sa vyberá podľa zadaneho množstva v topic settingu.
                        </p>
                    </td>
                </tr>

                <!-- Dynamické Počet otázok v topicu -->
                <tr>
                    <th><label>Počet otázok v topicu</label></th>
                    <td>
                        <?php if ( ! empty( $topics ) && ! is_wp_error( $topics ) ) : ?>
                            <table class="widefat fixed" style="width: auto;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; width: 200px;">Topic</th>
                                        <th style="text-align: left;">Počet otázok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $topics as $topic_term ) : 
                                        $topic_slug = $topic_term->slug;
                                        $topic_name = $topic_term->name;
                                        $current_value = isset( $meta['event_knowledge_number_question_in_topic_' . $topic_slug][0] ) ? (int) $meta['event_knowledge_number_question_in_topic_' . $topic_slug][0] : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $topic_name ); ?></td>
                                        <td>
                                            <input type="number" name="event_knowledge[number_question_in_topic_<?php echo esc_attr( $topic_slug ); ?>]" value="<?php echo esc_attr( $current_value ); ?>" min="0" class="small-text" />
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="description">Nastavte počet otázok pre každý dostupný topic (z taxonomie 'topic').</p>
                        <?php else : ?>
                            <p>Žiadne topics nenájdené v taxonomii 'topic'. Pridajte terms do taxonomie.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- poslat_vysledok_usera_mailom + conditional admin_mail -->
                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_knowledge" name="event_knowledge[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_knowledge_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_knowledge" style="margin-top: 10px; <?php echo ( $meta['event_knowledge_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_knowledge[admin_mail]" value="<?php echo esc_attr( $meta['event_knowledge_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktučný výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <!-- zobraz_spravne_odpovede -->
                <tr>
                    <th><label>Zobraziť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_knowledge_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- zobraz_spravne_uhadnute_odpovede -->
                <tr>
                    <th><label>Zobraziť správne uhádnuté odpovede používateľa</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_knowledge_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia JEHO VLASTNÉ správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- pocet_pokusov -->
                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_knowledge[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_knowledge_pocet_pokusov'][0] ?? '10' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

                <!-- min_body_na_postup -->
                <tr>
                    <th><label>Minimálne body na postup</label></th>
                    <td>
                        <input type="number" name="event_knowledge[min_body_na_postup]" value="<?php echo esc_attr( $meta['event_knowledge_min_body_na_postup'][0] ?? '400' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            počet bodov na splnenie kvízu - používa sa na zobrazenie riadku sudoku, alebo iného kľúča.<br>
                            Ak je 0, tak sa nekontroluje počet.
                        </p>
                    </td>
                </tr>

                <!-- Formát pri splnení kvízu + conditional -->
                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_knowledge" name="event_knowledge[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu (po dosiahnutí minimálnych bodov).</p>

                        <!-- Conditional: Obrázok -->
                        <div id="obrazok_container_knowledge" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_knowledge[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_knowledge" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_knowledge" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_knowledge" data-preview="#obrazok_preview_knowledge">
                                <?php _e( 'Vybrať obrázok', 'eventkviz' ); ?>
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_knowledge" data-preview="#obrazok_preview_knowledge" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                <?php _e( 'Odstrániť obrázok', 'eventkviz' ); ?>
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

                        <!-- Conditional: Text -->
                        <div id="text_container_knowledge" style="margin-top: 15px; <?php echo $format_pri_splneni === 'text' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Text pri splnení kvízu:</strong></label><br>
                            <textarea name="event_knowledge[text_pri_splneni_kvizu]" rows="6" class="large-text"><?php echo esc_textarea( $text_pri_splneni ); ?></textarea>
                            <p class="description">Custom text, ktorý sa zobrazí po splnení kvízu (podporuje HTML).</p>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    <script>
    jQuery(function($) {
        // Conditional pre celý Knowledge obsah
        function toggleKnowledgeFields() {
            if ($('#knowledge_quiz_active_cb').is(':checked')) {
                $('#knowledge_fields_container').show();
            } else {
                $('#knowledge_fields_container').hide();
            }
        }
        $('#knowledge_quiz_active_cb').on('change', toggleKnowledgeFields);
        toggleKnowledgeFields();

        // Conditional pre admin_mail
        function toggleAdminMailKnowledge() {
            if ($('#poslat_vysledok_mailom_cb_knowledge').is(':checked')) {
                $('#admin_mail_container_knowledge').show();
            } else {
                $('#admin_mail_container_knowledge').hide();
            }
        }
        $('#poslat_vysledok_mailom_cb_knowledge').on('change', toggleAdminMailKnowledge);
        toggleAdminMailKnowledge();

        // Conditional pre formát pri splnení
        function toggleFormatPriSplneniKnowledge() {
            var value = $('#format_pri_splneni_select_knowledge').val();
            if (value === 'obrazok') {
                $('#obrazok_container_knowledge').show();
                $('#text_container_knowledge').hide();
            } else if (value === 'text') {
                $('#obrazok_container_knowledge').hide();
                $('#text_container_knowledge').show();
            }
        }
        $('#format_pri_splneni_select_knowledge').on('change', toggleFormatPriSplneniKnowledge);
        toggleFormatPriSplneniKnowledge();

        // Media Uploader (globálny kód)
        $('.upload_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));

            var frame = wp.media({
                title: 'Vybrať obrázok',
                button: { text: 'Použiť tento obrázok' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.id);
                preview.html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;" />');
                button.siblings('.remove_obrazok_button').show();
            });

            frame.open();
        });

        $('.remove_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));
            target.val('');
            preview.html('');
            button.hide();
        });
    });
    </script>
    <?php
}

/**
 * Render Sudoku tabu
 */
private function render_sudoku_tab( $post, $meta ) {
    // Načítaj aktuálne hodnoty
    $image_id = isset( $meta['event_sudoku_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_sudoku_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_sudoku_format_pri_splneni'][0] ) ? $meta['event_sudoku_format_pri_splneni'][0] : 'obrazok'; // default obrazok
    $text_pri_splneni = isset( $meta['event_sudoku_text_pri_splneni_kvizu'][0] ) ? $meta['event_sudoku_text_pri_splneni_kvizu'][0] : '';

    // Credits
    $credits = array(
        'easy'   => isset( $meta['event_sudoku_credits_easy'][0] ) ? (int) $meta['event_sudoku_credits_easy'][0] : 10,
        'medium' => isset( $meta['event_sudoku_credits_medium'][0] ) ? (int) $meta['event_sudoku_credits_medium'][0] : 20,
        'hard'   => isset( $meta['event_sudoku_credits_hard'][0] ) ? (int) $meta['event_sudoku_credits_hard'][0] : 35,
    );

    // Aktívny kvíz – pre conditional
    $sudoku_active = isset( $meta['event_sudoku_sudoku_quiz_active'][0] ) ? $meta['event_sudoku_sudoku_quiz_active'][0] : '0';
    ?>
    <div id="tab-sudoku" class="tab-content">
        <h3>Sudoku kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <!-- Aktívny Sudoku kvíz (vždy viditeľný) -->
            <tr>
                <th><label>Aktívny Sudoku kvíz</label></th>
                <td>
                    <input type="checkbox" id="sudoku_quiz_active_cb" name="event_sudoku[sudoku_quiz_active]" value="1" <?php checked( $sudoku_active, '1' ); ?> />
                    <p class="description">true/false - či je Sudoku kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <!-- VŠETKY OSTATNÉ POLIA (conditional podľa aktívneho kvízu) -->
            <tbody id="sudoku_fields_container" style="<?php echo $sudoku_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <!-- show_entry_form -->
                <tr>
                    <th><label>Zobraziť entry formulár</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[show_entry_form]" value="1" <?php checked( $meta['event_sudoku_show_entry_form'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            true/false, používa sa keď potrebujem jednu URL na tento kvíz ukázať viacerým tímom, ktorí si pred kvízom musia vybrať svoj tím.<br>
                            Nie cez all links, ale len pre tento konkrétny kvíz.
                        </p>
                    </td>
                </tr>

                <!-- Bodovanie / kredity - samostatné polia -->
                <tr>
                    <th><label>Bodovanie</label></th>
                    <td>
                        <table class="widefat fixed" style="width: auto;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 300px;">Obtiažnosť</th>
                                    <th style="text-align: left;">Body</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Easy</td>
                                    <td>
                                        <input type="number" name="event_sudoku[credits_easy]" value="<?php echo esc_attr( $credits['easy'] ); ?>" min="0" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Medium</td>
                                    <td>
                                        <input type="number" name="event_sudoku[credits_medium]" value="<?php echo esc_attr( $credits['medium'] ); ?>" min="0" class="small-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Hard</td>
                                    <td>
                                        <input type="number" name="event_sudoku[credits_hard]" value="<?php echo esc_attr( $credits['hard'] ); ?>" min="0" class="small-text" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">Nastavenie bodov za Sudoku podľa obtiažnosti.</p>
                    </td>
                </tr>

                <!-- pocet_otazok_v_sete -->
                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_sudoku[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_sudoku_pocet_otazok_v_sete'][0] ?? '1' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť zvoliť si koľko otázok v sete dostane používateľ</p>
                    </td>
                </tr>

                <!-- moze_si_vybrat_difficulty -->
                <tr>
                    <th><label>Môže si vybrať obtiažnosť</label></th>
                    <td>
                        <select name="event_sudoku[moze_si_vybrat_difficulty]">
                            <option value="yes" <?php selected( $meta['event_sudoku_moze_si_vybrat_difficulty'][0] ?? 'yes', 'yes' ); ?>>Yes</option>
                            <option value="no" <?php selected( $meta['event_sudoku_moze_si_vybrat_difficulty'][0] ?? 'yes', 'no' ); ?>>No</option>
                        </select>
                        <p class="description">yes/no - či si používateľ môže vybrať obtiažnosť Sudoku</p>
                    </td>
                </tr>

                <!-- default_difficulty -->
                <tr>
                    <th><label>Predvolená obtiažnosť</label></th>
                    <td>
                        <select name="event_sudoku[default_difficulty]">
                            <option value="easy" <?php selected( $meta['event_sudoku_default_difficulty'][0] ?? 'hard', 'easy' ); ?>>Easy</option>
                            <option value="medium" <?php selected( $meta['event_sudoku_default_difficulty'][0] ?? 'hard', 'medium' ); ?>>Medium</option>
                            <option value="hard" <?php selected( $meta['event_sudoku_default_difficulty'][0] ?? 'hard', 'hard' ); ?>>Hard</option>
                        </select>
                        <p class="description">hard/medium/easy - predvolená obtiažnosť Sudoku (použije sa ak nemôže vybrať)</p>
                    </td>
                </tr>

                <!-- poslat_vysledok_usera_mailom + conditional admin_mail -->
                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_sudoku" name="event_sudoku[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_sudoku_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_sudoku" style="margin-top: 10px; <?php echo ( $meta['event_sudoku_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_sudoku[admin_mail]" value="<?php echo esc_attr( $meta['event_sudoku_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <!-- zobraz_spravne_odpovede -->
                <tr>
                    <th><label>Zobraziť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_sudoku_zobraz_spravne_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- zobraz_spravne_uhadnute_odpovede -->
                <tr>
                    <th><label>Zobraziť správne uhádnuté odpovede používateľa</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_sudoku_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">true/false - možnosť vybrať či sa používateľovi zobrazia JEHO VLASTNÉ správne odpovede po odoslaní kvízu, alebo nie</p>
                    </td>
                </tr>

                <!-- pocet_pokusov -->
                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_sudoku[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_sudoku_pocet_pokusov'][0] ?? '1' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

                <!-- Formát pri splnení kvízu + conditional -->
                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_sudoku" name="event_sudoku[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu.</p>

                        <!-- Conditional: Obrázok -->
                        <div id="obrazok_container_sudoku" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_sudoku[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_sudoku" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_sudoku" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_sudoku" data-preview="#obrazok_preview_sudoku">
                                <?php _e( 'Vybrať obrázok', 'eventkviz' ); ?>
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_sudoku" data-preview="#obrazok_preview_sudoku" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                <?php _e( 'Odstrániť obrázok', 'eventkviz' ); ?>
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

                        <!-- Conditional: Text -->
                        <div id="text_container_sudoku" style="margin-top: 15px; <?php echo $format_pri_splneni === 'text' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Text pri splnení kvízu:</strong></label><br>
                            <textarea name="event_sudoku[text_pri_splneni_kvizu]" rows="6" class="large-text"><?php echo esc_textarea( $text_pri_splneni ); ?></textarea>
                            <p class="description">Custom text, ktorý sa zobrazí po splnení kvízu (podporuje HTML).</p>
                        </div>
                    </td>
                </tr>
            </tbody>

        </table>
    </div>

    <script>
    jQuery(function($) {
        // Conditional pre celý Sudoku obsah
        function toggleSudokuFields() {
            if ($('#sudoku_quiz_active_cb').is(':checked')) {
                $('#sudoku_fields_container').show();
            } else {
                $('#sudoku_fields_container').hide();
            }
        }
        $('#sudoku_quiz_active_cb').on('change', toggleSudokuFields);
        toggleSudokuFields();

        // Conditional pre admin_mail
        function toggleAdminMailSudoku() {
            if ($('#poslat_vysledok_mailom_cb_sudoku').is(':checked')) {
                $('#admin_mail_container_sudoku').show();
            } else {
                $('#admin_mail_container_sudoku').hide();
            }
        }
        $('#poslat_vysledok_mailom_cb_sudoku').on('change', toggleAdminMailSudoku);
        toggleAdminMailSudoku();

        // Conditional pre formát pri splnení
        function toggleFormatPriSplneniSudoku() {
            var value = $('#format_pri_splneni_select_sudoku').val();
            if (value === 'obrazok') {
                $('#obrazok_container_sudoku').show();
                $('#text_container_sudoku').hide();
            } else if (value === 'text') {
                $('#obrazok_container_sudoku').hide();
                $('#text_container_sudoku').show();
            }
        }
        $('#format_pri_splneni_select_sudoku').on('change', toggleFormatPriSplneniSudoku);
        toggleFormatPriSplneniSudoku();

        // Media Uploader (globálny kód)
        $('.upload_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));

            var frame = wp.media({
                title: 'Vybrať obrázok',
                button: { text: 'Použiť tento obrázok' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.id);
                preview.html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;" />');
                button.siblings('.remove_obrazok_button').show();
            });

            frame.open();
        });

        $('.remove_obrazok_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var target = $(button.data('target'));
            var preview = $(button.data('preview'));
            target.val('');
            preview.html('');
            button.hide();
        });
    });
    </script>
    <?php
}

/**
 * Enqueue scripts a styles pre admin (vrátane Media Uploadera)
 */
public function enqueue_admin_scripts( $hook ) {
    // Spusti len na edit/new stránke nášho CPT
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
        return;
    }

    global $post;
    if ( 'eventkviz_event' !== $post->post_type ) {
        return;
    }

    // Kľúčové: Načítaj WP Media Library
    wp_enqueue_media();

    // Voliteľne: Ak máš custom admin CSS/JS súbory, enqueue ich tu
    // wp_enqueue_style( 'eventkviz-admin', plugin_dir_url( __FILE__ ) . '../admin/css/admin.css', array(), $this->version );
    // wp_enqueue_script( 'eventkviz-admin', plugin_dir_url( __FILE__ ) . '../admin/js/admin.js', array( 'jquery' ), $this->version, true );
}

/**
 * Automatické vytvorenie stránok pri vytvorení eventu
 */
public function create_event_pages( $post_id, $post, $update ) {
    // Spusti len pre náš CPT a len pri publish (nie draft/autosave)
    if ( $post->post_type !== 'eventkviz_event' || $post->post_status !== 'publish' ) {
        return;
    }

    // Zabráň nekonečnej slučke (lebo wp_insert_post spustí save_post)
    remove_action( 'save_post_eventkviz_event', array( $this, 'create_event_pages' ) );

    // Skontroluj, či pages už existujú (podľa meta v evente – uložíme ID parent page)
    $parent_page_id = get_post_meta( $post_id, '_eventkviz_parent_page_id', true );
    if ( $parent_page_id && get_post( $parent_page_id ) ) {
        // Pages už existujú – nič nerob
        add_action( 'save_post_eventkviz_event', array( $this, 'create_event_pages' ), 10, 3 );
        return;
    }

    // Hlavná (parent) stránka
    $parent_page = array(
        'post_title'   => $post->post_title . ' Event', // napr. "Môj Event Event" – uprav podľa potreby
        'post_name'    => $post->post_name, // rovnaký slug ako event
        'post_content' => 'Táto stránka je zámerne prázdna.', 
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => get_current_user_id(),
    );

    $parent_id = wp_insert_post( $parent_page );

    if ( $parent_id && ! is_wp_error( $parent_id ) ) {
        // Ulož ID parent page do meta eventu (aby sa to nespustilo znova)
        update_post_meta( $post_id, '_eventkviz_parent_page_id', $parent_id );

        // 3 sub-stránky (child pages)
        $sub_pages = array(
            array(
                'title'   => 'Všetky linky',
                'slug'    => $post->post_name . '-all-team-links',
                'content' => '[show_team_links akcia="' . $post->post_name . '"]', 
            ),
            array(
                'title'   => 'Statistika',
                'slug'    => $post->post_name . '-statistika',
                'content' => '[statistika akcia="' . $post->post_name . '"]', 
            ),
            
        );

        foreach ( $sub_pages as $sub ) {
            $sub_page = array(
                'post_title'   => $sub['title'],
                'post_name'    => $sub['slug'],
                'post_content' => $sub['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_parent'  => $parent_id, // child of parent
                'post_author'  => get_current_user_id(),
            );

            wp_insert_post( $sub_page );
        }
    }

    // Obnov hook
    add_action( 'save_post_eventkviz_event', array( $this, 'create_event_pages' ), 10, 3 );
}
public function delete_event_pages( $post_id ) {
    if ( get_post_type( $post_id ) !== 'eventkviz_event' ) {
        return;
    }

    error_log( 'EventKviz DELETE: Spustené vymazávanie pre event ID ' . $post_id );

    $parent_page_id = get_post_meta( $post_id, '_eventkviz_parent_page_id', true );

    error_log( 'EventKviz DELETE: Načítaný parent page ID: ' . ( $parent_page_id ? $parent_page_id : 'ŽIADNY' ) );

    if ( $parent_page_id && get_post( $parent_page_id ) ) {
        // Najprv vymaž všetky child pages (rekurzívne)
        $children = get_children( array(
            'post_parent' => $parent_page_id,
            'post_type'   => 'page',
            'numberposts' => -1,
            'post_status' => 'any'
        ) );

        if ( $children ) {
            foreach ( $children as $child ) {
                error_log( 'EventKviz DELETE: Mažem child page ID ' . $child->ID );
                wp_delete_post( $child->ID, true ); // force delete
            }
        }

        // Potom vymaž parent page
        error_log( 'EventKviz DELETE: Mažem parent page ID ' . $parent_page_id );
        wp_delete_post( $parent_page_id, true );

        error_log( 'EventKviz DELETE: Všetky pages vymazané' );

        delete_post_meta( $post_id, '_eventkviz_parent_page_id' );
    } else {
        error_log( 'EventKviz DELETE: Žiadny parent page na vymazanie' );
    }
}
}