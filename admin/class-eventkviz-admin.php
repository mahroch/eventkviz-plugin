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

		// Per-event landing pages are no longer auto-created (since 1.4.0).
		// New events use global hub pages (/eventkviz-vstup/?akcia=X, /eventkviz-statistika/?akcia=X)
		// created by Eventkviz_Activator. Existing per-event pages still work as-is.
		// To re-enable old behavior: uncomment the line below.
		// add_action( 'save_post_eventkviz_event', array( $this, 'create_event_pages' ), 10, 3 );
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
		wp_enqueue_style( 'eventkviz-admin-tabs', plugin_dir_url( __FILE__ ) . 'css/eventkviz-admin-tabs.css', array(), $this->version );

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

    // Submenu: Mapové šablóny — manuálne (CPT má show_in_menu=false)
    // aby sa zobrazila TU v poradí, nie ako auto-prvá pod parentom.
    add_submenu_page(
        'edit.php?post_type=eventkviz_event',
        __( 'Mapové šablóny', 'eventkviz' ),
        __( 'Mapové šablóny', 'eventkviz' ),
        'manage_options',
        'edit.php?post_type=mapquiz_template'
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
    <div class="eventkviz-metabox-tabs">
        <ul class="eventkviz-tabs-nav">
            <li class="active"><a href="#tab-general">General</a></li>
            <li><a href="#tab-music">Music</a></li>
            <li><a href="#tab-movies">Movies</a></li>
            <li><a href="#tab-knowledge">Knowledge</a></li>
            <li><a href="#tab-sudoku">Sudoku</a></li>
            <li><a href="#tab-mapa">Mapa</a></li>
        </ul>

        <div class="eventkviz-tab-content">
            <?php
            $this->render_general_tab( $post, $meta );
            $this->render_music_tab( $post, $meta );
            $this->render_movies_tab( $post, $meta );
            $this->render_knowledge_tab( $post, $meta );
            $this->render_sudoku_tab( $post, $meta );
            $this->render_mapa_tab( $post, $meta );
            ?>
        </div>
    </div>

    <script>
    jQuery(function($) {
        // Prepínanie tabov
        $('.eventkviz-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');

            $('.eventkviz-tab-content > div').hide().removeClass('active');
            $(target).show().addClass('active');

            $('.eventkviz-tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
        });

        // Otvor General
        $('.eventkviz-tabs-nav li.active a').trigger('click');

        // Conditional show/hide (unique ID pre každý tab)
        // General
        $('#select_from_teams_array_cb_general').on('change', function() {
            $('#select_teams_container_general').toggle($(this).is(':checked'));
        });
        $('#use_seed_cb_general').on('change', function() {
            $('#places_container_general').toggle($(this).is(':checked'));
        });

        // Music
        $('#music_quiz_active_cb').on('change', function() {
            $('#music_fields_container').toggle($(this).is(':checked'));
        });
        $('#poslat_vysledok_mailom_cb_music').on('change', function() {
            $('#admin_mail_container_music').toggle($(this).is(':checked'));
        });
        $('#format_pri_splneni_select_music').on('change', function() {
            var value = $(this).val();
            $('#obrazok_container_music').toggle(value === 'obrazok');
            $('#text_container_music').toggle(value === 'text');
        });

        // Movies
        $('#movies_quiz_active_cb').on('change', function() {
            $('#movies_fields_container').toggle($(this).is(':checked'));
        });
        $('#poslat_vysledok_mailom_cb_movies').on('change', function() {
            $('#admin_mail_container_movies').toggle($(this).is(':checked'));
        });
        $('#format_pri_splneni_select_movies').on('change', function() {
            var value = $(this).val();
            $('#obrazok_container_movies').toggle(value === 'obrazok');
            $('#text_container_movies').toggle(value === 'text');
        });

        // Knowledge
        $('#knowledge_quiz_active_cb').on('change', function() {
            $('#knowledge_fields_container').toggle($(this).is(':checked'));
        });
        $('#poslat_vysledok_mailom_cb_knowledge').on('change', function() {
            $('#admin_mail_container_knowledge').toggle($(this).is(':checked'));
        });
        $('#format_pri_splneni_select_knowledge').on('change', function() {
            var value = $(this).val();
            $('#obrazok_container_knowledge').toggle(value === 'obrazok');
            $('#text_container_knowledge').toggle(value === 'text');
        });

        // Sudoku
        $('#sudoku_quiz_active_cb').on('change', function() {
            $('#sudoku_fields_container').toggle($(this).is(':checked'));
        });
        $('#poslat_vysledok_mailom_cb_sudoku').on('change', function() {
            $('#admin_mail_container_sudoku').toggle($(this).is(':checked'));
        });
        $('#format_pri_splneni_select_sudoku').on('change', function() {
            var value = $(this).val();
            $('#obrazok_container_sudoku').toggle(value === 'obrazok');
            $('#text_container_sudoku').toggle(value === 'text');
        });

        // Initial toggle (po load)
        $('.eventkviz-tabs-nav a').each(function() {
            if ($(this).parent().hasClass('active')) {
                var target = $(this).attr('href');
                $(target + ' input[type="checkbox"], select[id^="format_pri_splneni_select"]').trigger('change');
            }
        });

        // Uploader
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
            'show_link_back_to_all_quizes',
            'geochallenge_integration'
        ];

        foreach ( $bool_keys as $key ) {
            $value = isset( $general_fields[$key] ) && $general_fields[$key] == '1' ? '1' : '0';
            update_post_meta( $post_id, 'event_general_' . $key, $value );
        }

        // Špeciálne JSON array polia (select_teams, places, names_of_places)
        $json_fields = [
            'select_teams_json' => 'event_general_select_teams',
            'places_json' => 'event_general_places',
            'names_of_places_json' => 'event_general_names_of_places',
            'credits_json' => 'event_general_credits' 
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
    // Pozn.: mapa je už multi-quiz (event_mapa_quizzes JSON array) — savne sa nižšie.
    $quiz_types = ['music', 'movies', 'knowledge', 'sudoku'];
    foreach ( $quiz_types as $type ) {
        if ( isset( $_POST['event_' . $type] ) && is_array( $_POST['event_' . $type] ) ) {
            foreach ( $_POST['event_' . $type] as $key => $value ) {
                $sanitized_value = sanitize_text_field( $value );
                update_post_meta( $post_id, 'event_' . $type . '_' . sanitize_key( $key ), $sanitized_value );
            }
        }
    }

    // === MAPA — multi-quiz JSON array ===
    $this->save_mapa_quizzes( $post_id );

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

    if ( isset( $_POST['event_general']['minimal_number_of_correct_seeds'] ) ) {
        update_post_meta( $post_id, 'event_general_minimal_number_of_correct_seeds', (int) $_POST['event_general']['minimal_number_of_correct_seeds'] );
    }

    if ( isset( $_POST['event_general']['final_place_pocet_pokusov'] ) ) {
        update_post_meta( $post_id, 'event_general_final_place_pocet_pokusov', (int) $_POST['event_general']['final_place_pocet_pokusov'] );
    }


    $quiz_checkbox_keys = [
        'music'     => ['music_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede', 'mark_correctness_on_retry', 'new_questions_on_retry'],
        'movies'    => ['movies_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede', 'mark_correctness_on_retry', 'new_questions_on_retry'],
        'knowledge' => ['knowledge_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede', 'mark_correctness_on_retry', 'new_questions_on_retry'],
        'sudoku'    => ['sudoku_quiz_active', 'show_entry_form', 'poslat_vysledok_usera_mailom', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede', 'new_questions_on_retry'],
        // mapa: nepoužíva flat event_mapa_* postmeta, ale event_mapa_quizzes JSON array
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

    if ( isset( $_POST['event_general']['credits_json'] ) ) {
        $json = stripslashes( $_POST['event_general']['credits_json'] );
        $array = json_decode( $json, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $array ) ) {
            update_post_meta( $post_id, 'event_general_credits', $array );
        } else {
            delete_post_meta( $post_id, 'event_general_credits' );
        }
    }

}

	/**
 * Render General tabu
 */
private function render_general_tab( $post, $meta ) {
    ?>
    <div id="tab-general" class="tab-panel">
        <h3>Všeobecné nastavenia eventu</h3>
        <table class="form-table" role="presentation">

            <!-- startup_form -->
            <tr>
                <th><label>Vstupný formulár (user + tím)</label></th>
                <td>
                    <input type="checkbox" name="event_general[startup_form]" value="1" <?php checked( $meta['event_general_startup_form'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Pred kvízom hráč uvidí formulár, kde zadá svoj kód (a tím). Použi keď organizátor rozosiela všetkým rovnaký link a každý sa identifikuje sám.<br>
                        <strong>Vypnuté:</strong> Hráč vstúpi rovno do kvízu cez URL s parametrami <code>?user=XYZ&amp;team=team1</code>. Použi keď generuješ unikátne linky (QR kódy, e-mail kampaň, GeoChallenge prepojenie).
                    </p>
                </td>
            </tr>

            <!-- identifikacia_kodom_usera -->
            <tr>
                <th><label>Identifikácia hráča kódom</label></th>
                <td>
                    <input type="checkbox" name="event_general[identifikacia_kodom_usera]" value="1" <?php checked( $meta['event_general_identifikacia_kodom_usera'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Každý hráč má vlastný kód (napr. <code>marek42</code>) a výsledky sa ukladajú per hráč. Použi pri individuálnych súťažiach.<br>
                        <strong>Vypnuté:</strong> Hráči sa identifikujú len kódom tímu, výsledky sa zlučujú za tím. Použi pri tímových teambuildingoch kde nie je dôležité kto z tímu hral.
                    </p>
                </td>
            </tr>

            <!-- verify_users_in_db -->
            <tr>
                <th><label>Overiť hráča v zozname</label></th>
                <td>
                    <input type="checkbox" name="event_general[verify_users_in_db]" value="1" <?php checked( $meta['event_general_verify_users_in_db'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Plugin pri vstupe overí, či zadaný kód existuje v tabuľke <em>Participants</em>. Cudzie/preklepnuté kódy sa odmietnu. Použi keď chceš obmedziť účasť len na predregistrovaných.<br>
                        <strong>Vypnuté:</strong> Akýkoľvek kód je akceptovaný — záznam sa vytvorí automaticky. Použi pri otvorenom (verejnom) evente.
                    </p>
                </td>
            </tr>

            <!-- identifikacia_userov_timu -->
            <tr>
                <th><label>Hráč zadáva svoj kód aj kód tímu</label></th>
                <td>
                    <input type="checkbox" name="event_general[identifikacia_userov_timu]" value="1" <?php checked( $meta['event_general_identifikacia_userov_timu'][0] ?? '1', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Tím má viacerých hráčov a každý si pri vstupe zadá <em>svoj kód</em> aj <em>kód tímu</em>. Výsledky idú per hráč ale zlučujú sa za tím.<br>
                        <strong>Vypnuté:</strong> Hráč zadáva iba svoj kód — tím sa mu doplní automaticky podľa zoznamu Participants (musí byť v ňom predzapísaný spolu s tímom).
                    </p>
                </td>
            </tr>

            <!-- select_from_teams_array + conditional -->
            <tr>
                <th><label>Výber tímu z preddefinovaného zoznamu</label></th>
                <td>
                    <input type="checkbox" id="select_from_teams_array_cb_general" name="event_general[select_from_teams_array]" value="1" <?php checked( $meta['event_general_select_from_teams_array'][0] ?? '1', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Hráč vyberá tím z dropdownu (zoznam definuješ nižšie). Žiadne preklepy v kóde tímu, žiadne falošné tímy.<br>
                        <strong>Vypnuté:</strong> Hráč napíše kód tímu ručne do textového poľa — flexibilnejšie ale rizikovejšie (preklepy = nový tím v DB).
                    </p>

                    <div id="select_teams_container_general" style="margin-top: 10px; <?php echo ( $meta['event_general_select_from_teams_array'][0] ?? '1' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                        <label><strong>Zoznam tímov:</strong></label><br>
                        <input type="hidden" name="event_general[select_teams_json]" id="select_teams_json_hidden" value="">
                        <table id="select_teams_table" class="widefat" style="max-width:500px; margin-top:5px; border-spacing:0;">
                            <thead><tr><th style="padding:4px 8px;">Kód tímu</th><th style="padding:4px 8px;">Názov tímu</th><th style="width:32px; padding:4px;"></th></tr></thead>
                            <tbody>
                            <?php
                                $teams = get_post_meta( $post->ID, 'event_general_select_teams', true );
                                if ( empty( $teams ) ) {
                                    $teams = array('team1' => 'Team 1', 'team2' => 'Team 2');
                                } else {
                                    unset( $teams[''] );
                                }
                                foreach ( $teams as $key => $label ) :
                            ?>
                                <tr>
                                    <td style="padding:2px 4px;"><input type="text" class="team-key" value="<?php echo esc_attr($key); ?>" style="width:100%; padding:2px 4px;"></td>
                                    <td style="padding:2px 4px;"><input type="text" class="team-label" value="<?php echo esc_attr($label); ?>" style="width:100%; padding:2px 4px;"></td>
                                    <td style="padding:2px 4px;"><button type="button" class="button remove-team-row" style="padding:0 6px; line-height:24px;">&times;</button></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="button" id="add_team_row" style="margin-top:5px;">+ Pridať tím</button>
                        <script>
                        (function(){
                            function syncTeamsJson(){
                                var obj = {"": "Select ..."};
                                document.querySelectorAll('#select_teams_table tbody tr').forEach(function(tr){
                                    var k = tr.querySelector('.team-key').value;
                                    var v = tr.querySelector('.team-label').value;
                                    if(k !== '') obj[k] = v;
                                });
                                document.getElementById('select_teams_json_hidden').value = JSON.stringify(obj);
                            }
                            document.getElementById('add_team_row').addEventListener('click', function(){
                                var tbody = document.querySelector('#select_teams_table tbody');
                                var tr = document.createElement('tr');
                                tr.innerHTML = '<td style="padding:2px 4px;"><input type="text" class="team-key" value="" style="width:100%; padding:2px 4px;"></td>'
                                    + '<td style="padding:2px 4px;"><input type="text" class="team-label" value="" style="width:100%; padding:2px 4px;"></td>'
                                    + '<td style="padding:2px 4px;"><button type="button" class="button remove-team-row" style="padding:0 6px; line-height:24px;">&times;</button></td>';
                                tbody.appendChild(tr);
                                syncTeamsJson();
                            });
                            document.getElementById('select_teams_table').addEventListener('click', function(e){
                                if(e.target.classList.contains('remove-team-row')){
                                    e.target.closest('tr').remove();
                                    syncTeamsJson();
                                }
                            });
                            document.getElementById('select_teams_table').addEventListener('input', syncTeamsJson);
                            syncTeamsJson();
                        })();
                        </script>
                    </div>
                </td>
            </tr>

            <!-- use_seed + conditional -->
            <tr>
                <th><label>Stanovištia s kódmi (seed)</label></th>
                <td>
                    <input type="checkbox" id="use_seed_cb_general" name="event_general[use_seed]" value="1" <?php checked( $meta['event_general_use_seed'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Po splnení každého kvízu dostane hráč/tím „kód stanovišťa" (seed). Po vyzbieraní minimálneho počtu kódov sa otvorí finálna úloha (truhlica). Použi pri viacstanovišťových GeoChallenge eventoch.<br>
                        <strong>Vypnuté:</strong> Žiadne kódy sa nezobrazujú, kvízy sú samostatné bez finálnej zhrnujúcej úlohy.<br>
                        <em>Konfigurácia stanovíšť, názvov a kreditov sa zjaví nižšie po zapnutí.</em>
                    </p>

                    <div id="places_container_general" style="margin-top: 10px; <?php echo ( $meta['event_general_use_seed'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                        <label><strong>Poradie stanovísk (places – JSON indexed array):</strong></label><br>
                        <textarea name="event_general[places_json]" rows="8" class="large-text"><?php 
                            $places = get_post_meta( $post->ID, 'event_general_places', true );
                            echo esc_textarea( json_encode( $places ?: array(
                                array('sudoku', 'Sudoku quiz'),
                                array('movies', 'Movies quiz'),
                                array('music', 'Music quiz'),
                                array('knowledge', 'Knowledge quiz')
                            ), JSON_PRETTY_PRINT ) );
                        ?></textarea><br><br>

                        <label><strong>Názvy stanovísk (names_of_places – JSON objekt):</strong></label><br>
                        <textarea name="event_general[names_of_places_json]" rows="6" class="large-text"><?php 
                            $names = get_post_meta( $post->ID, 'event_general_names_of_places', true );
                            echo esc_textarea( json_encode( $names ?: array(
                                'sudoku' => 'Sudoku quiz',
                                'movies' => 'Movies quiz',
                                'music' => 'Music quiz',
                                'knowledge' => 'Knowledge quiz'
                            ), JSON_PRETTY_PRINT ) );
                        ?></textarea><br><br>

                        <label><strong>Kredity za stanovištia (JSON objekt):</strong></label><br>
                        <textarea name="event_general[credits_json]" rows="12" class="large-text"><?php 
                            $credits = get_post_meta( $post->ID, 'event_general_credits', true );
                            echo esc_textarea( json_encode( $credits ?: array(
                                'horse'          => 10,
                                'racing'         => 20,
                                'stadium'        => 40,
                                'bridge'         => 50,
                                'hotel'          => 30,
                                'danube'         => 60,
                                'final'          => 20,
                                'chest_success'  => 100,
                                'unspecified'    => 30
                            ), JSON_PRETTY_PRINT ) );
                        ?></textarea>
                        <p class="description">Formát: {"key": body, ...} – key je názov stanovišťa (seed), body za splnenie.</p>

                        <div style="margin-top: 20px;">
                            <label><strong>Minimálny počet správnych seedov na otvorenie truhlice:</strong></label><br>
                            <input type="number" name="event_general[minimal_number_of_correct_seeds]" value="<?php echo esc_attr( $meta['event_general_minimal_number_of_correct_seeds'][0] ?? '3' ); ?>" min="0" class="small-text" />
                            <p class="description">Minimálny počet správnych seedov potrebných na otvorenie truhlice.</p>
                        </div>

                        <div style="margin-top: 20px;">
                            <label><strong>Max počet pokusov na otvorenie truhlice:</strong></label><br>
                            <input type="number" name="event_general[final_place_pocet_pokusov]" value="<?php echo esc_attr( $meta['event_general_final_place_pocet_pokusov'][0] ?? '3' ); ?>" min="1" class="small-text" />
                            <p class="description">Maximálny počet pokusov na otvorenie truhlice na finálnom mieste.</p>
                        </div>
                    </div>
                </td>
            </tr>

            <!-- geochallenge_integration -->
            <tr>
                <th><label>GeoChallenge integrácia</label></th>
                <td>
                    <input type="checkbox" name="event_general[geochallenge_integration]" value="1" <?php checked( $meta['event_general_geochallenge_integration'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        Zapnite ak je tento event prepojený s GeoChallenge appkou.<br>
                        Pri splnení kvízu sa hráčovi vygeneruje 5-znakový kód a návratový link späť do GeoChallenge.
                    </p>
                </td>
            </tr>

            <!-- show_link_back_to_all_quizes -->
            <tr>
                <th><label>Tlačidlo „späť na všetky kvízy"</label></th>
                <td>
                    <input type="checkbox" name="event_general[show_link_back_to_all_quizes]" value="1" <?php checked( $meta['event_general_show_link_back_to_all_quizes'][0] ?? '0', '1' ); ?> />
                    <p class="description">
                        <strong>Zapnuté:</strong> Po dokončení kvízu vidí hráč tlačidlo s odkazom späť na zoznam všetkých kvízov v tejto akcii. Užitočné pri viacstanovišťových eventoch s viacerými kvízmi.<br>
                        <strong>Vypnuté:</strong> Žiadne tlačidlo — hráč musí ísť späť cez prehliadač.
                    </p>
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
    $image_id = isset( $meta['event_music_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_music_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_music_format_pri_splneni'][0] ) ? $meta['event_music_format_pri_splneni'][0] : 'obrazok';
    $text_pri_splneni = isset( $meta['event_music_text_pri_splneni_kvizu'][0] ) ? $meta['event_music_text_pri_splneni_kvizu'][0] : '';

    $credits = array(
        'corr_art_corr_pos_corr_song_corr_pos' => isset( $meta['event_music_credits_corr_art_corr_pos_corr_song_corr_pos'][0] ) ? (int) $meta['event_music_credits_corr_art_corr_pos_corr_song_corr_pos'][0] : 100,
        'corr_art_corr_pos_incorr_song'       => isset( $meta['event_music_credits_corr_art_corr_pos_incorr_song'][0] ) ? (int) $meta['event_music_credits_corr_art_corr_pos_incorr_song'][0] : 50,
        'incorr_art_corr_song_corr_pos'       => isset( $meta['event_music_credits_incorr_art_corr_song_corr_pos'][0] ) ? (int) $meta['event_music_credits_incorr_art_corr_song_corr_pos'][0] : 50,
    );

    $music_active = isset( $meta['event_music_music_quiz_active'][0] ) ? $meta['event_music_music_quiz_active'][0] : '1';
    ?>
    <div id="tab-music" class="tab-panel">
        <h3>Music kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <tr>
                <th><label>Aktívny Music kvíz</label></th>
                <td>
                    <input type="checkbox" id="music_quiz_active_cb" name="event_music[music_quiz_active]" value="1" <?php checked( $music_active, '1' ); ?> />
                    <p class="description">true/false - či je Music kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <tbody id="music_fields_container" style="<?php echo $music_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <tr>
                    <th><label>Vstup: zobraziť výber tímu / ísť rovno do otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_music[show_entry_form]" value="1" <?php checked( $meta['event_music_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Ak hráč príde na URL kvízu bez vyplneného tímu (napr. cez všeobecný link <code>/aqljk/?akcia=event</code> bez <code>team=</code>), najprv sa mu zobrazí formulár na výber tímu. Po výbere pokračuje do kvízu. Použi keď chceš poslať jeden zdieľaný link všetkým hráčom a každý si vyberie tím sám.<br>
                            <strong>Vypnuté:</strong> Hráč musí prísť s <code>team=</code> parametrom už v URL (napr. cez predistribuovaný link <code>/aqljk/?akcia=event&amp;team=team1</code>). Žiadny vstupný formulár — ide rovno do otázok. Použi keď máš pre každý tím samostatný link (QR kód, email, hub vstup s preselected tímom).<br>
                            ⚠️ <em>Funguje len v kombinácii s „Výber tímu z preddefinovaného zoznamu" v General tabe. Bez toho sa selector neukáže ani keď je toggle ON.</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní označ správnosť</label></th>
                    <td>
                        <input type="checkbox" name="event_music[mark_correctness_on_retry]" value="1" <?php checked( $meta['event_music_mark_correctness_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po neúspešnom kvíze („Opakovať kvíz") sa formulár predvyplní predošlými odpoveďami a každé pole bude farebne označené — <strong style="color:#26913f">zelené</strong> ak bolo správne, <strong style="color:#a33">červené</strong> ak nesprávne. Hráč rýchlo vidí čo opraviť. Platí pre meno interpreta aj názov piesne.<br>
                            <strong>Vypnuté:</strong> Formulár sa predvyplní predošlými odpoveďami (cez autosave) ale bez farebného označenia.<br>
                            <em>Funkcia sa nepoužije ak je súčasne zapnutá voľba „Pri opakovaní vygeneruj nový set otázok" (otázky by boli iné ako predtým).</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní vygeneruj nový set otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_music[new_questions_on_retry]" value="1" <?php checked( $meta['event_music_new_questions_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Pri každom opakovaní kvízu sa hráčovi vygeneruje <em>nový</em> náhodný set piesní (ak ešte zostávajú pokusy).<br>
                            <strong>Vypnuté (odporúčané):</strong> Pri opakovaní dostane hráč <em>rovnaký</em> set piesní ako prvýkrát — má šancu opraviť konkrétne odpovede.
                        </p>
                    </td>
                </tr>

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
                                        <p class="description">správny umelec, správna pieseň</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Správny umelec, nesprávna pieseň</td>
                                    <td>
                                        <input type="number" name="event_music[credits_corr_art_corr_pos_incorr_song]" value="<?php echo esc_attr( $credits['corr_art_corr_pos_incorr_song'] ); ?>" min="0" class="small-text" />
                                        <p class="description">správny umelec, nesprávna pieseň</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Nesprávny umelec, správna pieseň</td>
                                    <td>
                                        <input type="number" name="event_music[credits_incorr_art_corr_song_corr_pos]" value="<?php echo esc_attr( $credits['incorr_art_corr_song_corr_pos'] ); ?>" min="0" class="small-text" />
                                        <p class="description">nesprávny umelec, správna pieseň</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">Nastavenie bodov za rôzne kombinácie správnosti umelca a piesne.</p>
                    </td>
                </tr>

                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_music[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_music_pocet_otazok_v_sete'][0] ?? '10' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            0/číslo - možnosť zvoliť si koľko otázok v sete dostane používateľ.<br>
                            0 znamená, že sa vyberá podľa žiadneho množstva v production settingu.
                        </p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_music" name="event_music[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_music_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_music" style="margin-top: 10px; <?php echo ( $meta['event_music_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_music[admin_mail]" value="<?php echo esc_attr( $meta['event_music_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktučný výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th><label>Odhaliť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_music[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_music_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej otázke vidí <em>aký bol správny interpret a pieseň</em> (aj pri tých, ktoré nevyplnil alebo nesprávne uhádol).<br>
                            <strong>Vypnuté:</strong> Správne riešenie sa neukáže — namiesto neho sa zobrazí informačná hláška že správne odpovede sú zámerne skryté. Hodí sa keď chceš dať hráčovi šancu opakovať kvíz a riešenia musí prísť na to sám.<br>
                            <em>Nezávislé od toggle nižšie</em> — kontroluje len zobrazenie správneho riešenia.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Hodnotenie hráčových odpovedí (správne/nesprávne + body)</label></th>
                    <td>
                        <input type="checkbox" name="event_music[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_music_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej <em>svojej</em> odpovedi vidí či bola správna/nesprávna a koľko bodov za ňu dostal (napr. „Spevák/skupina boli určené správne, +100 bodov").<br>
                            <strong>Vypnuté:</strong> Hráč vidí len celkové bodové hodnotenie kvízu, bez per-otázku feedback-u.<br>
                            <em>Nezávislé od toggle vyššie</em> — kontroluje len feedback k hráčovým odpovediam.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_music[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_music_pocet_pokusov'][0] ?? '10' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_music" name="event_music[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu (po dosiahnutí minimálnych bodov).</p>

                        <div id="obrazok_container_music" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_music[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_music" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_music" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_music" data-preview="#obrazok_preview_music">
                                Vybrať obrázok
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_music" data-preview="#obrazok_preview_music" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                Odstrániť obrázok
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

                        <div id="text_container_music" style="margin-top: 15px; <?php echo $format_pri_splneni === 'text' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Text pri splnení kvízu:</strong></label><br>
                            <textarea name="event_music[text_pri_splneni_kvizu]" rows="6" class="large-text"><?php echo esc_textarea( $text_pri_splneni ); ?></textarea>
                            <p class="description">Custom text, ktorý sa zobrazí po splnení kvízu (podporuje HTML).</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Render Movies tabu
 */
private function render_movies_tab( $post, $meta ) {
    $image_id = isset( $meta['event_movies_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_movies_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_movies_format_pri_splneni'][0] ) ? $meta['event_movies_format_pri_splneni'][0] : 'obrazok';
    $text_pri_splneni = isset( $meta['event_movies_text_pri_splneni_kvizu'][0] ) ? $meta['event_movies_text_pri_splneni_kvizu'][0] : '';

    $credits_corr_movie = isset( $meta['event_movies_credits_corr_movie'][0] ) ? (int) $meta['event_movies_credits_corr_movie'][0] : 100;

    $movies_active = isset( $meta['event_movies_movies_quiz_active'][0] ) ? $meta['event_movies_movies_quiz_active'][0] : '1';
    ?>
    <div id="tab-movies" class="tab-panel">
        <h3>Movies kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <tr>
                <th><label>Aktívny Movies kvíz</label></th>
                <td>
                    <input type="checkbox" id="movies_quiz_active_cb" name="event_movies[movies_quiz_active]" value="1" <?php checked( $movies_active, '1' ); ?> />
                    <p class="description">true/false - či je Movies kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <tbody id="movies_fields_container" style="<?php echo $movies_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <!-- show_entry_form -->
                <tr>
                    <th><label>Vstup: zobraziť výber tímu / ísť rovno do otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[show_entry_form]" value="1" <?php checked( $meta['event_movies_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Ak hráč príde na URL kvízu bez vyplneného tímu (napr. cez všeobecný link <code>/merdfghh/?akcia=event</code> bez <code>team=</code>), najprv sa mu zobrazí formulár na výber tímu. Po výbere pokračuje do kvízu. Použi keď chceš poslať jeden zdieľaný link všetkým hráčom a každý si vyberie tím sám.<br>
                            <strong>Vypnuté:</strong> Hráč musí prísť s <code>team=</code> parametrom už v URL (napr. cez predistribuovaný link <code>/merdfghh/?akcia=event&amp;team=team1</code>). Žiadny vstupný formulár — ide rovno do otázok. Použi keď máš pre každý tím samostatný link (QR kód, email, hub vstup s preselected tímom).<br>
                            ⚠️ <em>Funguje len v kombinácii s „Výber tímu z preddefinovaného zoznamu" v General tabe. Bez toho sa selector neukáže ani keď je toggle ON.</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní označ správnosť</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[mark_correctness_on_retry]" value="1" <?php checked( $meta['event_movies_mark_correctness_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po neúspešnom kvíze sa formulár predvyplní predošlými odpoveďami a každé pole bude farebne označené — <strong style="color:#26913f">zelené</strong> ak bolo správne, <strong style="color:#a33">červené</strong> ak nesprávne.<br>
                            <strong>Vypnuté:</strong> Formulár sa predvyplní (cez autosave) ale bez farebného označenia.<br>
                            <em>Funkcia sa nepoužije ak je súčasne zapnutá voľba „Pri opakovaní vygeneruj nový set otázok".</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní vygeneruj nový set otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[new_questions_on_retry]" value="1" <?php checked( $meta['event_movies_new_questions_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Pri každom opakovaní kvízu sa hráčovi vygeneruje <em>nový</em> náhodný set filmov (ak ešte zostávajú pokusy).<br>
                            <strong>Vypnuté (odporúčané):</strong> Pri opakovaní dostane hráč <em>rovnaký</em> set filmov ako prvýkrát.
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

                <!-- Bodovanie -->
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
                        <p class="description">Celkový počet otázok v kvíze. 0 = podľa nastavenia produkcie nižšie.</p>
                    </td>
                </tr>

                <!-- Počet otázok podľa produkcie (dynamicky z taxonomie) -->
                <tr>
                    <th><label>Počet otázok podľa produkcie</label></th>
                    <td>
                        <table class="widefat fixed" style="width: auto;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 300px;">Produkcia</th>
                                    <th style="text-align: left;">Počet otázok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $production_terms = get_terms( array( 'taxonomy' => 'production', 'hide_empty' => false ) );
                                if ( ! is_wp_error( $production_terms ) && ! empty( $production_terms ) ) :
                                    foreach ( $production_terms as $term ) :
                                        $meta_key = 'event_movies_number_question_in_production_' . $term->slug;
                                        $saved_value = $meta[ $meta_key ][0] ?? '0';
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $term->name ); ?></td>
                                    <td>
                                        <input type="number" name="event_movies[number_question_in_production_<?php echo esc_attr( $term->slug ); ?>]" value="<?php echo esc_attr( $saved_value ); ?>" min="0" class="small-text" />
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </tbody>
                        </table>
                        <p class="description">Zadaj počet otázok pre každú produkciu. Ak sú všetky 0, vyberú sa náhodné filmy zo všetkých (podľa "Počet otázok v sete" vyššie).</p>
                    </td>
                </tr>


                <!-- poslat_vysledok_usera_mailom + conditional -->
                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_movies" name="event_movies[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_movies_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_movies" style="margin-top: 10px; <?php echo ( $meta['event_movies_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_movies[admin_mail]" value="<?php echo esc_attr( $meta['event_movies_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktučný výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <!-- zobraz_spravne_odpovede -->
                <tr>
                    <th><label>Odhaliť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_movies_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej otázke vidí <em>ktorý film bol správny</em> (aj pri tých, ktoré nevyplnil alebo zle uhádol).<br>
                            <strong>Vypnuté:</strong> Správne riešenie sa neukáže — namiesto neho sa zobrazí informačná hláška že správne odpovede sú zámerne skryté. Hodí sa keď chceš dať hráčovi šancu opakovať kvíz a riešenia musí prísť na to sám.<br>
                            <em>Nezávislé od toggle nižšie</em> — kontroluje len zobrazenie správneho riešenia.
                        </p>
                    </td>
                </tr>

                <!-- zobraz_spravne_uhadnute_odpovede -->
                <tr>
                    <th><label>Hodnotenie hráčových odpovedí (správne/nesprávne + body)</label></th>
                    <td>
                        <input type="checkbox" name="event_movies[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_movies_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej <em>svojej</em> odpovedi vidí či bola správna/nesprávna a koľko bodov za ňu dostal (napr. „Film určený správne, +100 bodov").<br>
                            <strong>Vypnuté:</strong> Hráč vidí len celkové bodové hodnotenie kvízu, bez per-otázku feedback-u.<br>
                            <em>Nezávislé od toggle vyššie</em> — kontroluje len feedback k hráčovým odpovediam.
                        </p>
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

                        <div id="obrazok_container_movies" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_movies[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_movies" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_movies" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_movies" data-preview="#obrazok_preview_movies">
                                Vybrať obrázok
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_movies" data-preview="#obrazok_preview_movies" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                Odstrániť obrázok
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

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
    <?php
}

/**
 * Render Knowledge tabu
 */
private function render_knowledge_tab( $post, $meta ) {
    $image_id = isset( $meta['event_knowledge_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_knowledge_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_knowledge_format_pri_splneni'][0] ) ? $meta['event_knowledge_format_pri_splneni'][0] : 'obrazok';
    $text_pri_splneni = isset( $meta['event_knowledge_text_pri_splneni_kvizu'][0] ) ? $meta['event_knowledge_text_pri_splneni_kvizu'][0] : '';

    $credits_corr_answer = isset( $meta['event_knowledge_credits_corr_answer'][0] ) ? (int) $meta['event_knowledge_credits_corr_answer'][0] : 100;

    // Dynamické topics z taxonomie 'topic'
    $topics = get_terms( array(
        'taxonomy'   => 'topic',
        'hide_empty' => false,
    ) );

    $knowledge_active = isset( $meta['event_knowledge_knowledge_quiz_active'][0] ) ? $meta['event_knowledge_knowledge_quiz_active'][0] : '1';
    ?>
    <div id="tab-knowledge" class="tab-panel">
        <h3>Knowledge kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <tr>
                <th><label>Aktívny Knowledge kvíz</label></th>
                <td>
                    <input type="checkbox" id="knowledge_quiz_active_cb" name="event_knowledge[knowledge_quiz_active]" value="1" <?php checked( $knowledge_active, '1' ); ?> />
                    <p class="description">true/false - či je Knowledge kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <tbody id="knowledge_fields_container" style="<?php echo $knowledge_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <tr>
                    <th><label>Vstup: zobraziť výber tímu / ísť rovno do otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[show_entry_form]" value="1" <?php checked( $meta['event_knowledge_show_entry_form'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Ak hráč príde na URL kvízu bez vyplneného tímu (napr. cez všeobecný link <code>/kwersdfzx/?akcia=event</code> bez <code>team=</code>), najprv sa mu zobrazí formulár na výber tímu. Po výbere pokračuje do kvízu. Použi keď chceš poslať jeden zdieľaný link všetkým hráčom a každý si vyberie tím sám.<br>
                            <strong>Vypnuté:</strong> Hráč musí prísť s <code>team=</code> parametrom už v URL (napr. cez predistribuovaný link <code>/kwersdfzx/?akcia=event&amp;team=team1</code>). Žiadny vstupný formulár — ide rovno do otázok. Použi keď máš pre každý tím samostatný link (QR kód, email, hub vstup s preselected tímom).<br>
                            ⚠️ <em>Funguje len v kombinácii s „Výber tímu z preddefinovaného zoznamu" v General tabe. Bez toho sa selector neukáže ani keď je toggle ON.</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní označ správnosť</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[mark_correctness_on_retry]" value="1" <?php checked( $meta['event_knowledge_mark_correctness_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po neúspešnom kvíze sa formulár predvyplní predošlými odpoveďami a každé pole bude farebne označené — <strong style="color:#26913f">zelené</strong> ak bolo správne, <strong style="color:#a33">červené</strong> ak nesprávne. Funguje pre voľne písané odpovede aj výberové dropdowny.<br>
                            <strong>Vypnuté:</strong> Formulár sa predvyplní (cez autosave) ale bez farebného označenia.<br>
                            <em>Funkcia sa nepoužije ak je súčasne zapnutá voľba „Pri opakovaní vygeneruj nový set otázok".</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní vygeneruj nový set otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[new_questions_on_retry]" value="1" <?php checked( $meta['event_knowledge_new_questions_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Pri každom opakovaní kvízu sa hráčovi vygeneruje <em>nový</em> náhodný set otázok (ak ešte zostávajú pokusy).<br>
                            <strong>Vypnuté (odporúčané):</strong> Pri opakovaní dostane hráč <em>rovnaký</em> set otázok ako prvýkrát.
                        </p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_knowledge[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_knowledge_pocet_otazok_v_sete'][0] ?? '0' ); ?>" min="0" class="small-text" />
                        <p class="description">
                            0/číslo - možnosť zvoliť si koľko otázok v sete dostane používateľ.<br>
                            0 znamená, že sa vyberá podľa žiadneho množstva v topic settingu.
                        </p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Odhaliť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_knowledge_zobraz_spravne_odpovede'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej otázke vidí <em>správnu odpoveď</em> (aj pri tých, ktoré nevyplnil alebo zle).<br>
                            <strong>Vypnuté:</strong> Správne riešenie sa neukáže — namiesto neho sa zobrazí informačná hláška že správne odpovede sú zámerne skryté. Hodí sa keď chceš dať hráčovi šancu opakovať kvíz a riešenia musí prísť na to sám.<br>
                            <em>Nezávislé od toggle nižšie</em> — kontroluje len zobrazenie správneho riešenia.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Hodnotenie hráčových odpovedí (správne/nesprávne + body)</label></th>
                    <td>
                        <input type="checkbox" name="event_knowledge[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_knowledge_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč pri každej <em>svojej</em> odpovedi vidí či bola správna/nesprávna a koľko bodov za ňu dostal.<br>
                            <strong>Vypnuté:</strong> Hráč vidí len celkové bodové hodnotenie kvízu, bez per-otázku feedback-u.<br>
                            <em>Nezávislé od toggle vyššie</em> — kontroluje len feedback k hráčovým odpovediam.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_knowledge[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_knowledge_pocet_pokusov'][0] ?? '10' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_knowledge" name="event_knowledge[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu (po dosiahnutí minimálnych bodov).</p>

                        <div id="obrazok_container_knowledge" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_knowledge[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_knowledge" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_knowledge" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_knowledge" data-preview="#obrazok_preview_knowledge">
                                Vybrať obrázok
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_knowledge" data-preview="#obrazok_preview_knowledge" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                Odstrániť obrázok
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

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
    <?php
}

/**
 * Render Sudoku tabu
 */
private function render_sudoku_tab( $post, $meta ) {
    $image_id = isset( $meta['event_sudoku_obrazok_pri_splneni_kvizu'][0] ) ? (int) $meta['event_sudoku_obrazok_pri_splneni_kvizu'][0] : 0;
    $image_src = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $format_pri_splneni = isset( $meta['event_sudoku_format_pri_splneni'][0] ) ? $meta['event_sudoku_format_pri_splneni'][0] : 'obrazok';
    $text_pri_splneni = isset( $meta['event_sudoku_text_pri_splneni_kvizu'][0] ) ? $meta['event_sudoku_text_pri_splneni_kvizu'][0] : '';

    $credits = array(
        'easy'   => isset( $meta['event_sudoku_credits_easy'][0] ) ? (int) $meta['event_sudoku_credits_easy'][0] : 10,
        'medium' => isset( $meta['event_sudoku_credits_medium'][0] ) ? (int) $meta['event_sudoku_credits_medium'][0] : 20,
        'hard'   => isset( $meta['event_sudoku_credits_hard'][0] ) ? (int) $meta['event_sudoku_credits_hard'][0] : 35,
    );

    $sudoku_active = isset( $meta['event_sudoku_sudoku_quiz_active'][0] ) ? $meta['event_sudoku_sudoku_quiz_active'][0] : '0';
    ?>
    <div id="tab-sudoku" class="tab-panel">
        <h3>Sudoku kvíz nastavenia</h3>
        <table class="form-table" role="presentation">

            <tr>
                <th><label>Aktívny Sudoku kvíz</label></th>
                <td>
                    <input type="checkbox" id="sudoku_quiz_active_cb" name="event_sudoku[sudoku_quiz_active]" value="1" <?php checked( $sudoku_active, '1' ); ?> />
                    <p class="description">true/false - či je Sudoku kvíz aktívny pre tento event</p>
                </td>
            </tr>

            <tbody id="sudoku_fields_container" style="<?php echo $sudoku_active === '1' ? 'display: table-row-group;' : 'display: none;'; ?>">
                <tr>
                    <th><label>Vstup: zobraziť výber tímu / ísť rovno do otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[show_entry_form]" value="1" <?php checked( $meta['event_sudoku_show_entry_form'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Ak hráč príde na URL kvízu bez vyplneného tímu (napr. cez všeobecný link <code>/sweertydfd/?akcia=event</code> bez <code>team=</code>), najprv sa mu zobrazí formulár na výber tímu. Po výbere pokračuje do kvízu. Použi keď chceš poslať jeden zdieľaný link všetkým hráčom a každý si vyberie tím sám.<br>
                            <strong>Vypnuté:</strong> Hráč musí prísť s <code>team=</code> parametrom už v URL (napr. cez predistribuovaný link <code>/sweertydfd/?akcia=event&amp;team=team1</code>). Žiadny vstupný formulár — ide rovno do otázok. Použi keď máš pre každý tím samostatný link (QR kód, email, hub vstup s preselected tímom).<br>
                            ⚠️ <em>Funguje len v kombinácii s „Výber tímu z preddefinovaného zoznamu" v General tabe. Bez toho sa selector neukáže ani keď je toggle ON.</em>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Pri opakovaní vygeneruj nový set otázok</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[new_questions_on_retry]" value="1" <?php checked( $meta['event_sudoku_new_questions_on_retry'][0] ?? '0', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Pri každom opakovaní kvízu sa hráčovi vygeneruje <em>nové</em> sudoku (ak ešte zostávajú pokusy).<br>
                            <strong>Vypnuté (odporúčané):</strong> Pri opakovaní dostane hráč <em>rovnaké</em> sudoku ako prvýkrát.
                        </p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Počet otázok v sete</label></th>
                    <td>
                        <input type="number" name="event_sudoku[pocet_otazok_v_sete]" value="<?php echo esc_attr( $meta['event_sudoku_pocet_otazok_v_sete'][0] ?? '1' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť zvoliť si koľko otázok v sete dostane používateľ</p>
                    </td>
                </tr>

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

                <tr>
                    <th><label>Poslať výsledok mailom</label></th>
                    <td>
                        <input type="checkbox" id="poslat_vysledok_mailom_cb_sudoku" name="event_sudoku[poslat_vysledok_usera_mailom]" value="1" <?php checked( $meta['event_sudoku_poslat_vysledok_usera_mailom'][0] ?? '0', '1' ); ?> />
                        <p class="description">true/false - možnosť poslať aktuálny výsledok používateľa po odoslaní na zadaný email</p>

                        <div id="admin_mail_container_sudoku" style="margin-top: 10px; <?php echo ( $meta['event_sudoku_poslat_vysledok_usera_mailom'][0] ?? '0' ) === '1' ? 'display: block;' : 'display: none;'; ?>">
                            <label><strong>Admin email pre výsledky:</strong></label><br>
                            <input type="email" name="event_sudoku[admin_mail]" value="<?php echo esc_attr( $meta['event_sudoku_admin_mail'][0] ?? 'mahroch@gmail.com' ); ?>" class="regular-text" />
                            <p class="description">možnosť poslať aktučný výsledok používateľa po odoslaní na zadaný email</p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th><label>Odhaliť správne odpovede</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[zobraz_spravne_odpovede]" value="1" <?php checked( $meta['event_sudoku_zobraz_spravne_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč vidí <em>správne riešenie sudoku</em> (vrátane obrázku ak je nahraný).<br>
                            <strong>Vypnuté:</strong> Riešenie sa neukáže — namiesto neho sa zobrazí informačná hláška že správne odpovede sú zámerne skryté.<br>
                            <em>Nezávislé od toggle nižšie</em>.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Hodnotenie hráčových odpovedí (správne/nesprávne + body)</label></th>
                    <td>
                        <input type="checkbox" name="event_sudoku[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( $meta['event_sudoku_zobraz_spravne_uhadnute_odpovede'][0] ?? '1', '1' ); ?> />
                        <p class="description">
                            <strong>Zapnuté:</strong> Po odoslaní hráč vidí pri každej svojej odpovedi či bola správna a koľko bodov dostal.<br>
                            <strong>Vypnuté:</strong> Hráč vidí len celkové bodové hodnotenie kvízu, bez per-otázku feedback-u.<br>
                            <em>Nezávislé od toggle vyššie</em>.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label>Počet pokusov</label></th>
                    <td>
                        <input type="number" name="event_sudoku[pocet_pokusov]" value="<?php echo esc_attr( $meta['event_sudoku_pocet_pokusov'][0] ?? '1' ); ?>" min="1" class="small-text" />
                        <p class="description">možnosť rozhodnúť sa či môže používateľ absolvovať kvíz len raz, alebo viackrát (dá sa určiť počet koľkokrát)</p>
                    </td>
                </tr>

                <tr>
                    <th><label>Formát pri splnení kvízu</label></th>
                    <td>
                        <select id="format_pri_splneni_select_sudoku" name="event_sudoku[format_pri_splneni]">
                            <option value="obrazok" <?php selected( $format_pri_splneni, 'obrazok' ); ?>>Obrázok</option>
                            <option value="text" <?php selected( $format_pri_splneni, 'text' ); ?>>Text</option>
                        </select>
                        <p class="description">Vyberte, čo sa zobrazí používateľovi po splnení kvízu.</p>

                        <div id="obrazok_container_sudoku" style="margin-top: 15px; <?php echo $format_pri_splneni === 'obrazok' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="hidden" name="event_sudoku[obrazok_pri_splneni_kvizu]" id="obrazok_pri_splneni_kvizu_id_sudoku" value="<?php echo esc_attr( $image_id ); ?>" />
                            <div id="obrazok_preview_sudoku" style="margin-bottom: 10px;">
                                <?php if ( $image_src ) : ?>
                                    <img src="<?php echo esc_url( $image_src ); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button upload_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_sudoku" data-preview="#obrazok_preview_sudoku">
                                Vybrať obrázok
                            </button>
                            <button type="button" class="button remove_obrazok_button" data-target="#obrazok_pri_splneni_kvizu_id_sudoku" data-preview="#obrazok_preview_sudoku" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>
                                Odstrániť obrázok
                            </button>
                            <p class="description">ID obrázku z media library, ktorý sa zobrazí po splnení kvízu</p>
                        </div>

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

/**
 * Render Mapa tabu (per-event nastavenia mapového kvízu).
 * Spája event s globálnym mapquiz_template + per-event override-y.
 */
private function render_mapa_tab( $post, $meta ) {
    // Multi-mapa: každý event môže mať N mapových kvízov. Storage v postmeta
    // event_mapa_quizzes (JSON array). Bez backwards compat (user povolil
    // clean rewrite, žiadne live mapa kvízy).
    $quizzes_json = $meta['event_mapa_quizzes'][0] ?? '';
    $quizzes = is_string( $quizzes_json ) && $quizzes_json !== '' ? json_decode( $quizzes_json, true ) : array();
    if ( ! is_array( $quizzes ) ) $quizzes = array();

    $templates = get_posts( array(
        'post_type'      => 'mapquiz_template',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );

    ?>
    <div id="tab-mapa" class="tab-panel">
        <h3>Mapové kvízy</h3>
        <p class="description">
            Pre tento event môžeš pridať <strong>viacero mapových kvízov</strong> (napr. „Hrady SR" + „Rieky" + „Pohoria"). Každý sub-kvíz má vlastný hub link a vlastné nastavenia.
        </p>

        <?php if ( empty( $templates ) ) : ?>
            <p style="color:#a00"><strong>Žiadne mapové šablóny zatiaľ neexistujú.</strong> Vytvor aspoň jednu v <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mapquiz_template' ) ); ?>">EventKviz → Mapové šablóny</a>.</p>
        <?php else : ?>

        <div id="ek-mapa-quizzes-list">
            <?php foreach ( $quizzes as $idx => $q ) : ?>
                <?php $this->render_mapa_subquiz_fieldset( $q, $idx, $templates ); ?>
            <?php endforeach; ?>
        </div>

        <p>
            <button type="button" class="button button-secondary" id="ek-mapa-add-btn">➕ Pridať mapový kvíz</button>
        </p>

        <template id="ek-mapa-quiz-tpl"><?php $this->render_mapa_subquiz_fieldset( array(), '__INDEX__', $templates ); ?></template>

        <script>
        jQuery(function($) {
            var nextIdx = <?php echo count( $quizzes ); ?>;

            // Score tiers visual editor — per fieldset
            function renderTiers($fs) {
                var raw = $fs.find('.ek-mapa-tiers-json').val();
                var tiers = [];
                try { tiers = JSON.parse(raw) || []; } catch (e) {}
                if (!Array.isArray(tiers)) tiers = [];
                var $body = $fs.find('.ek-mapa-tiers-tbody').empty();
                var $msg = $fs.find('.ek-mapa-tiers-empty');
                if (tiers.length === 0) { $msg.show(); return; }
                $msg.hide();
                tiers.forEach(function(t, idx) {
                    var km = parseFloat(t.maxKm) || 0;
                    var pct = parseFloat(t.percent) || 0;
                    var $tr = $('<tr></tr>').attr('data-idx', idx);
                    $tr.append('<td>do <input type="number" class="ek-mapa-tier-km small-text" value="' + km + '" min="0" step="0.5" /> km</td>');
                    $tr.append('<td><input type="number" class="ek-mapa-tier-pct small-text" value="' + pct + '" min="0" max="100" step="1" /> %</td>');
                    $tr.append('<td><button type="button" class="button-link-delete ek-mapa-tier-del" title="Vymazať stupeň">✕</button></td>');
                    $body.append($tr);
                });
            }
            function persistTiers($fs) {
                var tiers = [];
                $fs.find('.ek-mapa-tiers-tbody tr').each(function(){
                    var km = parseFloat($(this).find('.ek-mapa-tier-km').val());
                    var pct = parseFloat($(this).find('.ek-mapa-tier-pct').val());
                    if (!isNaN(km) && !isNaN(pct)) tiers.push({ maxKm: km, percent: pct });
                });
                tiers.sort(function(a,b){ return a.maxKm - b.maxKm; });
                $fs.find('.ek-mapa-tiers-json').val(tiers.length ? JSON.stringify(tiers) : '');
            }
            // Initial render — len existing fieldsets (template <template> sa neparsuje)
            $('#ek-mapa-quizzes-list .ek-mapa-quiz-fieldset').each(function(){ renderTiers($(this)); });

            $('#ek-mapa-add-btn').on('click', function() {
                var tpl = $('#ek-mapa-quiz-tpl').html().replace(/__INDEX__/g, nextIdx);
                var $appended = $(tpl).appendTo('#ek-mapa-quizzes-list');
                renderTiers($appended);  // initial empty render
                nextIdx++;
            });
            $(document).on('click', '.ek-mapa-quiz-remove', function(e) {
                e.preventDefault();
                if (!confirm('Naozaj vymazať tento mapový kvíz a všetky jeho nastavenia? Túto akciu nebude možné vrátiť.')) return;
                $(this).closest('.ek-mapa-quiz-fieldset').remove();
            });
            $(document).on('change', '.ek-mapa-email-toggle input', function() {
                $(this).closest('.ek-mapa-email-row').find('.ek-mapa-email-input').toggle($(this).is(':checked'));
            });
            $(document).on('input', '.ek-mapa-quiz-label', function() {
                var $fs = $(this).closest('.ek-mapa-quiz-fieldset');
                $fs.find('.ek-mapa-quiz-label-preview').text($(this).val() ? '— ' + $(this).val() : '');
            });
            $(document).on('change', '.ek-mapa-quiz-template-select', function() {
                var $fs = $(this).closest('.ek-mapa-quiz-fieldset');
                var $labelInput = $fs.find('.ek-mapa-quiz-label');
                if (!$labelInput.val()) {
                    var title = $(this).find('option:selected').data('title') || '';
                    if (title) $labelInput.val(title).trigger('input');
                }
            });

            // Tier editor handlers (event delegation aby pokryli aj dynamicky pridané)
            $(document).on('click', '.ek-mapa-tier-add', function() {
                var $fs = $(this).closest('.ek-mapa-quiz-fieldset');
                var raw = $fs.find('.ek-mapa-tiers-json').val();
                var tiers = [];
                try { tiers = JSON.parse(raw) || []; } catch (e) {}
                if (!Array.isArray(tiers)) tiers = [];
                var next;
                if (tiers.length === 0) {
                    next = { maxKm: 5, percent: 100 };  // sane default pre prvý
                } else {
                    var last = tiers[tiers.length - 1];
                    next = {
                        maxKm: (parseFloat(last.maxKm) || 0) + 10,
                        percent: Math.max(0, (parseFloat(last.percent) || 100) - 25)
                    };
                }
                tiers.push(next);
                $fs.find('.ek-mapa-tiers-json').val(JSON.stringify(tiers));
                renderTiers($fs);
            });
            $(document).on('input', '.ek-mapa-tier-km, .ek-mapa-tier-pct', function() {
                persistTiers($(this).closest('.ek-mapa-quiz-fieldset'));
            });
            $(document).on('click', '.ek-mapa-tier-del', function() {
                var $fs = $(this).closest('.ek-mapa-quiz-fieldset');
                var idx = $(this).closest('tr').data('idx');
                var raw = $fs.find('.ek-mapa-tiers-json').val();
                var tiers = [];
                try { tiers = JSON.parse(raw) || []; } catch (e) {}
                tiers.splice(idx, 1);
                $fs.find('.ek-mapa-tiers-json').val(tiers.length ? JSON.stringify(tiers) : '');
                renderTiers($fs);
            });
        });
        </script>

        <style>
            .ek-mapa-quiz-fieldset { background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:14px 18px; margin:12px 0; }
            .ek-mapa-quiz-fieldset legend { font-weight:600; padding:0 8px; font-size:15px; }
            .ek-mapa-quiz-label-preview { color:#666; font-weight:400; font-style:italic; }
            .ek-mapa-quiz-fieldset .form-table th { padding:10px 10px 10px 0; width:240px; }
            .ek-mapa-quiz-fieldset .form-table td { padding:8px 10px; }
            .ek-mapa-quiz-remove { color:#d63638 !important; margin-top:8px !important; }
        </style>

        <?php endif; ?>
    </div>
    <?php
}

/**
 * Renderuje jeden sub-kvíz fieldset (admin repeater item).
 *
 * @param array $q       Sub-kvíz data (alebo prázdne pre nový).
 * @param mixed $idx     Index v poli; pre <template> placeholder se používa string '__INDEX__'.
 * @param array $templates Pole mapquiz_template post objektov.
 */
private function render_mapa_subquiz_fieldset( $q, $idx, $templates ) {
    $name_prefix = 'event_mapa_quizzes[' . $idx . ']';
    $label       = $q['label'] ?? '';
    $template_id = (int) ( $q['template_id'] ?? 0 );
    ?>
    <fieldset class="ek-mapa-quiz-fieldset">
        <legend>Mapový kvíz <span class="ek-mapa-quiz-label-preview"><?php echo $label ? '— ' . esc_html( $label ) : ''; ?></span></legend>

        <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[slug]" value="<?php echo esc_attr( $q['slug'] ?? '' ); ?>" />

        <table class="form-table" role="presentation">
            <tr>
                <th><label>Názov pre hráča</label></th>
                <td>
                    <input type="text" class="regular-text ek-mapa-quiz-label" name="<?php echo esc_attr( $name_prefix ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" placeholder="napr. Slovenské hrady" />
                    <p class="description">Zobrazí sa hráčovi v hub karte. Ak prázdne, predvyplní sa z názvu šablóny.</p>
                </td>
            </tr>

            <tr>
                <th><label>Šablóna</label></th>
                <td>
                    <select class="ek-mapa-quiz-template-select" name="<?php echo esc_attr( $name_prefix ); ?>[template_id]">
                        <option value="0">— Vyber šablónu —</option>
                        <?php foreach ( $templates as $t ) : ?>
                            <option value="<?php echo esc_attr( $t->ID ); ?>" data-title="<?php echo esc_attr( $t->post_title ); ?>" <?php selected( $template_id, $t->ID ); ?>>
                                <?php echo esc_html( $t->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label>Počet otázok v sete</label></th>
                <td>
                    <input type="number" class="small-text" name="<?php echo esc_attr( $name_prefix ); ?>[pocet_otazok_v_sete]" value="<?php echo esc_attr( $q['pocet_otazok_v_sete'] ?? 10 ); ?>" min="1" max="100" />
                    <p class="description">Koľko otázok dostane hráč v jednom kole (vyberú sa náhodne zo šablóny).</p>
                </td>
            </tr>

            <tr>
                <th><label>Vstup: zobraziť výber tímu</label></th>
                <td>
                    <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[show_entry_form]" value="1" <?php checked( ! empty( $q['show_entry_form'] ) ); ?> />
                    <p class="description">Zapnuté: hráč bez tímu v URL uvidí výber tímu. Funguje s „Výber tímu z preddefinovaného zoznamu" v General tabe.</p>
                </td>
            </tr>

            <tr>
                <th><label>Max body za celý kvíz <em style="color:#888;font-weight:400">(override)</em></label></th>
                <td>
                    <input type="number" class="small-text" name="<?php echo esc_attr( $name_prefix ); ?>[max_points_override]" value="<?php echo esc_attr( $q['max_points_override'] ?? '' ); ?>" min="0" max="9999" placeholder="prázdne = default zo šablóny" />
                    <p class="description"><code>max_per_úloha = max_body / počet_otázok</code>. <strong>Prázdne</strong> = použije sa hodnota <strong>z mapovej šablóny</strong> (default 100).</p>
                </td>
            </tr>

            <tr>
                <th><label>Stupne hodnotenia <em style="color:#888;font-weight:400">(override)</em></label></th>
                <td>
                    <div class="ek-mapa-tiers-wrap">
                        <input type="hidden" class="ek-mapa-tiers-json" name="<?php echo esc_attr( $name_prefix ); ?>[score_tiers_override]" value="<?php echo esc_attr( $q['score_tiers_override'] ?? '' ); ?>" />
                        <table class="widefat striped" style="max-width:420px">
                            <thead>
                                <tr>
                                    <th style="width:130px">Do vzdialenosti</th>
                                    <th style="width:130px">% z max bodov</th>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>
                            <tbody class="ek-mapa-tiers-tbody"></tbody>
                        </table>
                        <p class="ek-mapa-tiers-empty" style="margin:6px 0;color:#888;font-style:italic;display:none">Žiadne stupne — prázdne = default zo šablóny.</p>
                        <p style="margin-top:6px">
                            <button type="button" class="button button-small ek-mapa-tier-add">+ Pridať stupeň</button>
                        </p>
                    </div>
                    <p class="description">
                        ⚠ Aplikuje sa LEN pre šablóny typu „Hľadanie miest" (pin). Pre rieku/pohorie je hodnotenie binárne (správne = max body, nesprávne = 0).<br>
                        Príklad: 0–5 km = 100 %, 5–10 km = 75 %. Pri vzdialenosti väčšej než posledný stupeň → 0 bodov.<br>
                        <strong>Prázdne</strong> = použijú sa default stupne <strong>z mapovej šablóny</strong> (najčastejšie: do 5 km = 100 %, do 10 km = 75 %, do 20 km = 50 %, do 40 km = 25 %). Skutočný default si pozri / nastav v editore vybranej šablóny.
                    </p>
                </td>
            </tr>

            <tr>
                <th><label>Pri opakovaní označ správnosť</label></th>
                <td>
                    <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[mark_correctness_on_retry]" value="1" <?php checked( ! empty( $q['mark_correctness_on_retry'] ) ); ?> />
                </td>
            </tr>

            <tr>
                <th><label>Pri opakovaní nový set</label></th>
                <td>
                    <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[new_questions_on_retry]" value="1" <?php checked( ! empty( $q['new_questions_on_retry'] ) ); ?> />
                </td>
            </tr>

            <tr>
                <th><label>Odhaliť správne odpovede</label></th>
                <td>
                    <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[zobraz_spravne_odpovede]" value="1" <?php checked( ! empty( $q['zobraz_spravne_odpovede'] ) ); ?> />
                    <p class="description">Zapnuté: hráč pri vyhodnotení vidí mini-mapu so správnou lokáciou pri nesprávnych odpovediach.</p>
                </td>
            </tr>

            <tr>
                <th><label>Hodnotenie hráčových odpovedí</label></th>
                <td>
                    <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[zobraz_spravne_uhadnute_odpovede]" value="1" <?php checked( ! empty( $q['zobraz_spravne_uhadnute_odpovede'] ), true ); ?> />
                    <p class="description">Zapnuté: hráč vidí per-úloha hodnotenie (správne/nesprávne + body).</p>
                </td>
            </tr>

            <tr>
                <th><label>Počet pokusov</label></th>
                <td>
                    <input type="number" class="small-text" name="<?php echo esc_attr( $name_prefix ); ?>[pocet_pokusov]" value="<?php echo esc_attr( $q['pocet_pokusov'] ?? 10 ); ?>" min="1" />
                </td>
            </tr>

            <tr>
                <th><label>Min. body na postup</label></th>
                <td>
                    <input type="number" class="small-text" name="<?php echo esc_attr( $name_prefix ); ?>[min_body_na_postup]" value="<?php echo esc_attr( $q['min_body_na_postup'] ?? 0 ); ?>" min="0" />
                    <p class="description">Ak hráč nedosiahne tento prah, môže opakovať. 0 = bez prahu.</p>
                </td>
            </tr>

            <tr class="ek-mapa-email-row">
                <th><label>Poslať výsledok mailom</label></th>
                <td>
                    <span class="ek-mapa-email-toggle">
                        <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[poslat_vysledok_usera_mailom]" value="1" <?php checked( ! empty( $q['poslat_vysledok_usera_mailom'] ) ); ?> />
                    </span>
                    <span class="ek-mapa-email-input" style="margin-left:10px; <?php echo ! empty( $q['poslat_vysledok_usera_mailom'] ) ? '' : 'display:none;'; ?>">
                        <input type="email" class="regular-text" name="<?php echo esc_attr( $name_prefix ); ?>[admin_mail]" value="<?php echo esc_attr( $q['admin_mail'] ?? '' ); ?>" placeholder="admin@example.com" />
                    </span>
                </td>
            </tr>

        </table>

        <p style="text-align:right">
            <button type="button" class="button button-link-delete ek-mapa-quiz-remove">🗑️ Vymazať tento mapový kvíz</button>
        </p>
    </fieldset>
    <?php
}

/**
 * Helper: vygeneruje krátky obfuscated slug pre nový sub-kvíz (6 hex chars).
 * Hub link bude /mapa-quiz/?akcia=X&mq=<slug>; admin neuvidí slug v UI.
 */
public static function ek_generate_mapa_slug() {
    return substr( bin2hex( random_bytes( 4 ) ), 0, 6 );
}

/**
 * Validate + normalize tier JSON. Prijíma JSON string z hidden inputu,
 * vráti čistý JSON (sorted by maxKm asc, percent clamped 0..100) alebo
 * prázdny string ak input je broken/empty → fallback na template default.
 */
public static function sanitize_tiers_json( $raw ) {
    $raw = trim( $raw );
    if ( $raw === '' ) return '';
    $decoded = json_decode( $raw, true );
    if ( ! is_array( $decoded ) ) return '';
    $norm = array();
    foreach ( $decoded as $t ) {
        if ( ! is_array( $t ) || ! isset( $t['maxKm'], $t['percent'] ) ) continue;
        $norm[] = array(
            'maxKm'   => max( 0, (float) $t['maxKm'] ),
            'percent' => max( 0, min( 100, (float) $t['percent'] ) ),
        );
    }
    if ( empty( $norm ) ) return '';
    usort( $norm, function( $a, $b ) { return $a['maxKm'] <=> $b['maxKm']; } );
    return wp_json_encode( $norm );
}

/**
 * Save mapa multi-quiz repeater data ako JSON array do event_mapa_quizzes.
 * Pre každý sub-kvíz vygeneruje slug ak chýba a sanitizuje fields.
 * Auto-prefill label z template title ak prázdne.
 */
private function save_mapa_quizzes( $post_id ) {
    $raw = isset( $_POST['event_mapa_quizzes'] ) && is_array( $_POST['event_mapa_quizzes'] ) ? wp_unslash( $_POST['event_mapa_quizzes'] ) : array();
    $clean = array();
    foreach ( $raw as $q ) {
        if ( ! is_array( $q ) ) continue;
        $template_id = (int) ( $q['template_id'] ?? 0 );
        if ( $template_id <= 0 ) continue; // skip incomplete (no template selected)

        $slug = isset( $q['slug'] ) ? sanitize_key( $q['slug'] ) : '';
        if ( $slug === '' ) {
            $slug = self::ek_generate_mapa_slug();
        }

        $label = sanitize_text_field( (string) ( $q['label'] ?? '' ) );
        if ( $label === '' ) {
            // Auto-prefill z template title
            $tpl = get_post( $template_id );
            if ( $tpl ) $label = $tpl->post_title;
        }

        $clean[] = array(
            'slug'                            => $slug,
            'label'                           => $label,
            'template_id'                     => $template_id,
            'show_entry_form'                 => ! empty( $q['show_entry_form'] ),
            'pocet_otazok_v_sete'             => max( 1, (int) ( $q['pocet_otazok_v_sete'] ?? 10 ) ),
            'max_points_override'             => sanitize_text_field( (string) ( $q['max_points_override'] ?? '' ) ),
            'score_tiers_override'            => self::sanitize_tiers_json( (string) ( $q['score_tiers_override'] ?? '' ) ),
            'mark_correctness_on_retry'       => ! empty( $q['mark_correctness_on_retry'] ),
            'new_questions_on_retry'          => ! empty( $q['new_questions_on_retry'] ),
            'zobraz_spravne_odpovede'         => ! empty( $q['zobraz_spravne_odpovede'] ),
            'zobraz_spravne_uhadnute_odpovede' => ! empty( $q['zobraz_spravne_uhadnute_odpovede'] ),
            'pocet_pokusov'                   => max( 1, (int) ( $q['pocet_pokusov'] ?? 10 ) ),
            'min_body_na_postup'              => max( 0, (int) ( $q['min_body_na_postup'] ?? 0 ) ),
            'poslat_vysledok_usera_mailom'    => ! empty( $q['poslat_vysledok_usera_mailom'] ),
            'admin_mail'                      => sanitize_email( (string) ( $q['admin_mail'] ?? '' ) ),
        );
    }

    update_post_meta( $post_id, 'event_mapa_quizzes', wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ) );

    // Delete legacy single-mapa postmeta (clean break — žiadne live mapa kvízy)
    $legacy_keys = array( 'template_id', 'show_entry_form', 'pocet_otazok_v_sete',
        'max_points_override', 'score_tiers_override', 'mark_correctness_on_retry',
        'new_questions_on_retry', 'zobraz_spravne_odpovede', 'zobraz_spravne_uhadnute_odpovede',
        'pocet_pokusov', 'min_body_na_postup', 'poslat_vysledok_usera_mailom',
        'admin_mail', 'mapa_quiz_active' );
    foreach ( $legacy_keys as $k ) {
        delete_post_meta( $post_id, 'event_mapa_' . $k );
    }
}

}