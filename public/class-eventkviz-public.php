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

	private function detect_quiz_type() {
		// Preferred: detect by shortcode in current post content
		if (function_exists('is_singular') && is_singular()) {
			$post = get_queried_object();
			if ($post instanceof WP_Post) {
				if (has_shortcode($post->post_content, 'movies_form_dynamic')) {
					return 'movies';
				}
				if (has_shortcode($post->post_content, 'music_form_dynamic')) {
					return 'music';
				}
			}
		}

		// Fallback: legacy slug-based detection (kept for pages where shortcode lives
		// inside Elementor widgets and isn't part of post_content)
		$current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
		$page_to_type = apply_filters('eventkviz_quiz_slug_map', array(
			'merdfghh' => 'movies',
			'aqljk'    => 'music',
		));
		foreach ($page_to_type as $page => $type) {
			if (strpos($current_path, $page) !== false) {
				return $type;
			}
		}

		return false;
	}

	private function should_load_autocomplete() {
		return $this->detect_quiz_type() !== false;
	}

	private function is_mapa_form_page() {
		if ( ! function_exists( 'is_singular' ) || ! is_singular() ) return false;
		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) return false;
		return has_shortcode( $post->post_content, 'mapa_form_dynamic' )
			|| has_shortcode( $post->post_content, 'eval_mapa_quiz_dynamic' );
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

		if ($this->detect_quiz_type() !== false || $this->is_mapa_form_page()) {
			wp_enqueue_script(
				$this->eventkviz . '-quiz-form',
				plugin_dir_url( __FILE__ ) . 'js/eventkviz-quiz-form.js',
				array( 'jquery' ),
				$this->version,
				true
			);
		}

		// Mapa quiz: Leaflet + custom JS + CSS — len keď je shortcode [mapa_form_dynamic] na stránke
		if ($this->is_mapa_form_page()) {
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			);
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			);
			wp_enqueue_style(
				$this->eventkviz . '-mapa-form',
				plugin_dir_url( __FILE__ ) . 'css/eventkviz-mapa-form.css',
				array( 'leaflet' ),
				$this->version
			);
			wp_enqueue_script(
				$this->eventkviz . '-mapa-form',
				plugin_dir_url( __FILE__ ) . 'js/eventkviz-mapa-form.js',
				array( 'leaflet', 'jquery' ),
				$this->version,
				true
			);
			$maptiler_key = '';
			if ( class_exists( 'Eventkviz_Settings' ) ) {
				$maptiler_key = Eventkviz_Settings::get_maptiler_key();
			} elseif ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eventkviz-settings.php' ) ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eventkviz-settings.php';
				$maptiler_key = Eventkviz_Settings::get_maptiler_key();
			}
			wp_localize_script(
				$this->eventkviz . '-mapa-form',
				'ekMapaCfg',
				array(
					'geoJsonBase'  => plugin_dir_url( __FILE__ ) . 'data/regions/',
					'maptilerKey'  => (string) $maptiler_key,
				)
			);
		}

		if ($this->should_load_autocomplete()) {
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			
			wp_enqueue_script(
				$this->eventkviz . '-autocomplete',
				plugin_dir_url( __FILE__ ) . 'js/eventkviz-autocomplete.js',
				array( 'jquery', 'jquery-ui-autocomplete' ),
				$this->version,
				true
			);
		}
	}

	public function localize_autocomplete_data() {
		$quiz_type = $this->detect_quiz_type();
		if ($quiz_type === false) {
			return;
		}

		$type_to_datasets = array(
			'music'  => array( 'artists', 'songs' ),
			'movies' => array( 'movies' ),
		);

		wp_localize_script(
			$this->eventkviz . '-autocomplete',
			'eventkvizCfg',
			array(
				'apiUrl'   => esc_url_raw( rest_url( 'eventkviz/v1/search' ) ),
				'datasets' => isset( $type_to_datasets[ $quiz_type ] ) ? $type_to_datasets[ $quiz_type ] : array(),
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
    $public_query_vars[] = 'type'; // for hub page filtering: ?type=music|movies|knowledge|sudoku
    return $public_query_vars;
}

add_action( 'wp_enqueue_scripts', 'my_plugin_styles' );
function my_plugin_styles() {
		// Verzia = filemtime CSS súboru → cache sa automaticky obnoví pri každej
		// zmene eventkviz.css (predtým bez verzie → prehliadač cachoval natrvalo
		// a úpravy štýlov sa neprejavili bez hard refresh).
		$css_path = plugin_dir_path( __FILE__ ) . 'css/eventkviz.css';
		$ver = file_exists( $css_path ) ? filemtime( $css_path ) : ( defined( 'EVENKVIZ_VERSION' ) ? EVENKVIZ_VERSION : false );
		wp_enqueue_style( 'eventkviz-css', plugins_url( 'css/eventkviz.css', __FILE__ ), array(), $ver );
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
		'mapa_form_dynamic', 'eval_mapa_quiz_dynamic',
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