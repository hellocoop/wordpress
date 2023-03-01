<?php
/**
 * Plugin Admin settings page class.
 *
 * @package   Hello_Login
 * @category  Settings
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Settings_Page class.
 *
 * Admin settings page.
 *
 * @package Hello_Login
 * @category  Settings
 */
class Hello_Login_Settings_Page {

	/**
	 * Local copy of the settings provided by the base plugin.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private $settings;

	/**
	 * Instance of the client wrapper.
	 *
	 * @var Hello_Login_Client_Wrapper
	 */
	private $client_wrapper;

	/**
	 * Instance of the plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private $logger;

	/**
	 * The controlled list of settings & associated defined during
	 * construction for i18n reasons.
	 *
	 * @var array
	 */
	private $settings_fields = array();

	/**
	 * Options page slug.
	 *
	 * @var string
	 */
	private $options_page_name = 'hello-login-settings';

	/**
	 * Options page settings group name.
	 *
	 * @var string
	 */
	private $settings_field_group;

	/**
	 * Settings page class constructor.
	 *
	 * @param Hello_Login_Option_Settings $settings The plugin settings object.
	 * @param Hello_Login_Option_Logger   $logger   The plugin logging class object.
	 */
	public function __construct( Hello_Login_Option_Settings $settings, Hello_Login_Client_Wrapper $client_wrapper, Hello_Login_Option_Logger $logger ) {

		$this->settings             = $settings;
		$this->client_wrapper		= $client_wrapper;
		$this->logger               = $logger;
		$this->settings_field_group = $this->settings->get_option_name() . '-group';

		$fields = $this->get_settings_fields();

		// Some simple pre-processing.
		foreach ( $fields as $key => &$field ) {
			$field['key']  = $key;
			$field['name'] = $this->settings->get_option_name() . '[' . $key . ']';
		}

		// Allow alterations of the fields.
		$this->settings_fields = $fields;
	}

	/**
	 * Hook the settings page into WordPress.
	 *
	 * @param Hello_Login_Option_Settings $settings       A plugin settings object instance.
	 * @param Hello_Login_Client_Wrapper  $client_wrapper A client object instance.
	 * @param Hello_Login_Option_Logger   $logger         A plugin logger object instance.
	 *
	 * @return void
	 */
	public static function register( Hello_Login_Option_Settings $settings, Hello_Login_Client_Wrapper $client_wrapper, Hello_Login_Option_Logger $logger ) {
		$settings_page = new self( $settings, $client_wrapper, $logger );

		// Add our options page to the admin menu.
		add_action( 'admin_menu', array( $settings_page, 'admin_menu' ) );

		// Register our settings.
		add_action( 'admin_init', array( $settings_page, 'admin_init' ) );

		// Add "Settings" to the plugin in the plugin list
		add_filter( 'plugin_action_links_hello-login/hello-login.php', array( $settings_page, 'hello_login_settings_action' ) );
	}

	public function hello_login_settings_action( $links ) {
		// Build and escape the URL.
		$url = admin_url( '/options-general.php?page=hello-login-settings' );
		// Create the link.
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings' ) );
		// Adds the link to the beginning of the array.
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Implements hook admin_menu to add our options/settings page to the
	 *  dashboard menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Hellō Login', 'hello-login' ),
			__( 'Hellō Login', 'hello-login' ),
			'manage_options',
			$this->options_page_name,
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Implements hook admin_init to register our settings.
	 *
	 * @return void
	 */
	public function admin_init() {
		register_setting(
			$this->settings_field_group,
			$this->settings->get_option_name(),
			array(
				$this,
				'sanitize_settings',
			)
		);

		add_settings_section(
				'user_settings',
				__('User Settings', 'hello-login'),
				array($this, 'user_settings_description'),
				$this->options_page_name
		);

		add_settings_section(
			'client_settings',
			__( 'Client Settings', 'hello-login' ),
			array( $this, 'client_settings_description' ),
			$this->options_page_name
		);

		if ( isset( $_GET['debug'] ) ) {
			add_settings_section(
					'authorization_settings',
					__('Authorization Settings', 'hello-login'),
					array($this, 'authorization_settings_description'),
					$this->options_page_name
			);
		}

		add_settings_section(
			'log_settings',
			__( 'Log Settings', 'hello-login' ),
			array( $this, 'log_settings_description' ),
			$this->options_page_name
		);

		// Preprocess fields and add them to the page.
		foreach ( $this->settings_fields as $key => $field ) {
			// Make sure each key exists in the settings array.
			if ( ! isset( $this->settings->{ $key } ) ) {
				$this->settings->{ $key } = null;
			}

			// Determine appropriate output callback.
			switch ( $field['type'] ) {
				case 'checkbox':
					$callback = 'do_checkbox';
					break;

				case 'select':
					$callback = 'do_select';
					break;

				case 'text':
				default:
					$callback = 'do_text_field';
					break;
			}

			// Add the field.
			add_settings_field(
				$key,
				$field['title'],
				array( $this, $callback ),
				$this->options_page_name,
				$field['section'],
				$field
			);
		}

		$this->add_admin_notices();
	}

	private function add_admin_notices() {
		if ( isset( $_GET['hello-login-msg'] ) && ! empty( $_GET['hello-login-msg'] ) ) {
			$message_id = sanitize_text_field( $_GET['hello-login-msg'] );

			switch ($message_id) {
				case 'quickstart_success':
					add_action( 'admin_notices', array( $this, 'admin_notice_quickstart_success' ) );
					break;
				case 'quickstart_existing_client_id':
					add_action( 'admin_notices', array( $this, 'admin_notice_quickstart_existing_client_id' ) );
					break;
				case 'quickstart_missing_client_id':
					add_action( 'admin_notices', array( $this, 'admin_notice_quickstart_missing_client_id' ) );
					break;
				case 'unlink_success':
					add_action( 'admin_notices', array( $this, 'admin_notice_unlink_success' ) );
					break;
				case 'unlink_no_session':
					add_action( 'admin_notices', array( $this, 'admin_notice_unlink_no_session' ) );
					break;
				case 'unlink_not_linked':
					add_action( 'admin_notices', array( $this, 'admin_notice_unlink_not_linked' ) );
					break;
				case 'link_success':
					add_action( 'admin_notices', array( $this, 'admin_notice_link_success' ) );
					break;
				default:
					$this->logger->log( 'Unknown message id: ' . $message_id, 'admin_notices' );
					add_action( 'admin_notices', array( $this, 'admin_notice_quickstart_unknown' ) );
			}
		}
	}

	/**
	 * Show admin notice for successful Quickstart.
	 *
	 * @return void
	 */
	public function admin_notice_quickstart_success() {
		$site_name = get_bloginfo( 'name' );
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( "Quickstart has successfully registered your site \"$site_name\" at Hellō", 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for failed Quickstart because the client id is already set.
	 *
	 * @return void
	 */
	public function admin_notice_quickstart_existing_client_id() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Quickstart failed: client id already set', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for failed Quickstart because the client id is missing.
	 *
	 * @return void
	 */
	public function admin_notice_quickstart_missing_client_id() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Quickstart failed: missing or invalid client id', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for failed Quickstart, unknown message id.
	 *
	 * @return void
	 */
	public function admin_notice_quickstart_unknown() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Quickstart failed: unknown', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for successful unlink.
	 *
	 * @return void
	 */
	public function admin_notice_unlink_success() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( "This account has been unlinked with Hellō", 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for failed unlink because no current user was found.
	 *
	 * @return void
	 */
	public function admin_notice_unlink_no_session() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Unlink failed: no current user was found', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for failed unlink because current user is not linked.
	 *
	 * @return void
	 */
	public function admin_notice_unlink_not_linked() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Unlink failed: current user not linked', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show admin notice for successful link.
	 *
	 * @return void
	 */
	public function admin_notice_link_success() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( "This account has been linked with Hellō", 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get the plugin settings fields definition.
	 *
	 * @return array
	 */
	private function get_settings_fields() {

		/**
		 * Simple settings fields have:
		 *
		 * - title
		 * - description
		 * - type ( checkbox | text | select )
		 * - section - settings/option page section ( client_settings | authorization_settings )
		 * - example (optional example will appear beneath description and be wrapped in <code>)
		 */
		$fields = array(
			/*
			'login_type'        => array(
				'title'       => __( 'Login Type', 'hello-login' ),
				'description' => __( 'Select how the client (login form) should provide login options.', 'hello-login' ),
				'type'        => 'select',
				'options'     => array(
					'button' => __( 'OpenID Connect button on login form', 'hello-login' ),
					'auto'   => __( 'Auto Login - SSO', 'hello-login' ),
				),
				'disabled'    => defined( 'OIDC_LOGIN_TYPE' ),
				'section'     => 'client_settings',
			),
			*/
			'scope'             => array(
				'title'       => __( 'Scopes', 'hello-login' ),
				'description' => __( 'Scopes to request in addition to <code>openid email name</code>. See <a href="https://www.hello.dev/documentation/hello-claims.html" target="_blank">https://www.hello.dev/documentation/hello-claims.html</a> for available scopes.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_CLIENT_SCOPE' ),
				'section'     => 'client_settings',
			),
			'client_id'         => array(
				'title'       => __( 'Client ID', 'hello-login' ),
				'description' => __( 'The client identifier provided by Hellō and set by Quickstart.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_CLIENT_ID' ),
				'section'     => 'client_settings',
			),
			'redirect_uri'         => array(
				'title'       => __( 'Redirect URI', 'hello-login' ),
				'description' => __( 'The endpoint used to receive authentication data.', 'hello-login' ),
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			/*
			'client_secret'     => array(
				'title'       => __( 'Client Secret Key', 'hello-login' ),
				'description' => __( 'Arbitrary secret key the server expects from this client. Can be anything, but should be very unique.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_CLIENT_SECRET' ),
				'section'     => 'client_settings',
			),
			*/
			/*
			'endpoint_login'    => array(
				'title'       => __( 'Login Endpoint URL', 'hello-login' ),
				'description' => __( 'Identify provider authorization endpoint.', 'hello-login' ),
				'example'     => 'https://example.com/oauth2/authorize',
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_ENDPOINT_LOGIN_URL' ),
				'section'     => 'client_settings',
			),
			'endpoint_userinfo' => array(
				'title'       => __( 'Userinfo Endpoint URL', 'hello-login' ),
				'description' => __( 'Identify provider User information endpoint.', 'hello-login' ),
				'example'     => 'https://example.com/oauth2/UserInfo',
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_ENDPOINT_USERINFO_URL' ),
				'section'     => 'client_settings',
			),
			'endpoint_token'    => array(
				'title'       => __( 'Token Validation Endpoint URL', 'hello-login' ),
				'description' => __( 'Identify provider token endpoint.', 'hello-login' ),
				'example'     => 'https://example.com/oauth2/token',
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_ENDPOINT_TOKEN_URL' ),
				'section'     => 'client_settings',
			),
			'endpoint_end_session'    => array(
				'title'       => __( 'End Session Endpoint URL', 'hello-login' ),
				'description' => __( 'Identify provider logout endpoint.', 'hello-login' ),
				'example'     => 'https://example.com/oauth2/logout',
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_ENDPOINT_LOGOUT_URL' ),
				'section'     => 'client_settings',
			),
			'acr_values'    => array(
				'title'       => __( 'ACR values', 'hello-login' ),
				'description' => __( 'Use a specific defined authentication contract from the IDP - optional.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'OIDC_ACR_VALUES' ),
				'section'     => 'client_settings',
			),
			'enable_pkce' => array(
				'title'       => __( 'Enable PKCE support', 'hello-login' ),
				'description' => __( 'If checked, add PKCE challenge during authentication requests.', 'hello-login' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'OIDC_ENABLE_PKCE' ),
				'section'     => 'client_settings',
			),
			'identity_key'     => array(
				'title'       => __( 'Identity Key', 'hello-login' ),
				'description' => __( 'Where in the user claim array to find the user\'s identification data. Possible standard values: preferred_username, name, or sub. If you\'re having trouble, use "sub".', 'hello-login' ),
				'example'     => 'preferred_username',
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			'no_sslverify'      => array(
				'title'       => __( 'Disable SSL Verify', 'hello-login' ),
				// translators: %1$s HTML tags for layout/styles, %2$s closing HTML tag for styles.
				'description' => sprintf( __( 'Do not require SSL verification during authorization. The OAuth extension uses curl to make the request. By default CURL will generally verify the SSL certificate to see if its valid an issued by an accepted CA. This setting disabled that verification.%1$sNot recommended for production sites.%2$s', 'hello-login' ), '<br><strong>', '</strong>' ),
				'type'        => 'checkbox',
				'section'     => 'client_settings',
			),
			'http_request_timeout'      => array(
				'title'       => __( 'HTTP Request Timeout', 'hello-login' ),
				'description' => __( 'Set the timeout for requests made to the IDP. Default value is 5.', 'hello-login' ),
				'example'     => 30,
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			'nickname_key'     => array(
				'title'       => __( 'Nickname Key', 'hello-login' ),
				'description' => __( 'Where in the user claim array to find the user\'s nickname. Possible standard values: preferred_username, name, or sub.', 'hello-login' ),
				'example'     => 'preferred_username',
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			'email_format'     => array(
				'title'       => __( 'Email Formatting', 'hello-login' ),
				'description' => __( 'String from which the user\'s email address is built. Specify "{email}" as long as the user claim contains an email claim.', 'hello-login' ),
				'example'     => '{email}',
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			'displayname_format'     => array(
				'title'       => __( 'Display Name Formatting', 'hello-login' ),
				'description' => __( 'String from which the user\'s display name is built.', 'hello-login' ),
				'example'     => '{given_name} {family_name}',
				'type'        => 'text',
				'section'     => 'client_settings',
			),
			'identify_with_username'     => array(
				'title'       => __( 'Identify with User Name', 'hello-login' ),
				'description' => __( 'If checked, the user\'s identity will be determined by the user name instead of the email address.', 'hello-login' ),
				'type'        => 'checkbox',
				'section'     => 'client_settings',
			),
			'state_time_limit'     => array(
				'title'       => __( 'State time limit', 'hello-login' ),
				'description' => __( 'State valid time in seconds. Defaults to 180', 'hello-login' ),
				'type'        => 'number',
				'section'     => 'client_settings',
			),
			'token_refresh_enable'   => array(
				'title'       => __( 'Enable Refresh Token', 'hello-login' ),
				'description' => __( 'If checked, support refresh tokens used to obtain access tokens from supported IDPs.', 'hello-login' ),
				'type'        => 'checkbox',
				'section'     => 'client_settings',
			),
			*/
			'enable_logging'    => array(
				'title'       => __( 'Enable Logging', 'hello-login' ),
				'description' => __( 'Very simple log messages for debugging purposes.', 'hello-login' ),
				'type'        => 'checkbox',
				'section'     => 'log_settings',
			),
			'log_limit'         => array(
				'title'       => __( 'Log Limit', 'hello-login' ),
				'description' => __( 'Number of items to keep in the log. These logs are stored as an option in the database, so space is limited.', 'hello-login' ),
				'type'        => 'number',
				'section'     => 'log_settings',
			),
		);

		$fields['link_existing_users'] = array(
				'title'       => __( 'Link Existing Users via Email', 'hello-login' ),
				'description' => __( 'If a newly-authenticated Hellō user does not have an account, and a WordPress account already exists with the same email, link the user to that Wordpress account.', 'hello-login' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'OIDC_LINK_EXISTING_USERS' ),
				'section'     => 'user_settings',
		);
		$fields['create_if_does_not_exist'] = array(
				'title'       => __( 'Allow anyone to register with Hellō', 'hello-login' ),
				'description' => __( 'Create a new user if they do not have an account. Authentication will fail if the user does not have an account and this is disabled.', 'hello-login' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'OIDC_CREATE_IF_DOES_NOT_EXIST' ),
				'section'     => 'user_settings',
		);

		if ( isset( $_GET['debug'] ) ) {
			$fields['enforce_privacy'] = array(
					'title'       => __( 'Enforce Privacy', 'hello-login' ),
					'description' => __( 'Require users be logged in to see the site.', 'hello-login' ),
					'type'        => 'checkbox',
					'disabled'    => defined( 'OIDC_ENFORCE_PRIVACY' ),
					'section'     => 'authorization_settings',
			);
			$fields['redirect_user_back'] = array(
					'title'       => __( 'Redirect Back to Origin Page', 'hello-login' ),
					'description' => __( 'After a successful authentication, this will redirect the user back to the page on which they clicked the Hellō login button. This will cause the login process to proceed in a traditional WordPress fashion. For example, users logging in through the default wp-login.php page would end up on the WordPress Dashboard and users logging in through the WooCommerce "My Account" page would end up on their account page.', 'hello-login' ),
					'type'        => 'checkbox',
					'disabled'    => defined( 'OIDC_REDIRECT_USER_BACK' ),
					'section'     => 'user_settings',
			);

			$fields['redirect_on_logout'] = array(
					'title' => __('Redirect to the login screen when session is expired', 'hello-login'),
					'description' => __('When enabled, this will automatically redirect the user back to the WordPress login page if their access token has expired.', 'hello-login'),
					'type' => 'checkbox',
					'disabled' => defined('OIDC_REDIRECT_ON_LOGOUT'),
					'section' => 'user_settings',
			);
		}

		$fields['link_not_now'] = array(
				'title' => 'Link Not Now',
				'type' => 'checkbox',
				'section' => 'hidden_settings',
		);

		return apply_filters( 'hello-login-settings-fields', $fields );

	}

	/**
	 * Sanitization callback for settings/option page.
	 *
	 * @param array $input The submitted settings values.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$options = array();

		// Loop through settings fields to control what we're saving.
		foreach ( $this->settings_fields as $key => $field ) {
			if ( isset( $input[ $key ] ) ) {
				$options[ $key ] = sanitize_text_field( trim( $input[ $key ] ) );

				if ( 'scope' == $key ) {
					$no_duplicate_scope = Hello_Login::remove_duplicate_scopes( $options[ $key ] );

					if ( strlen( $no_duplicate_scope ) < strlen( $options[ $key ] ) ) {
						$options[ $key ] = $no_duplicate_scope;
						add_settings_error( 'scope', 'scope_no_duplicate', 'Duplicate scopes removed.', 'warning' );
					}

					$no_defaults_scope = Hello_Login::remove_default_scopes( $options[ $key ] );

					if ( strlen( $no_defaults_scope ) < strlen( $options[ $key ] ) ) {
						$options[ $key ] = $no_defaults_scope;
						add_settings_error( 'scope', 'scope_no_default', 'Default scopes removed.', 'warning' );
					}
				}
			} else {
				$options[ $key ] = '';
			}
		}

		return $options;
	}

	/**
	 * Output the options/settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		wp_enqueue_style( 'hello-login-admin', plugin_dir_url( __DIR__ ) . 'css/styles-admin.css', array(), Hello_Login::VERSION, 'all' );

		$redirect_uri = site_url( '?hello-login=callback' );
		$quickstart_uri = site_url( '?hello-login=quickstart' );
		$settings_page_not_now_url = admin_url( '/options-general.php?page=hello-login-settings&link_not_now=1' );

		$custom_logo_url = '';
		if ( has_custom_logo() ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$custom_logo_data = wp_get_attachment_image_src( $custom_logo_id , 'full' );
			$custom_logo_url = $custom_logo_data[0];
		}

		$redirect_to_path = Hello_Login::extract_path_and_query( admin_url( '/options-general.php?page=hello-login-settings' ) );
		$start_url = create_auth_request_start_url( $redirect_to_path );

		$debug = isset( $_GET['debug'] );
		$configured = ! empty( $this->settings->client_id );

		$link_not_now = ( 1 == $this->settings->link_not_now );
		if ( isset( $_GET['link_not_now'] ) ) {
			if ( '1' == $_GET['link_not_now'] ) {
				$link_not_now = true;
				$this->settings->link_not_now = 1;
				$this->settings->save();
			} else {
				$link_not_now = false;
				$this->settings->link_not_now = 0;
				$this->settings->save();
			}
		}
		?>
		<div class="wrap">
			<h2><?php print esc_html( get_admin_page_title() ); ?></h2>

			<?php if ( ! $configured ) { ?>
			<h2>To use Hellō, you must configure your site. Hellō Quickstart will get you up and running in seconds. You will create a Hellō Wallet if you don't have one already.</h2>

			<form method="get" action="https://quickstart.hello.coop/">
				<input type="hidden" name="integration" id="integration" value="wordpress" />
				<input type="hidden" name="response_uri" id="response_uri" value="<?php print esc_attr( $quickstart_uri ); ?>" />
				<input type="hidden" name="name" id="name" value="<?php print esc_attr( get_bloginfo( 'name' ) ); ?>" />
				<input type="hidden" name="pp_uri" id="pp_uri" value="<?php print esc_attr( get_privacy_policy_url() ); ?>" />
				<input type="hidden" name="image_uri" id="image_uri" value="<?php print esc_attr( $custom_logo_url ); ?>" />
				<input type="hidden" name="redirect_uri" id="redirect_uri" value="<?php print esc_attr( $redirect_uri ); ?>" />
				<input type="submit" id="hello_quickstart" class="hello-btn" value="ō&nbsp;&nbsp;&nbsp;Configure your site with Hellō Quickstart" />
			</form>

			<?php } ?>

			<?php if ( $configured || $debug ) { ?>
				<?php if ( empty( get_user_meta( get_current_user_id(), 'hello-login-subject-identity', true ) ) && ! $link_not_now && ! is_multisite() ) { ?>
					<h2>You are logged into this account with a username and password. Link this account with Hellō to login with Hellō in the future.</h2>
					<button class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Link this account with Hellō" onclick="parent.location='<?php print esc_js( $start_url ); ?>'"></button>
					<a href="<?php print esc_attr( $settings_page_not_now_url ); ?>" class="hello-link-not-now">Not Now</a>
				<?php } else { ?>
					<h2>Use the <a href="https://console.hello.coop/?client_id=<?php print rawurlencode( $this->settings->client_id ); ?>" target="_blank">Hellō Console</a> to update the name, images, terms of service, and privacy policy displayed by Hellō when logging in.</h2>

					<h2>Hellō Button</h2>
					<p>The Hellō Button has been added to the /wp-login.php page. You can add a "Continue with Hellō" button to other pages with the shortcode <code>[hello_login_button]</code>. Block support coming soon!
					</p>
					<form method="post" action="options.php">
						<?php
						settings_fields( $this->settings_field_group );
						do_settings_sections( $this->options_page_name );
						submit_button();
						?>
					</form>
				<?php } ?>
			<?php } ?>

			<?php if ( $debug ) { ?>
				<h4>Debug</h4>

				<p>Hellō user id: <code><?php print esc_html( get_user_meta( get_current_user_id(), 'hello-login-subject-identity', true ) ); ?></code></p>

				<p>Settings:</p>
				<pre>
				<?php var_dump( $this->settings->get_values() ); ?>
				</pre>
			}
			<?php } ?>

			<?php if ( $this->settings->enable_logging ) { ?>
				<h2><?php esc_html_e( 'Logs', 'hello-login' ); ?></h2>
				<div id="logger-table-wrapper">
					<?php print wp_kses_post( $this->logger->get_logs_table() ); ?>
				</div>

			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Output a standard text field.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_text_field( $field ) {
		$disabled = ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true;
		$value = $this->settings->{ $field['key'] };

		$readonly = '';
		if ( $field['key'] == 'client_id' ) {
			$readonly = 'readonly';
		}
		if ( $field['key'] == 'redirect_uri' ) {
			$readonly = 'readonly';
			$value = site_url( '?hello-login=callback' );
		}
		?>
		<input type="<?php print esc_attr( $field['type'] ); ?>" <?php print esc_attr( $readonly ); ?>
				<?php echo ( $disabled ? ' disabled' : '' ); ?>
			  id="<?php print esc_attr( $field['key'] ); ?>"
			  class="large-text<?php echo ( $disabled ? ' disabled' : '' ); ?>"
			  name="<?php print esc_attr( $field['name'] ); ?>"
			  value="<?php print esc_attr( $value ); ?>">
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output a checkbox for a boolean setting.
	 *  - hidden field is default value so we don't have to check isset() on save.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_checkbox( $field ) {
		?>
		<input type="hidden" name="<?php print esc_attr( $field['name'] ); ?>" value="0">
		<input type="checkbox"
			   id="<?php print esc_attr( $field['key'] ); ?>"
			   name="<?php print esc_attr( $field['name'] ); ?>"
			   value="1"
			<?php checked( $this->settings->{ $field['key'] }, 1 ); ?>>
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output a select control.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_select( $field ) {
		$current_value = isset( $this->settings->{ $field['key'] } ) ? $this->settings->{ $field['key'] } : '';
		?>
		<select name="<?php print esc_attr( $field['name'] ); ?>">
			<?php foreach ( $field['options'] as $value => $text ) : ?>
				<option value="<?php print esc_attr( $value ); ?>" <?php selected( $value, $current_value ); ?>><?php print esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->do_field_description( $field );
	}

	/**
	 * Output the field description, and example if present.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_field_description( $field ) {
		?>
		<p class="description">
			<?php print wp_kses_post( $field['description'] ); ?>
			<?php if ( isset( $field['example'] ) ) : ?>
				<br/><strong><?php esc_html_e( 'Example', 'hello-login' ); ?>: </strong>
				<code><?php print esc_html( $field['example'] ); ?></code>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Output the 'Client Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function client_settings_description() {
		esc_html_e( 'Enter your Hellō settings.', 'hello-login' );
	}

	/**
	 * Output the 'WordPress User Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function user_settings_description() {
		esc_html_e( 'Modify the interaction between Hellō Login and WordPress users.', 'hello-login' );
	}

	/**
	 * Output the 'Authorization Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function authorization_settings_description() {
		esc_html_e( 'Control the authorization mechanics of the site.', 'hello-login' );
	}

	/**
	 * Output the 'Log Settings' plugin setting section description.
	 *
	 * @return void
	 */
	public function log_settings_description() {
		esc_html_e( 'Log information about login attempts through Hellō Login.', 'hello-login' );
	}
}
