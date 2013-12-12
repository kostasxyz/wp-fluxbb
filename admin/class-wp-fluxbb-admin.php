<?php
/**
 * Plugin Name.
 *
 * @package   WPFluxBB_Admin
 * @author    Charlie MERLAND <charlie.merland@gmail.com>
 * @license   GPL-3.0+
 * @link      http://charliemerland.me
 * @copyright 2013 Charlie MERLAND
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package WPFluxBB_Admin
 * @author  Charlie MERLAND <charlie.merland@gmail.com>
 */
class WPFluxBB_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		if( ! is_super_admin() )
			return;

		// Call $plugin_slug from public plugin class.
		$plugin = WPFluxBB::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->plugin = &$plugin;

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		add_action( 'wp_ajax_scan_folders', array( $this, 'wpfluxbb_scan_folders_callback' ) );
		add_action( 'wp_ajax_test_config_file', array( $this, 'wpfluxbb_test_config_file_callback' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if( ! is_super_admin() )
			return;

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WPFluxBB::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), WPFluxBB::VERSION );
			wp_localize_script(
				$this->plugin_slug . '-admin-script', 'wp_ajax_',
				array(
					'ajax_url'     => admin_url( 'admin-ajax.php' ),
					'file_exists'  => __( 'File {file} exists.', $this->plugin_slug ),
					'file_invalid' => __( 'File {file} is invalid.', $this->plugin_slug )
				)
			);
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'WPFluxBB Settings', $this->plugin_slug ),
			__( 'WPFluxBB Settings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		if ( isset( $_POST['wpfluxbb'] ) && '' != $_POST['wpfluxbb'] )
			update_option( 'wpfluxbb_settings', $_POST['wpfluxbb'] );

		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

	/**
	 * AJAX Callback for wpfluxbb_scan_folders()
	 *
	 * @since    1.0.0
	 */
	public function wpfluxbb_scan_folders_callback() {

		$scan = $this->wpfluxbb_scan_folders();
		echo json_encode( $scan );
		die();
	}

	/**
	 * AJAX Callback for wpfluxbb_test_config_file()
	 *
	 * @since    1.0.0
	 */
	public function wpfluxbb_test_config_file_callback() {

		$file = ( isset( $_GET['config_file'] ) && '' != $_GET['config_file'] ? $_GET['config_file'] : null );
		$test = $this->wpfluxbb_test_config_file( $file );
		echo json_encode( $test );
		die();
	}

	/**
	 * Scan possible folders for a FluxBB Config File
	 *
	 * @since    1.0.0
	 */
	private function wpfluxbb_scan_folders() {

		$files = array();

		$current = dirname( __FILE__ );

		if ( false === ( $www = stripos( $current, 'www' ) ) )
			return array( 'error_code' => 0, 'error_message' => __( "Couldn't find a 'www' folder to scan.", $this->plugin_slug ) );

		$base = substr( $current, 0, $www + 3 );
		$dir  = scandir( $base );

		if ( false === $dir )
			return array( 'error_code' => 1, 'error_message' => __( sprintf( "Couldn't scan '%s' folder.", $dir ), $this->plugin_slug ) );

		foreach ( $dir as $d ) {
			$preg = preg_match( '#(forum|forums|fluxbb)#i', strtolower( $d ), $m );
			$file = $base . '/' . $m[0] . '/config.php';
			if ( $preg && file_exists( $file ) )
				$files[] = $file;
		}

		$files = $this->wpfluxbb_validate_config_files( $files );
		if ( ! is_array( $files ) )
			$files = array( $files );

		return $files;
	}

	/**
	 * Check the validity of submitted FluxBB Config Files.
	 * 
	 * Kind of an alias for passing multiple files to
	 * wpfluxbb_validate_config_file()
	 *
	 * @since    1.0.0
	 * 
	 * @param    array    $files Possible FluxBB Config Files to check
	 * 
	 * @return   array    Valid Config Files if any.
	 */
	private function wpfluxbb_validate_config_files( $files ) {

		if ( empty( $files ) )
			return false;

		$valid  = array();
		$errors = array();

		foreach ( $files as $i => $file )
			$valid[] = $this->wpfluxbb_validate_config_file( $file );

		return $valid;
	}

	/**
	 * Check the validity of submitted FluxBB Config File.
	 * 
	 * If $verbose is set to true, the function will return an array of
	 * detailled errors if anything went wrong. If $verbose is false, only
	 * return boolean status, valid or not.
	 *
	 * @since    1.0.0
	 * 
	 * @param    array    $files Possible FluxBB Config Files to check
	 * @param    boolean  $verbose Chatty or not chatty
	 * 
	 * @return   array    Valid Config Files if any.
	 */
	private function wpfluxbb_validate_config_file( $file, $verbose = true ) {

		$required_vars = array(
			array( 'db_type', false ),
			array( 'db_host', true ),
			array( 'db_name', true ),
			array( 'db_username', true ),
			array( 'db_password', true ),
			array( 'db_prefix', false ),
			array( 'cookie_name', true ),
			array( 'cookie_domain', true ),
			array( 'cookie_path', true ),
			array( 'cookie_secure', false ),
			array( 'cookie_seed', true )
		);

		if ( ! file_exists( $file ) )
			return array(
				'file'   => $file,
				'errors' => array(
					array(
						'error_code' => 1,
						'error_message' => sprintf( __( 'File "%s" does not exists.', $this->plugin_slug ), $file )
					)
				)
			);

		$size = filesize( $file );
		$perm = is_readable( $file );

		$valid = array(
			'file'   => $file,
			'errors' => array()
		);

		if ( $size && $perm ) {

			$content = file_get_contents( $file );
			$valid['content'] = $content;

			foreach ( $required_vars as $req ) {
				$var_name = '$' . $req[0];
				$required = $req[1];
				if ( false === stripos( $content, $var_name ) ) {
					$valid['errors'][] = array(
						'error_code' => 2,
						'error_message' => sprintf( __( 'Variable "%s" is missing in %s file.', $this->plugin_slug ), $var_name, $file )
					);
				}
				else if ( $required && preg_match( '~^\s*'.'\\'.$var_name.'\s*=\s*(["\'])\s*\1[^\S\r\n]*\R?~m', $content ) ) {
					$valid['errors'][] = array(
						'error_code' => 3,
						'error_message' => sprintf( __( 'Variable "%s" is empty in %s file.', $this->plugin_slug ), $var_name, $file )
					);
				}
			}
		}
		else if ( ! $size ) {
			$valid['errors'][] = array(
				'error_code' => 4,
				'error_message' => sprintf( __( 'File %s exists but seems empty.', $this->plugin_slug ), $file )
			);
		}
		else if ( ! $perm ) {
			$valid['errors'][] = array(
				'error_code' => 5,
				'error_message' => sprintf( __( 'File %s exists but seems unreadable (check permissions).', $this->plugin_slug ), $file )
			);
		}

		if ( ! $verbose )
			return empty( $valid['errors'] );

		return $valid;
	}

	/**
	 * Test a FluxBB Config File
	 *
	 * @since    1.0.0
	 */
	private function wpfluxbb_test_config_file( $file ) {

		if ( is_null( $file ) )
			return __( 'Wrong file path.', 'wp-fluxbb' );

		$validate = $this->wpfluxbb_validate_config_file( $file );
		if ( ! empty( $validate['errors'] ) )
			return array( 'errors' => $validate['errors'] );

		require_once $file;

		$test_db = new wpdb( $db_username, $db_password, $db_name, $db_host );

		if ( ! empty( $test_db->error ) )
			return __( 'Failed to connect to the database.', 'wp-fluxbb' );

		return array();
	}

}
