<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://eventkviz.sk
 * @since             1.0.0
 * @package           Eventkviz
 *
 * @wordpress-plugin
 * Plugin Name:       Eventkviz
 * Plugin URI:        http://eventkviz.sk/
 * Description:       Quizes for events
 * Version:           1.16.1
 * Author:            Maros Markovic
 * Author URI:        http://eventkviz.sk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eventkviz
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EVENKVIZ_VERSION', '1.16.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-eventkviz-activator.php
 */
function activate_eventkviz() {
	Eventkviz_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-eventkviz-deactivator.php
 */
function deactivate_eventkviz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-deactivator.php';
	Eventkviz_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_eventkviz' );
register_deactivation_hook( __FILE__, 'deactivate_eventkviz' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz.php';

// Include the class file
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-musicquiz.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-moviesquiz.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-knowledgequiz.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-sudokuquiz.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-mapaquiz.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-links.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-statistika.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-seedpage.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-activator.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-finalpage.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-rest.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-link-token.php' );

// Opaque link token decode — early init aby $_GET[akcia/team/user/mq] boli
// dostupné pre všetky shortcodes/handlers nižšie v request cykle.
add_action( 'init', array( 'Eventkviz_Link_Token', 'apply_from_request' ), 4 );
if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-questions-admin.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-event-links.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-settings.php' );
	Eventkviz_Questions_Admin::init();
	Eventkviz_Event_Links_Admin::init();
	Eventkviz_Settings::init();

	// Idempotently ensure global hub pages exist (covers already-active installs).
	add_action( 'admin_init', array( 'Eventkviz_Activator', 'ensure_hub_pages' ) );
}

// Map quiz CPT — registers on `init` action; works in both admin + frontend
// (frontend may need to read templates for rendering, even though CPT itself
// is not publicly queryable).
require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-mapquiz-cpt.php' );
Eventkviz_MapQuiz_CPT::init();

// Mapquiz dataset registry — central definícia pre area/line datasety (bundled
// GeoJSON v public/data/regions/). Použité v admin (dataset dropdown) + form/eval.
require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-mapquiz-datasets.php' );

// Map quiz admin editor — meta boxes + save hook + asset enqueues. Admin-only.
if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-eventkviz-mapquiz-editor.php' );
	Eventkviz_MapQuiz_Editor::init();
}

Eventkviz_Rest_Search::init();

/**
 * GeoChallenge: ensure each browser visiting a quiz/hub page has a unique
 * session UUID cookie. Only sets cookie when:
 *   - akcia is in URL (= player is on a quiz/hub page)
 *   - cp is NOT in URL (= no per-player ID from GC app)
 *   - cookie for this akcia doesn't already exist
 *
 * Hooked early on `init` so setcookie() runs before any output. The cookie
 * is read later via Eventkviz_Quiz_Class::geo_user_code() — only when the
 * event has geochallenge_integration enabled. Setting the cookie eagerly
 * avoids an extra DB lookup on every init; harmless for non-GC events.
 */
add_action( 'init', function() {
	if ( empty( $_GET['akcia'] ) || ! empty( $_GET['cp'] ) ) {
		return;
	}
	$akcia = sanitize_key( $_GET['akcia'] );
	if ( $akcia === '' ) {
		return;
	}
	$cookie_name = 'eventkviz_gc_' . $akcia;
	if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
		return;
	}
	$uuid = function_exists( 'wp_generate_uuid4' )
		? wp_generate_uuid4()
		: bin2hex( random_bytes( 8 ) );
	if ( ! headers_sent() ) {
		setcookie( $cookie_name, $uuid, array(
			'expires'  => time() + 6 * HOUR_IN_SECONDS,
			'path'     => '/',
			'samesite' => 'Lax',
		) );
	}
	$_COOKIE[ $cookie_name ] = $uuid;
}, 5 );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_eventkviz() {

	$plugin = new Eventkviz();
	$plugin->run();

}
run_eventkviz();

// Register the shortcode on init
add_action( 'init', array( 'Eventkviz_MusicForm_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_MusicEval_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_MoviesForm_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_MoviesEval_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_KnowledgeForm_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_KnowledgeEval_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_SudokuForm_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_SudokuEval_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_MapaForm_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_MapaEval_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_OneLink_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_AllLinks_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_Statistika_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_Seedpage_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_Finalpage_Class', 'load_shortcodes' ) );

