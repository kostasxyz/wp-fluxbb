<?php
/**
 * WPFluxBB
 *
 * @package   WPFluxBB
 * @author    Charlie MERLAND <charlie.merland@gmail.com>
 * @license   GPL-3.0+
 * @link      http://charliemerland.me
 * @copyright 2013 Charlie MERLAND
 */

/**
 * @package WPFluxBB
 * @author  Charlie MERLAND <charlie.merland@gmail.com>
 */
class WPFluxBB {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin Slug.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'wp-fluxbb';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		global $wpdb;

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		$this->fluxbb_config = $this->get_fluxbb_config();
		$this->fluxdb = &$this->get_fluxbb_db();
		$this->wpdb   = &$wpdb;

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// FluxBB login
		add_action( 'wp_authenticate', array( $this, 'wpfluxbb_authenticate' ), 1, 2 );

		// FluxBB register URL
		add_filter( 'register_url', array( $this, 'wpfluxbb_register_url' ) );


	}

	/**
	 * Retrieve FluxBB Config
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_fluxbb_config() {

		global $fluxbb_config;

		return ( is_null( $fluxbb_config ) ? false : $fluxbb_config );
	}

	/**
	 * FluxBB Database Connection
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_fluxbb_db() {

		if ( ! $this->fluxbb_config )
			return false;

		extract( $this->fluxbb_config['db'] );

		$fluxdb = new wpdb( $username, $password, $name, $host );
		$fluxdb->users = $prefix . 'users';

		return $fluxdb;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) )
			return;

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Apply 'register_url' filter: redirect Registration to FluxBB's
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_register_url( $url, $redirect ) {
		return $this->fluxbb_config['base_url'] . '/register.php';
	}

	/**
	 * Password Hashing 'à la mode de FluxBB'
	 * 
	 * @since    1.0.0
	 *
	 * @param    string    $plain_text    Plain text Password
	 *
	 * @return   string    Hashed Password.
	 */
	public function wpfluxbb_hash( $plain_text ) {
		return sha1( $plain_text );
	}

	/**
	 * Log User to FluxBB using wp_authenticate hook.
	 * 
	 * @since    1.0.0
	 *
	 * @param    string    $user_login    User login
	 * @param    string    $user_pass     User pass
	 *
	 * @return   boolean   True if User successfully logged in, false else.
	 */
	public function wpfluxbb_authenticate( $user_login, $user_pass ) {

		if ( ! isset( $user_login ) && ! isset( $user_pass ) || '' == $user_login || '' == $user_pass )
			return false;

		$user = $this->fluxdb->get_row(
			$this->fluxdb->prepare(
				"SELECT * FROM {$this->fluxdb->users} WHERE username = %s LIMIT 1",
				$user_login
			)
		);

		// Is this a FluxBB user?
		if ( $this->wpfluxbb_hash( $user_pass ) == $user->password )
			$this->wpfluxbb_setcookie( $user );
		else
			return false;

		// Get WordPress User account if existing
		$wp_user = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->wpdb->users} WHERE user_login = %s LIMIT 1",
				$user_login
			)
		);

		// Not a WP User yet? Fix this.
		if ( is_null( $wp_user ) )
			$this->wpfluxbb_register_new_user( $user_login, $user_pass, $user );

		return true;
	}

	/**
	 * Register a new WordPress User based on FluxBB User.
	 * This function is a copy of WordPress' register_new_user() function
	 * except we avoid the random generated password.
	 * 
	 * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/user.php#L1661
	 * 
	 * @since    1.0.0
	 *
	 * @param    string    $user_login    User login
	 * @param    string    $user_pass     User pass
	 *
	 * @return   int|WP_Error     User ID if registered successfully, WP_Error else
	 */
	public function wpfluxbb_register_new_user( $user_login, $user_pass, $userdata ) {
		$user_id = wp_create_user( $user_login, $user_pass, $userdata->email );
		return $user_id;
	}

	/**
	 * Set FluxBB Cookie.
	 * Implementation of FluxBB forum_setcookie() and pun_setcookie()
	 * 
	 * @see https://fluxbb.org/docs/v1.5/functions#forum_setcookie-name-value-expire
	 * @see https://fluxbb.org/docs/v1.5/functions#pun_setcookie-user_id-password_hash-expire
	 * 
	 * @since    1.0.0
	 *
	 * @param    array     $user          User login & password
	 *
	 * @return   boolean   True if cookie if set correctly, false else.
	 */
	public function wpfluxbb_setcookie( $user ) {

		$cookie = $this->fluxbb_config['cookie'];

		$expire = time() + 31536000;
		$value  = sprintf( '%d|%s|%d|%s', $user->id, $this->wpfluxbb_hmac( $user->password, $cookie['seed'].'_password_hash' ), $expire, $this->wpfluxbb_hmac( $user->id . '|' . $expire, $cookie['seed'] . '_cookie_hash' ) );

		header('P3P: CP="CUR ADM"');

		if ( version_compare( PHP_VERSION, '5.2.0', '>=' ) )
			$c = setcookie( $cookie['name'], $value, $expire, $cookie['path'], $cookie['domain'], $cookie['secure'], true );
		else
			$c = setcookie( $cookie['name'], $value, $expire, $cookie['path'] . '; HttpOnly', $cookie['domain'], $cookie['secure'] );
	}

	/**
	 * SHA1 HMAC with PHP 4 fallback.
	 * Implementation of FluxBB forum_hmac()
	 * 
	 * @see https://fluxbb.org/docs/v1.5/functions#function-forum_hmac-data-key-raw_output
	 * 
	 * @since    1.0.0
	 *
	 * @return   string    Hashed text
	 */
	public function wpfluxbb_hmac( $data, $key, $raw_output = false ) {

		if ( function_exists('hash_hmac') )
			return hash_hmac( 'sha1', $data, $key, $raw_output );

		if ( 64 < strlen( $key ) )
			$key = pack( 'H*', sha1( $key ) );

		$key       = str_pad( $key, 64, chr( 0x00 ) );
		$hmac_opad = str_repeat( chr( 0x5C ), 64 );
		$hmac_ipad = str_repeat( chr( 0x36 ), 64 );

		for ( $i = 0; $i < 64; $i++ ) {
			$hmac_opad[ $i ] = $hmac_opad[ $i ] ^ $key[ $i ];
			$hmac_ipad[ $i ] = $hmac_ipad[ $i ] ^ $key[ $i ];
		}

		$hash = sha1( $hmac_opad . pack( 'H*', sha1( $hmac_ipad . $data ) ) );

		if ( $raw_output )
			$hash = pack( 'H*', $hash );

		return $hash;
	}

}
