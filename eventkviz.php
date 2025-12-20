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
 * Version:           1.1.0
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
define( 'EVENKVIZ_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-eventkviz-activator.php
 */
function activate_eventkviz() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-activator.php';
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
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-links.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-statistika.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-seedpage.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-eventkviz-finalpage.php' );

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

add_action( 'init', array( 'Eventkviz_OneLink_Quiz_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_AllLinks_Quiz_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_Statistika_Class', 'load_shortcodes' ) );
add_action( 'init', array( 'Eventkviz_Seedpage_Class', 'load_shortcodes' ) );

add_action( 'init', array( 'Eventkviz_Finalpage_Class', 'load_shortcodes' ) );

