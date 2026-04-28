<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Eventkviz
 * @subpackage Eventkviz/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Eventkviz
 * @subpackage Eventkviz/public
 * @author     Your Name <email@example.com>
 */
class Eventkviz_Public {

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
	 * @param      string    $eventkviz       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $eventkviz, $version ) {

		$this->eventkviz = $eventkviz;
		$this->version = $version;

	}

	private function should_load_autocomplete() {
		// Získaj aktuálnu URL cestu
		$current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
		
		// Povolené stránky
		$allowed_pages = array(
			'merdfghh',  // movies kviz
			'aqljk'      // music kviz
		);
		
		// Skontroluj či sme na povolenej stránke
		foreach ($allowed_pages as $page) {
			if (strpos($current_path, $page) !== false) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->eventkviz, plugin_dir_url( __FILE__ ) . 'css/eventkviz-public.css', array(), $this->version, 'all' );



		if ($this->should_load_autocomplete()) {
			wp_enqueue_style( 
				'jquery-ui-smoothness',
				'//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css',
				array(),
				'1.12.1'
			);
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->eventkviz, plugin_dir_url( __FILE__ ) . 'js/eventkviz-public.js', array( 'jquery' ), $this->version, false );
		
		if ($this->should_load_autocomplete()) {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			
			wp_enqueue_script(
				$this->eventkviz . '-autocomplete',
				plugin_dir_url( __FILE__ ) . 'js/eventkviz-autocomplete.js',
				array( 'jquery', 'jquery-ui-autocomplete' ),
				'1.1.1',
				true
			);
		}
	}

	public function localize_autocomplete_data() {
		global $wpdb;
		
		if (!$this->should_load_autocomplete()) {
			return;
		}

		// Artists - PRESNE ako si to mal ty
		$artists = array();
		$table_name = $wpdb->prefix . 'jet_cct_artists';
		$artists_array = $wpdb->get_results( "SELECT * FROM $table_name" );
		foreach ($artists_array as $item) {
			$artists[ addslashes($item->artist) ] = $item->_ID;
		}

		// Songs - PRESNE ako si to mal ty
		$songs = array();
		$table_name = $wpdb->prefix . 'jet_cct_songs';
		$songs_array = $wpdb->get_results( "SELECT * FROM $table_name" );
		foreach ($songs_array as $item) {
			$songs[ addslashes($item->song) ] = $item->_ID;
		}

		// Movies - PRESNE ako si to mal ty
		$movies = array();
		$table_name = $wpdb->prefix . 'jet_cct_movies';
		$movies_array = $wpdb->get_results( "SELECT * FROM $table_name" );
		foreach ($movies_array as $item) {
			$movies[ addslashes($item->original_title) ] = $item->_ID;
		}
		
		// Pošli do JS
		wp_localize_script(
			$this->eventkviz . '-autocomplete',
			'eventkvizAutocomplete',
			array(
				'artists' => $artists,
				'songs' => $songs,
				'movies' => $movies
			)
		);
	}

}


//custom_query_vars_filter
add_filter('query_vars', 'add_my_var');
function add_my_var($public_query_vars) {
    $public_query_vars[] = 'team';
	  $public_query_vars[] = 'user';
	  $public_query_vars[] = 'akcia';
    return $public_query_vars;
}

add_action( 'wp_enqueue_scripts', 'my_plugin_styles' );
function my_plugin_styles() {
		wp_enqueue_style( 'eventkviz-css', plugins_url( 'css/eventkviz.css', __FILE__ ) );
	}

// Vráti true ak aktuálny post obsahuje aspoň jeden eventkviz shortcode.
function eventkviz_is_eventkviz_page() {
	global $post;
	if ( ! is_singular() || ! $post instanceof WP_Post ) {
		return false;
	}
	$shortcodes = array(
		'show_team_links', 'show_link_to_quiz',
		'music_form_dynamic', 'eval_music_quiz_dynamic',
		'movies_form_dynamic', 'eval_movies_quiz_dynamic',
		'knowledge_form_dynamic', 'eval_knowledge_quiz_dynamic',
		'sudoku_form_dynamic', 'eval_sudoku_quiz_dynamic',
		'show_final_page', 'show_seed_page', 'statistika',
	);
	foreach ( $shortcodes as $sc ) {
		if ( has_shortcode( $post->post_content, $sc ) ) {
			return true;
		}
	}
	return false;
}

// Pridá body class "eventkviz-page" pre scoping CSS pravidiel.
add_filter( 'body_class', 'eventkviz_add_body_class' );
function eventkviz_add_body_class( $classes ) {
	if ( eventkviz_is_eventkviz_page() ) {
		$classes[] = 'eventkviz-page';
	}
	return $classes;
}

// Floating jazykový prepínač pre eventkviz pages — site header je skrytý cez CSS,
// switcher renderujeme priamo cez shortcode v pravom hornom rohu (fixed position).
// Podporované pluginy: Google Language Translator ([google-translator]), GTranslate ([gtranslate]).
add_action( 'wp_footer', 'eventkviz_render_lang_switcher' );
function eventkviz_render_lang_switcher() {
	if ( ! eventkviz_is_eventkviz_page() ) {
		return;
	}
	$shortcode = '';
	if ( shortcode_exists( 'google-translator' ) ) {
		$shortcode = '[google-translator]';
	} elseif ( shortcode_exists( 'gtranslate' ) ) {
		$shortcode = '[gtranslate]';
	} else {
		return; // žiadny lang switcher plugin nie je aktívny
	}
	echo '<div class="ek-langswitch">' . do_shortcode( $shortcode ) . '</div>';
}