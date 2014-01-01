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
			'fluxbb_lang' => 'English',
			'wpfluxbb' => array(
				'auto_insert_user'  => 0,
				'remove_login_logo' => 1,
			)
		);
		$this->wpfluxbb_default_settings();

		add_action( 'enqueue_styles', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_authenticate', array( $this, 'wpfluxbb_authenticate' ), 1, 2 );
		add_action( 'wp_logout', array( $this, 'wpfluxbb_logout' ) );
		add_action( 'login_head', array( $this, 'wpfluxbb_remove_login_logo' ) );
		add_action( 'login_footer', array( $this, 'wpfluxbb_login_footer' ) );
		add_action( 'user_register', array( $this, 'wpfluxbb_register_fx_user' ) );
		add_action( 'profile_update', array( $this, 'wpfluxbb_profile_update' ), 1, 2 );

		add_filter( 'allowed_redirect_hosts', array( $this, 'wpfluxbb_allow_forum_redirect' ) );
		add_filter( 'login_redirect', array( $this, 'wpfluxbb_login_redirect' ) );
		//add_filter( 'register_url', array( $this, 'wpfluxbb_register_url' ) );
		//add_filter( 'lostpassword_url', array( $this, 'wpfluxbb_lostpassword_url' ) );

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
	 * Triggered by the 'profile_update' Action Hook in wp_insert_user().
	 * When a User updates its WordPress Profile, edited fields are passed
	 * to FluxBB to update the Forum's Profile.
	 * 
	 * Allowed fields are 'user_email', 'user_url', 'user_nicename' and
	 * 'display_name'.
	 * 
	 * @since    1.0.0
	 * 
	 * @see wp_insert_user() For what fields can be set in $userdata
	 *
	 * @param    int       $user_id            ID of the Edited User
	 * @param    object    $update_userdata    Updated User Data from wp_insert_user()
	 */
	public function wpfluxbb_profile_update( $user_id, $update_userdata ) {

		$allowed_fields = array( 'user_email', 'user_url', 'user_nicename', 'display_name' );

		$user = new WP_User( $user_id );
		if ( is_wp_error( $user ) )
			return false;

		$current_userdata = get_object_vars( $user->data );
		$update_userdata  = get_object_vars( $update_userdata );
		$fields = array();

		foreach ( $current_userdata as $slug => $data ) {
			if ( in_array( $slug, $allowed_fields ) && isset( $update_userdata[ $slug ] ) && $update_userdata[ $slug ] != $data ) {
				$fields[ $slug ][] = $data;
			}
		}

		$this->wpfluxbb_update_fluxbb_profile( $user_id, $fields );

	}

	/**
	 * Propagate Profile changes to FluxBB Profiles.
	 * 
	 * @since    1.0.0
	 *
	 * @param    int       $user_id    ID of the Edited User
	 * @param    array     $data       Updated User Data from wp_insert_user()
	 */
	public function wpfluxbb_update_fluxbb_profile( $user_id, $data ) {

		if ( ! $this->fluxdb || empty( $data ) )
			return false;

		$set = array();

		foreach ( $data as $field => $value ) {
			switch ( $field ) {
				case 'user_email':
					$set[] = sprintf( 'SET email = "%s"', esc_url( $value ) );
					break;
				case 'user_url':
					$set[] = sprintf( 'SET url = "%s"', esc_url( $value ) );
					break;
				case 'user_nicename':
				case 'display_name':
					$set[] = sprintf( 'SET realname = "%s"', esc_attr( $value ) );
					break;
				case 'user_pass':
				default:
					    break;
			}
		}

		if ( empty( $set ) )
			return false;

		$set = implode( ', ', $set );

		$fluxbb_id = get_user_meta( $user_id, 'fluxbb_id', true );

		$this->fluxdb->query(
			$this->fluxdb->prepare(
				"UPDATE {$this->fluxdb->users} $set WHERE id = %d",
				$fluxbb_id
			)
		);

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
	 * Log User using wp_authenticate hook, checks for User duplicate
	 * Account and Dummy Passwords.
	 * 
	 * If a User logs in, we check if it exists on WordPress and FluxBB. If
	 * it exists on both, check for dummy passwords and update them if the
	 * other password is valid. If User exists only in one CMS, check the
	 * authentification validity and create the matching Account.
	 * 
	 * 
	 * TODO: check setcookie correct implementation.
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

		$wp_user = new WP_User( $user_login );
		$is_wp_user = $wp_user->exists();

		$fx_user = $this->fluxdb->get_row( $this->fluxdb->prepare( "SELECT * FROM {$this->fluxdb->users} WHERE username = %s LIMIT 1", $user_login ) );
		$is_fx_user = ! is_null( $fx_user );

		// User exists on both WordPress and FluxBB
		if ( $is_wp_user && $is_fx_user ) {

			// Password check for both accounts
			$wp_check = wp_check_password( $user_pass, $wp_user->data->user_pass, $wp_user->ID );
			$fx_check = ( $this->wpfluxbb_hash( $user_pass ) == $fx_user->password );

			// Invalid FluxBB and WordPress Passwords? Fail.
			if ( ! $fx_check && ! $wp_check ) {
				return false;
			}

			// Valid FluxBB Password but Dummy WordPress Password? Update it.
			else if ( $fx_check && ( ! $wp_check && 'WPFLUXBB' == $wp_user->data->user_pass ) ) {
				wp_set_password( $user_pass, $wp_user->ID );
				$this->wpfluxbb_setcookie( $fx_user );
				return true;
			}

			// Valid WordPress Password but Dummy FluxBB Password? Update it.
			else if ( $wp_check && ( ! $fx_check && 'WPFLUXBB' == $fx_user->password ) ) {
				$fluxbb_id = get_user_meta( $wp_user->ID, 'fluxbb_id', true );

				if ( '' != $fluxbb_id ) {
					$this->fluxdb->update(
						$this->fluxdb->users,
						array( 'password' => $this->wpfluxbb_hash( $user_pass ) ),
						array( 'ID' => $fluxbb_id ),
						array( '%s' ),
						array( '%d' )
					);
				}
				else {
					$this->fluxdb->update(
						$this->fluxdb->users,
						array( 'password' => $this->wpfluxbb_hash( $user_pass ) ),
						array( 'username' => $wp_user->user_login ),
						array( '%s' ),
						array( '%s' )
					);
					add_user_meta( $wp_user->ID, 'fluxbb_id', $fx_user->id, true );
				}

				$this->wpfluxbb_setcookie( $fx_user );
				return true;
			}

			// Valid FluxBB and WordPress Passwords? Set FluxBB cookie.
			else if ( $fx_check && $wp_check ) {
				$this->wpfluxbb_setcookie( $fx_user );
				return true;
			}
		}

		// WordPress User but not FluxBB User
		else if ( $is_wp_user && ! $is_fx_user ) {

			// Valid WordPress User?
			// This duplicates the authentification that will be done later
			// in wp_signon(), but we want to make sure the User account 
			// really exists before trying anything.
			$wp_user = wp_authenticate( $user_login, $user_pass );
			if ( ! is_wp_error( $wp_user ) )
				$this->wpfluxbb_register_fx_user( $wp_user->ID, $user_pass );
		}

		// FluxBB User but not WordPress User
		else if ( $is_fx_user && ! $is_wp_user ) {

			// Validates FluxBB Password
			if ( $this->wpfluxbb_hash( $user_pass ) == $user->password )
				$this->wpfluxbb_register_wp_user( $user_login, $user_pass, $fx_user );
		}

		// User doesn't exists
		else if ( ! $is_wp_user && ! $is_fx_user ) {
			return false;
		}

		return true;
	}

	/**
	 * Log the User off by replacing FluxBB Cookie by a void one.
	 * 
	 * @since    1.0.0
	 */
	public function wpfluxbb_logout() {

		$cookie = $this->fluxbb_config['cookie'];
		$expire = time() - 3600;

		if ( version_compare( PHP_VERSION, '5.2.0', '>=' ) )
			$c = setcookie( $cookie['name'], null, $expire, $cookie['path'], $cookie['domain'], $cookie['secure'], true );
		else
			$c = setcookie( $cookie['name'], null, $expire, $cookie['path'] . '; HttpOnly', $cookie['domain'], $cookie['secure'] );
	}

	/**
	 * Register a new WordPress User based on FluxBB User.
	 * This function is a copy of WordPress' register_new_user() function
	 * except we avoid the random generated password.
	 * 
	 * @see register_new_user()
	 * @link https://codex.wordpress.org/Function_Reference/register_new_user
	 * 
	 * @since    1.0.0
	 *
	 * @param    string    $user_login    User login
	 * @param    string    $user_pass     User pass
	 *
	 * @return   int|WP_Error     User ID if registered successfully, WP_Error else
	 */
	public function wpfluxbb_register_wp_user( $user_login, $user_pass, $userdata ) {

		$user_id = wp_create_user( $user_login, $user_pass, $userdata->email );

		if ( ! is_wp_error( $user_id ) )
			add_user_meta( $user_id, 'fluxbb_id', $userdata->id, true );

		return $user_id;
	}

	/**
	 * Duplicate newly registered User by adding a new User to FluxBB. This
	 * function is meant to be triggered by the 'user_register' hook, but
	 * can be used directly to specify the password.
	 * 
	 * We can't access the WordPress Password as is (generated randomly,
	 * see register_new_user() function) so we use a dummy password that
	 * will be update in the next login.
	 * 
	 * If the function is called directly and not through the Plugin API,
	 * a password can be submitted as second parameter and will be added
	 * the the Database instead of Dummy.
	 * 
	 * @since    1.0.0
	 * @see register_new_user()
	 * @link https://codex.wordpress.org/Function_Reference/register_new_user
	 *
	 * @param    array     $user_id    WordPress User ID
	 * @param    string    $user_pass  WordPress User Password (optional)
	 *
	 * @return   boolean   True on success, false on failure.
	 */
	public function wpfluxbb_register_fx_user( $user_id, $user_pass = null ) {

		$user = new WP_User( $user_id );

		if ( ! $this->fluxdb || ! $user->exists() )
			return false;

		if ( is_null( $user_pass ) )
			$user_pass = 'WPFLUXBB';

		$create_user = $this->fluxdb->insert(
			$this->fluxdb->users,
			array(
				'username'        => $user->user_login,
				'email'           => $user->user_email,
				'password'        => $user_pass,
				'language'        => $this->wpfluxbb_o('fluxbb_lang'),
				'group_id'        => 4,
				'registered'      => time(),
				'registration_ip' => time(),
				'last_visit'      => isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d'
			)
		);
		$fluxbb_id = $this->fluxdb->insert_id;

		return $fluxbb_id;
	}

	/**
	 * Update a WordPress User's Password. Replace a dummy password created
	 * on auto-registration by the actual, FluxBB valid password.
	 * 
	 * @since    1.0.0
	 *
	 * @param    int       $fluxbb_id     User's FluxBB ID
	 * @param    string    $user_pass     User's FluxBB password
	 * @param    object    $user          User's FluxBB data
	 *
	 * @return   boolean   True if password was updated, false else.
	 */
	public function wpfluxbb_update_user_password( $fluxbb_id, $user_pass, $user = null ) {

		$wp_user = get_users(
			array(
				'meta_key'   => 'fluxbb_id',
				'meta_value' => (int) $fluxbb_id
			)
		);

		if ( '' == $user_pass || empty( $wp_user ) || 'WPFLUXBB' != $wp_user[0]->data->user_pass )
			return false;

		$change_pass = wp_set_password( $user_pass, $wp_user[0]->ID );

		return true;
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
