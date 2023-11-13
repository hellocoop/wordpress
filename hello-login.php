<?php
/**
 * Hellō Login
 *
 * This plugin provides the ability to authenticate users with Identity
 * Providers using the OpenID Connect OAuth2 API with Authorization Code Flow.
 *
 * @package   Hello_Login
 * @category  General
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2022 Hello Identity Co-op
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://www.hello.dev/
 *
 * @wordpress-plugin
 * Plugin Name:       Hellō Login
 * Plugin URI:        https://github.com/hellocoop/wordpress
 * Description:       Free and simple to setup plugin provides registration and login with the Hellō Wallet. Users choose from popular social login, email, or phone.
 * Version:           1.5.4
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Author:            hellocoop
 * Author URI:        http://www.hello.coop
 * Text Domain:       hello-login
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/hellocoop/wordpress
 */

/*
Notes
  Filters
  - hello-login-user-login-test                         - (bool) should the user be logged in based on their claim
  - hello-login-user-creation-test                      - (bool) should the user be created based on their claim
  - hello-login-alter-user-data                         - modify user data before a new user is created

  Actions
  - hello-login-user-create                     - 1 arg: user, fires when a new user is created by this plugin
  - hello-login-user-update                     - 1 arg: user ID, fires when user is updated by this plugin
  - hello-login-update-user-using-current-claim - 2 args: fires every time an existing user logs in and the claims are updated.
  - hello-login-redirect-user-back              - 2 args: redirect_url, $user. Allows interruption of redirect during login.
  - hello-login-user-logged-in                  - 1 arg: user, fires when user is logged in.
  - hello-login-cron-daily                      - daily cron action

  User Metadata
  - hello-login-subject-identity    - the identity of the user provided by the idp
  - hello-login-last-token          - the user's most recent Id Token or other JWP, encoded
  - hello-login-invite_created      - the Hellō invite event payload as a JSON string based on which the user account was created, linked or promoted.

  Options
  - hello_login_settings - plugin settings
*/


/**
 * Hello_Login class.
 *
 * Defines plugin initialization functionality.
 *
 * @package Hello_Login
 * @category  General
 */
class Hello_Login {

	/**
	 * Singleton instance of self
	 *
	 * @var Hello_Login
	 */
	protected static Hello_Login $_instance;

	/**
	 * Singleton to signal that the class was bootstrapped.
	 *
	 * @var bool
	 */
	private static bool $_bootstrapped = false;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.5.4';

	/**
	 * Plugin option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'hello_login_settings';

	/**
	 * Plugin logs option name.
	 *
	 * @var string
	 */
	const LOGS_OPTION_NAME = 'hello-login-logs';

	/**
	 * Federation groups option name.
	 *
	 * @var string
	 */
	const FEDERATION_GROUPS_OPTION_NAME = 'hello_login_federation_groups';

	/**
	 * Plugin settings.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private Hello_Login_Option_Settings $settings;

	/**
	 * Plugin logs.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * Client wrapper.
	 *
	 * @var Hello_Login_Client_Wrapper
	 */
	public Hello_Login_Client_Wrapper $client_wrapper;

	/**
	 * Hello invites.
	 *
	 * @var Hello_Login_Events
	 */
	public Hello_Login_Events $invites;

	/**
	 * Setup the plugin
	 *
	 * @param Hello_Login_Option_Settings $settings The settings object.
	 * @param Hello_Login_Option_Logger   $logger   The loggin object.
	 *
	 * @return void
	 */
	public function __construct( Hello_Login_Option_Settings $settings, Hello_Login_Option_Logger $logger ) {
		$this->settings = $settings;
		$this->logger = $logger;
		self::$_instance = $this;
	}

	/**
	 * WordPress Hook 'init'.
	 *
	 * @return void
	 */
	public function init() {

		$redirect_uri = site_url( '?hello-login=callback' );

		$state_time_limit = 600;
		if ( $this->settings->state_time_limit ) {
			$state_time_limit = $this->settings->state_time_limit;
		}

		$http_request_timeout = 5;
		if ( $this->settings->http_request_timeout ) {
			$http_request_timeout = $this->settings->http_request_timeout;
		}

		$client = new Hello_Login_Client(
			$this->settings->client_id,
			Hello_Login_Util::add_default_scopes( $this->settings->scope ),
			$this->settings->endpoint_token,
			$redirect_uri,
			$state_time_limit,
			$http_request_timeout,
			$this->logger
		);

		$users = new Hello_Login_Users( $this->logger, $this->settings );

		$this->client_wrapper = Hello_Login_Client_Wrapper::register( $client, $this->settings, $this->logger, $users );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		Hello_Login_Login_Form::register( $this->settings, $this->client_wrapper );

		// Add a shortcode to get the auth URL.
		add_shortcode( 'hello_login_auth_url', array( $this->client_wrapper, 'get_authentication_url' ) );

		// Add actions to our scheduled cron jobs.
		add_action( 'hello-login-cron-daily', array( $this, 'cron_states_garbage_collection' ) );

		$this->upgrade();

		if ( is_admin() ) {
			Hello_Login_Settings_Page::register( $this->settings, $this->logger );
		}

		$this->invites = Hello_Login_Events::register( $this->logger, $this->settings, $users );

		if ( ! empty( $this->settings->client_id ) ) {
			add_action( 'show_user_profile', array( $this, 'hello_login_user_profile_self' ) );
			add_action( 'edit_user_profile', array( $this, 'hello_login_user_profile_other' ) );
		}

		add_rewrite_tag( '%hello-login%', '([a-z]+)' );
		add_action( 'parse_request', array( $this, 'route_hello_login_request' ) );

		// Add "Settings" to the plugin in the plugin list.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'hello_login_plugin_action_links' ) );
		// TODO: consider adding filter for network_admin_plugin_action_links_... as well.
	}

	/**
	 * Add a link to the plugin settings page in the plugin list.
	 *
	 * @param array $links Existing list of plugin links.
	 *
	 * @return array The expanded list of links.
	 */
	public function hello_login_plugin_action_links( array $links ): array {
		// Build and escape the URL.
		$url = admin_url( '/options-general.php?page=hello-login-settings' );
		// Create the link.
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'hello-login' ) );
		// Adds the link to the beginning of the array.
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Implements WordPress parse_request action.
	 *
	 * @param WP $wp The WordPress environment instance.
	 */
	public function route_hello_login_request( WP $wp ) {
		if ( isset( $wp->query_vars['hello-login'] ) ) {
			switch ( $wp->query_vars['hello-login'] ) {
				case 'callback':
					$this->client_wrapper->authentication_request_callback();
					exit;
				case 'unlink':
					$this->client_wrapper->unlink_hello();
					exit;
				case 'quickstart':
					$this->client_wrapper->quickstart_callback();
					exit;
				case 'start':
					$this->client_wrapper->start_auth();
					exit;
				case 'event':
					$this->invites->handle_event();
					exit;
			}
		}
	}

	/**
	 * Show Hellō account linking controls on the user's own profile page.
	 *
	 * @param WP_User $profileuser The user whose profile is being edited.
	 * @return void
	 */
	public function hello_login_user_profile_self( WP_User $profileuser ) {
		$link_url = create_auth_request_start_url( Hello_Login_Util::extract_path_and_query( get_edit_user_link( $profileuser->ID ) ) );
		$update_email_url = create_auth_request_start_url( Hello_Login_Util::extract_path_and_query( get_edit_user_link( $profileuser->ID ) ), 'update_email' );
		$hello_user_id = Hello_Login_Users::get_hello_sub( $profileuser->ID );
		$unlink_url = wp_nonce_url( site_url( '?hello-login=unlink' ), 'unlink' . $profileuser->ID );
		?>
		<h2>Hellō</h2>
		<table class="form-table">
			<tr>
				<th>This Account</th>
				<td>
					<?php if ( empty( $hello_user_id ) ) { ?>
						<button type="button" class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Link with Hellō" onclick="parent.location='<?php print esc_js( $link_url ); ?>'"></button>
					<?php } else { ?>
						<button type="button" class="button" onclick="parent.location='<?php print esc_js( $unlink_url ); ?>'">ō&nbsp;&nbsp;&nbsp;Unlink from Hellō</button>
					<?php } ?>
				</td>
			</tr>
			<?php if ( ! empty( $hello_user_id ) ) { ?>
			<tr id="hello-update-email">
				<th>Email</th>
				<td>
					<div id="hello-email"><?php print esc_html( $profileuser->user_email ); ?></div>
					<div>
						<button type="button" class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Update Email with Hellō" onclick="parent.location='<?php print esc_js( $update_email_url ); ?>'"></button>
					</div>
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php
	}

	/**
	 * Show Hellō account linking controls on a user's profile page when edited by an admin.
	 *
	 * @param WP_User $profileuser The user whose profile is being edited.
	 * @return void
	 */
	public function hello_login_user_profile_other( WP_User $profileuser ) {
		$hello_user_id = Hello_Login_Users::get_hello_sub( $profileuser );
		$unlink_url = wp_nonce_url( site_url( '?hello-login=unlink&user_id=' . $profileuser->ID ), 'unlink' . $profileuser->ID );
		?>
		<h2>Hellō</h2>
		<table class="form-table">
			<tr>
				<th>This Account</th>
				<td>
					<?php if ( empty( $hello_user_id ) ) { ?>
						<p>Not linked with Hellō</p>
					<?php } else { ?>
						<button type="button" class="button" onclick="parent.location='<?php print esc_js( $unlink_url ); ?>'">ō&nbsp;&nbsp;&nbsp;Unlink from Hellō</button>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Append the Hellō Login signature to the user-agent string when the target URL is a Hellō API endpoint.
	 *
	 * @param string $user_agent The default WordPress user-agent string.
	 * @param string $url The target URL.
	 *
	 * @return string The augmented user-agent string.
	 */
	public function user_agent_hook( string $user_agent, string $url ): string {
		$target_host = parse_url( $url, PHP_URL_HOST );
		$token_endpoint_host = parse_url( $this->settings->endpoint_token, PHP_URL_HOST );

		if ( $target_host == $token_endpoint_host ) {
			$user_agent .= ';HelloLogin/' . self::VERSION;
		}

		return $user_agent;
	}

	/**
	 * Handle plugin upgrades
	 *
	 * @return void
	 */
	public function upgrade() {
		$last_version = get_option( 'hello-login-plugin-version', 0 );

		if ( version_compare( self::VERSION, $last_version, '>' ) ) {
			// An upgrade is required.
			self::setup_cron_jobs();

			// Update the stored version number.
			update_option( 'hello-login-plugin-version', self::VERSION );
		}
	}

	/**
	 * Expire state transients by attempting to access them and allowing the
	 * transient's own mechanisms to delete any that have expired.
	 *
	 * @return void
	 */
	public function cron_states_garbage_collection() {
		global $wpdb;
		$states = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_hello-login-state--%'" );

		if ( ! empty( $states ) ) {
			foreach ( $states as $state ) {
				$transient = str_replace( '_transient_', '', $state );
				get_transient( $transient );
			}
		}
	}

	/**
	 * Ensure cron jobs are added to the schedule.
	 *
	 * @return void
	 */
	public static function setup_cron_jobs() {
		if ( ! wp_next_scheduled( 'hello-login-cron-daily' ) ) {
			wp_schedule_event( time(), 'daily', 'hello-login-cron-daily' );
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activation() {
		self::setup_cron_jobs();
	}

	/**
	 * Redirect after plugin Activation hook.
	 *
	 * @param string $plugin The slug of the plugin being activated.
	 *
	 * @return void
	 */
	public static function activation_redirect( string $plugin ) {
		if ( plugin_basename( __FILE__ ) == $plugin ) {
			wp_redirect( admin_url( '/options-general.php?page=hello-login-settings' ) );
			exit();
		}
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'hello-login-cron-daily' );
	}

	/**
	 * Uninstall hook.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( self::OPTION_NAME );
		delete_option( self::LOGS_OPTION_NAME );
		delete_option( 'hello_login_permalinks_flushed' );
	}

	/**
	 * Simple autoloader.
	 *
	 * @param string $class The class name.
	 *
	 * @return void
	 */
	public static function autoload( string $class ) {
		$prefix = 'Hello_Login_';

		if ( stripos( $class, $prefix ) !== 0 ) {
			return;
		}

		$filename = $class . '.php';

		$filepath = dirname( __FILE__ ) . '/includes/' . $filename;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		} else {
			// Some internal files are all lowercase and use dashes in filenames.
			if ( false === strpos( $filename, '\\' ) ) {
				$filename = strtolower( str_replace( '_', '-', $filename ) );
			} else {
				$filename = str_replace( '\\', DIRECTORY_SEPARATOR, $filename );
			}

			$filepath = dirname( __FILE__ ) . '/includes/' . $filename;

			if ( file_exists( $filepath ) ) {
				require_once $filepath;
			}
		}
	}

	/**
	 * Instantiate the plugin and hook into WordPress.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		if ( self::$_bootstrapped ) {
			return;
		}

		self::$_bootstrapped = true;

		/**
		 * This is a documented valid call for spl_autoload_register.
		 *
		 * @link https://www.php.net/manual/en/function.spl-autoload-register.php#71155
		 */
		spl_autoload_register( array( 'Hello_Login', 'autoload' ) );

		$settings = new Hello_Login_Option_Settings(
			self::OPTION_NAME,
			// Default settings values.
			array(
				// OAuth client settings.
				'client_id'            => defined( 'HELLO_LOGIN_CLIENT_ID' ) ? HELLO_LOGIN_CLIENT_ID : '',
				'scope'                => defined( 'HELLO_LOGIN_CLIENT_SCOPE' ) ? HELLO_LOGIN_CLIENT_SCOPE : '',
				'endpoint_login'       => defined( 'HELLO_LOGIN_ENDPOINT_LOGIN_URL' ) ? HELLO_LOGIN_ENDPOINT_LOGIN_URL : 'https://wallet.hello.coop/authorize',
				'endpoint_token'       => defined( 'HELLO_LOGIN_ENDPOINT_TOKEN_URL' ) ? HELLO_LOGIN_ENDPOINT_TOKEN_URL : 'https://wallet.hello.coop/oauth/token',
				'endpoint_quickstart'  => defined( 'HELLO_LOGIN_ENDPOINT_QUICKSTART_URL' ) ? HELLO_LOGIN_ENDPOINT_QUICKSTART_URL : 'https://quickstart.hello.coop/',
				'endpoint_invite'      => defined( 'HELLO_LOGIN_ENDPOINT_INVITE_URL' ) ? HELLO_LOGIN_ENDPOINT_INVITE_URL : 'https://wallet.hello.coop/invite',
				'endpoint_introspect'  => defined( 'HELLO_LOGIN_ENDPOINT_INTROSPECT_URL' ) ? HELLO_LOGIN_ENDPOINT_INTROSPECT_URL : 'https://wallet.hello.coop/oauth/introspect',

				// Non-standard settings.
				'http_request_timeout' => 5,
				'displayname_format' => '{name}',

				// Plugin settings.
				'link_existing_users'      => defined( 'HELLO_LOGIN_LINK_EXISTING_USERS' ) ? HELLO_LOGIN_LINK_EXISTING_USERS : 1,
				'create_if_does_not_exist' => defined( 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST' ) ? HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST : 1,
				'redirect_user_back'       => defined( 'HELLO_LOGIN_REDIRECT_USER_BACK' ) ? HELLO_LOGIN_REDIRECT_USER_BACK : 1,
				'enable_logging'           => 0,
				'log_limit'                => 1000,
				'link_not_now'             => 0,
				'provider_hint'            => '',
			)
		);

		$logger = new Hello_Login_Option_Logger( self::LOGS_OPTION_NAME, 'error', $settings->enable_logging, $settings->log_limit );

		$plugin = new self( $settings, $logger );

		add_action( 'init', array( $plugin, 'init' ) );

		// User-Agent hook.
		add_filter( 'http_headers_useragent', array( $plugin, 'user_agent_hook' ), 0, 2 );
	}
}

Hello_Login::bootstrap();

register_activation_hook( __FILE__, array( 'Hello_Login', 'activation' ) );
add_action( 'activated_plugin', array( 'Hello_Login', 'activation_redirect' ) );
register_deactivation_hook( __FILE__, array( 'Hello_Login', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Hello_Login', 'uninstall' ) );

// Provide publicly accessible plugin helper functions.
require_once( 'includes/functions.php' );
