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
	 * Plugin Settings
	 * 
	 * @since    1.0.0
	 * 
	 * @var      array
	 */
	protected $settings = null;

	/**
	 * Plugin Settings slug
	 * 
	 * @since    1.0.0
	 * 
	 * @var      string
	 */
	protected $plugin_settings = 'wpfluxbb_settings';

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

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		$this->fluxbb_config = $this->get_fluxbb_config();

		$this->fluxdb = $this->get_fluxbb_db();
		$this->wpdb   = $wpdb;

		$this->settings = array(
			'fluxbb_config_file' => '',
			'fluxbb_base_url' => '',
			'wpfluxbb' => array(
				'auto_insert_user'  => 0,
				'remove_login_logo' => 1,
			)
		);
		$this->wpfluxbb_default_settings();

		add_action( 'enqueue_styles', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_authenticate', array( $this, 'wpfluxbb_authenticate' ), 1, 2 );
		add_action( 'login_head', array( $this, 'wpfluxbb_remove_login_logo' ) );
		add_action( 'login_footer', array( $this, 'wpfluxbb_login_footer' ) );

		add_filter( 'allowed_redirect_hosts', array( $this, 'wpfluxbb_allow_forum_redirect' ) );
		add_filter( 'login_redirect', array( $this, 'wpfluxbb_login_redirect' ) );
		add_filter( 'register_url', array( $this, 'wpfluxbb_register_url' ) );
		add_filter( 'lostpassword_url', array( $this, 'wpfluxbb_lostpassword_url' ) );

	}

	/**
	 * Retrieve FluxBB Config
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_fluxbb_config() {

		$config_file = $this->wpfluxbb_o('fluxbb_config_file');

		if ( ! file_exists( $config_file ) )
			return false;

		require_once $config_file;
		$fluxbb_config = array(
			'db' => array(
				'host'     => $db_host,
				'name'     => $db_name,
				'username' => $db_username,
				'password' => $db_password,
				'prefix'   => $db_prefix,
			),
			'cookie' => array(
				'name'   => $cookie_name,
				'domain' => $cookie_domain,
				'path'   => $cookie_path,
				'secure' => $cookie_secure,
				'seed'   => $cookie_seed
			)
		);

		return $fluxbb_config;
	}

	/**
	 * FluxBB Database Connection
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_fluxbb_db() {

		if ( empty( $this->fluxbb_config ) )
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
         * Register and enqueue public-facing style sheet.
         *
         * @since    1.0.0
         */
        public function enqueue_styles() {
                wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
        }

	/**
	 * Load WPFluxBB default settings if unexisting.
	 *
	 * @since    1.0.0
	 */
	public function wpfluxbb_default_settings( $force = false ) {

		$options = get_option( $this->plugin_settings );
		if ( ( false === $options || ! is_array( $options ) ) || true == $force ) {
			delete_option( $this->plugin_settings );
			add_option( $this->plugin_settings, $this->settings );
		}
	}

	/**
	 * Apply 'register_url' filter: redirect Registration to FluxBB's
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_register_url( $url ) {
		return ( '' != $this->wpfluxbb_o('fluxbb_base_url') ? esc_url( $this->wpfluxbb_o('fluxbb_base_url') ) . '/register.php' : $url );
	}

	/**
	 * Apply 'lostpassword_url' filter: redirect Password Recovery process
	 * to FluxBB's
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_lostpassword_url( $url ) {
		return ( '' != $this->wpfluxbb_o('fluxbb_base_url') ? esc_url( $this->wpfluxbb_o('fluxbb_base_url') ) . '/login.php?action=forget' : $url );
	}

	/**
	 * Remove the WordPress logo on Login page, inappropriate since we're
	 * using the page to log in both WordPress and FluxBB.
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_remove_login_logo() {
		if ( 1 == $this->wpfluxbb_o('remove_login_logo') )
			echo '<style type="text/css">h1 a {background:transparent !important;}</style>';
	}

	/**
	 * Replace the WordPress "Back to Blog" link by a link to the Forum.
	 * Actually we can't simply remove the link, so we include a style block
	 * to hide it and insert a new look-alike link below.
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_login_footer() {
		if ( '' != $this->wpfluxbb_o('fluxbb_base_url') ) {
			echo '<style type="text/css">#backtoblog {display:none;} .login #nav, #backtoforum {text-align:center;} #backtoforum {margin:auto;padding:12px 0 0 0;width:320px;}</style>';
			echo '<p id="backtoforum"><a href="' . esc_url( $this->wpfluxbb_o('fluxbb_base_url') ) . '" title="' . esc_attr__( 'Are you lost?' ) . '">' . sprintf( __( '&larr; Back to %s' ), get_bloginfo( 'title', 'display' ) ) . '</a></p>';
		}
	}

	/**
	 * Change the Login Redirect URL to redirect Users to the Forum after
	 * Loggin in if the User came from it. This allows the possibility to
	 * Log in directly to WordPress and reach the Dashboard like usual.
	 * 
	 * @since    1.0.0
	 *
	 * @param    string    $redirect_to    The default redirect URL
	 *
	 * @return   string    New redirect URL
	 */
	public function wpfluxbb_login_redirect( $redirect_to ) {

		$forum = $this->wpfluxbb_o('fluxbb_base_url');
		if ( ! is_null( $_SERVER['HTTP_REFERER'] ) && trailingslashit( $forum ) == trailingslashit( $_SERVER['HTTP_REFERER'] ) )
			$redirect_to = $forum;

		return $redirect_to;
	}

	/**
	 * Add the forum URL to the Allowed Redirect Hosts list.
	 * 
	 * @since    1.0.0
	 *
	 * @param    array     $hosts    The default Allowed Hosts
	 *
	 * @return   array     Completed Hosts list
	 */
	public function wpfluxbb_allow_forum_redirect( $hosts ) {
		
		$forum = parse_url( $this->wpfluxbb_o('fluxbb_base_url') );
		if ( '' != $forum['host'] )
			$hosts[] = $forum['host'];

		return $hosts;
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

		if ( ! $this->fluxdb || ! isset( $user_login ) && ! isset( $user_pass ) || '' == $user_login || '' == $user_pass )
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

	/**
	 * Built-in option finder/modifier
	 * Default behavior with no empty search and value params results in
	 * returning the complete WPFluxBB options' list.
	 * 
	 * If a search query is specified, navigate through the options'
	 * array and return the asked option if existing, empty string if it
	 * doesn't exist.
	 * 
	 * If a replacement value is specified and the search query is valid,
	 * update WPFluxBB options with new value.
	 * 
	 * Return can be string, boolean or array. If search, return array or
	 * string depending on search result. If value, return boolean true on
	 * success, false on failure.
	 *
	 * @since    1.0.0
	 * 
	 * @param    string        Search query for the option: 'aaa-bb-c'. Default none.
	 * @param    string        Replacement value for the option. Default none.
	 * 
	 * @return   string|boolean|array        option array of string, boolean on update.
	 */
	public function wpfluxbb_o( $search = '', $value = null ) {

		$options = get_option( $this->plugin_settings, $this->settings );

		if ( '' != $search && is_null( $value ) ) {
			$s = explode( '-', $search );
			$o = $options;
			while ( count( $s ) ) {
				$k = array_shift( $s );
				if ( isset( $o[ $k ] ) )
					$o = $o[ $k ];
				else
					$o = '';
			}
		}
		else if ( '' != $search && ! is_null( $value ) ) {
			$s = explode( '-', $search );
			$this->wpfluxbb_o_( $options, $s, $value );
			$o = update_option( $this->plugin_settings, $options );
		}
		else {
			$o = $options;
		}

		return $o;
	}

	/**
	 * Built-in option modifier
	 * 
	 * Navigate through WPFluxBB options to find a matching option and update
	 * its value.
	 *
	 * @since    1.0.0
	 * 
	 * @param    array         Options array passed by reference
	 * @param    string        key list to match the specified option
	 * @param    string        Replacement value for the option. Default none
	 */
	private function wpfluxbb_o_( &$array, $key, $value = '' ) {
		$a = &$array;
		foreach ( $key as $k )
			$a = &$a[ $k ];
		$a = $value;
	}

}
