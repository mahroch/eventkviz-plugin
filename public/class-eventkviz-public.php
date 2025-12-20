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