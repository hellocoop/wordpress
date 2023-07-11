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
	private Hello_Login_Option_Settings $settings;

	/**
	 * Instance of the plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * The controlled list of settings & associated defined during
	 * construction for i18n reasons.
	 *
	 * @var array
	 */
	private array $settings_fields;

	/**
	 * Options page slug, general tab.
	 *
	 * @var string
	 */
	private string $options_page_name = 'hello-login-settings';

	/**
	 * Options page slug, federation tab.
	 *
	 * @var string
	 */
	private string $federation_options_page_name = 'hello-login-federation-settings';

	/**
	 * Options page slug, advanced.
	 *
	 * @var string
	 */
	private string $advanced_options_page_name = 'hello-login-advanced-settings';

	/**
	 * Options page settings group name.
	 *
	 * @var string
	 */
	private string $settings_field_group;

	/**
	 * Federation groups logic.
	 *
	 * @var Hello_Login_Federation_Groups
	 */
	private Hello_Login_Federation_Groups $federation_groups;

	/**
	 * Settings page class constructor.
	 *
	 * @param Hello_Login_Option_Settings $settings The plugin settings object.
	 * @param Hello_Login_Option_Logger   $logger   The plugin logging class object.
	 */
	public function __construct( Hello_Login_Option_Settings $settings, Hello_Login_Option_Logger $logger ) {
		$this->settings             = $settings;
		$this->logger               = $logger;
		$this->settings_field_group = $this->settings->get_option_name() . '-group';

		$this->federation_groups = new Hello_Login_Federation_Groups( $this->logger );

		$fields = $this->get_settings_fields();

		// Some simple pre-processing.
		foreach ( $fields as $key => &$field ) {
			$field['key']  = $key;
			$field['name'] = $this->settings->get_option_name() . '[' . $key . ']';
		}

		$this->settings_fields = $fields;
	}

	/**
	 * Hook the settings page into WordPress.
	 *
	 * @param Hello_Login_Option_Settings $settings       A plugin settings object instance.
	 * @param Hello_Login_Option_Logger   $logger         A plugin logger object instance.
	 *
	 * @return void
	 */
	public static function register( Hello_Login_Option_Settings $settings, Hello_Login_Option_Logger $logger ) {
		$settings_page = new self( $settings, $logger );

		// Add our options page to the admin menu.
		add_action( 'admin_menu', array( $settings_page, 'admin_menu' ) );

		// Register our settings.
		add_action( 'admin_init', array( $settings_page, 'admin_init' ) );
	}

	/**
	 * Implements hook admin_menu to add our options/settings page to the
	 *  dashboard menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Hellō Login Settings', 'hello-login' ),
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
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'user_settings',
			__( 'User Settings', 'hello-login' ),
			array( $this, 'user_settings_description' ),
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
				__( 'Authorization Settings', 'hello-login' ),
				array( $this, 'authorization_settings_description' ),
				$this->options_page_name
			);
		}

		add_settings_section(
			'log_settings',
			__( 'Log Settings', 'hello-login' ),
			array( $this, 'log_settings_description' ),
			$this->advanced_options_page_name
		);

		$this->add_federation_settings_sections();

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

				case 'role_select':
					$callback = 'do_role_select';
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
				$field['page'],
				$field['section'],
				$field
			);
		}

		$this->add_admin_notices();
	}

	/**
	 * Add settings section for federation, one section per federated org.
	 *
	 * @return void
	 */
	private function add_federation_settings_sections() {
		$orgs_groups = $this->federation_groups->get_orgs_groups();

		foreach ( $orgs_groups as $org_groups ) {
			add_settings_section(
				self::federation_org_section_key( $org_groups['id'] ),
				$org_groups['org'] . esc_html( ' group to role mapping' ),
				function () {
				},
				$this->federation_options_page_name
			);
		}
	}

	/**
	 * Add admin notices based on hello-login-msg query param.
	 *
	 * @return void
	 */
	private function add_admin_notices() {
		if ( ! empty( $_GET['hello-login-msg'] ) ) {
			$message_id = sanitize_text_field( wp_unslash( $_GET['hello-login-msg'] ) );

			switch ( $message_id ) {
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
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Quickstart has successfully registered your site at Hellō', 'hello-login' ); ?></p>
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
			<p><?php esc_html_e( 'This account has been unlinked with Hellō', 'hello-login' ); ?></p>
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
			<p><?php esc_html_e( 'This account has been linked with Hellō', 'hello-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get the plugin settings fields definition.
	 *
	 * @return array
	 */
	private function get_settings_fields(): array {
		/**
		 * Simple settings fields have:
		 *
		 * - title
		 * - description
		 * - example (optional example will appear beneath description and be wrapped in <code>)
		 * - type ( checkbox | text | select )
		 * - section - settings/option page section ( client_settings | authorization_settings )
		 * - page - maps the tab, one of $this->options_page_name, $this->federation_options_page_name or $this->advanced_options_page_name
		 */
		$fields = array(
			'scope'             => array(
				'title'       => __( 'Scopes', 'hello-login' ),
				'description' => __( 'Scopes to request in addition to <code>openid email name</code>. See <a href="https://www.hello.dev/documentation/hello-claims.html" target="_blank">https://www.hello.dev/documentation/hello-claims.html</a> for available scopes.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'HELLO_LOGIN_CLIENT_SCOPE' ),
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			'client_id'         => array(
				'title'       => __( 'Client ID', 'hello-login' ),
				'description' => __( 'The client identifier provided by Hellō and set by Quickstart.', 'hello-login' ),
				'type'        => 'text',
				'disabled'    => defined( 'HELLO_LOGIN_CLIENT_ID' ),
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			'redirect_uri'         => array(
				'title'       => __( 'Redirect URI', 'hello-login' ),
				'description' => __( 'The endpoint used to receive authentication data.', 'hello-login' ),
				'type'        => 'text',
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			'provider_hint' => array(
				'title'       => __( 'Provider Hint', 'hello-login' ),
				'description' => __( 'Change which providers are recommended to better align with your users\' preferences.<br><strong>Example:</strong> <code>wordpress email--</code> will promote Wordpress.com to be recommended, and demote email.<br>See <a href="https://www.hello.dev/documentation/provider-hint.html" target="_blank">https://www.hello.dev/documentation/provider-hint.html</a> for details.', 'hello-login' ),
				'type'        => 'text',
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			/*
			'http_request_timeout'      => array(
				'title'       => __( 'HTTP Request Timeout', 'hello-login' ),
				'description' => __( 'Set the timeout for requests made to the IDP. Default value is 5.', 'hello-login' ),
				'example'     => 30,
				'type'        => 'text',
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			'displayname_format'     => array(
				'title'       => __( 'Display Name Formatting', 'hello-login' ),
				'description' => __( 'String from which the user\'s display name is built.', 'hello-login' ),
				'example'     => '{given_name} {family_name}',
				'type'        => 'text',
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			'state_time_limit'     => array(
				'title'       => __( 'State time limit', 'hello-login' ),
				'description' => __( 'State valid time in seconds. Defaults to 180', 'hello-login' ),
				'type'        => 'number',
				'section'     => 'client_settings',
				'page'        => $this->options_page_name,
			),
			*/
			'enable_logging'    => array(
				'title'       => __( 'Enable Logging', 'hello-login' ),
				'description' => __( 'Very simple log messages for debugging purposes.', 'hello-login' ),
				'type'        => 'checkbox',
				'section'     => 'log_settings',
				'page'        => $this->advanced_options_page_name,
			),
			'log_limit'         => array(
				'title'       => __( 'Log Limit', 'hello-login' ),
				'description' => __( 'Number of items to keep in the log. These logs are stored as an option in the database, so space is limited.', 'hello-login' ),
				'type'        => 'number',
				'section'     => 'log_settings',
				'page'        => $this->advanced_options_page_name,
			),
		);

		$fields['link_existing_users'] = array(
			'title'       => __( 'Link Existing Users via Email', 'hello-login' ),
			'description' => __( 'If a newly-authenticated Hellō user does not have an account, and a WordPress account already exists with the same email, link the user to that Wordpress account.', 'hello-login' ),
			'type'        => 'checkbox',
			'disabled'    => defined( 'HELLO_LOGIN_LINK_EXISTING_USERS' ),
			'section'     => 'user_settings',
			'page'        => $this->options_page_name,
		);
		$fields['create_if_does_not_exist'] = array(
			'title'       => __( 'Allow anyone to register with Hellō', 'hello-login' ),
			'description' => __( 'Create a new user if they do not have an account. Authentication will fail if the user does not have an account and this is disabled.', 'hello-login' ),
			'type'        => 'checkbox',
			'disabled'    => defined( 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST' ),
			'section'     => 'user_settings',
			'page'        => $this->options_page_name,
		);

		if ( isset( $_GET['debug'] ) ) {
			$fields['redirect_user_back'] = array(
				'title'       => __( 'Redirect Back to Origin Page', 'hello-login' ),
				'description' => __( 'After a successful authentication, this will redirect the user back to the page on which they clicked the Hellō login button. This will cause the login process to proceed in a traditional WordPress fashion. For example, users logging in through the default wp-login.php page would end up on the WordPress Dashboard and users logging in through the WooCommerce "My Account" page would end up on their account page.', 'hello-login' ),
				'type'        => 'checkbox',
				'disabled'    => defined( 'HELLO_LOGIN_REDIRECT_USER_BACK' ),
				'section'     => 'user_settings',
				'page'   => $this->options_page_name,
			);
		}

		$fields['link_not_now'] = array(
			'title'   => 'Link Not Now',
			'type'    => 'checkbox',
			'section' => 'hidden_settings',
			'page'    => $this->options_page_name,
		);

		return array_merge( $fields, $this->get_federation_settings_fields() );
	}

	/**
	 * Get the plugin federation settings fields definition.
	 *
	 * @return array
	 */
	private function get_federation_settings_fields(): array {
		$fields = array();

		$orgs_groups = $this->federation_groups->get_orgs_groups();

		foreach ( $orgs_groups as $org_groups ) {
			$org_id = $org_groups['id'];
			$groups = $org_groups['groups'];
			$section_key = self::federation_org_section_key( $org_id );

			foreach ( $groups as $group ) {
				$group_id   = $group['id'];
				$group_name = $group['display'];

				$fields[ self::federation_group_field_key( $org_id, $group_id ) ] = array(
					'title'       => $group_name,
					'description' => '',
					'type'        => 'role_select',
					'section'     => $section_key,
					'page'        => $this->federation_options_page_name,
				);
			}
		}

		return $fields;
	}

	/**
	 * Create the settings section key for a federated org.
	 *
	 * @param int $org_id The org id.
	 *
	 * @return string
	 */
	public static function federation_org_section_key( int $org_id ): string {
		return "federation_org_{$org_id}_settings";
	}

	/**
	 * Create the settings field key for a federated group.
	 *
	 * @param int $org_id   The org id.
	 * @param int $group_id The group id.
	 *
	 * @return string The settings field key.
	 */
	public static function federation_group_field_key( int $org_id, int $group_id ): string {
		return "federation_org_{$org_id}_group_{$group_id}";
	}

	/**
	 * Sanitization callback for settings/option page.
	 *
	 * @param array $input The submitted settings values.
	 *
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$options = array();

		// Loop through settings fields to control what we're saving.
		foreach ( $this->settings_fields as $key => $field ) {
			if ( isset( $input[ $key ] ) ) {
				$options[ $key ] = sanitize_text_field( trim( $input[ $key ] ) );

				if ( 'scope' == $key ) {
					$no_duplicate_scope = Hello_Login_Util::remove_duplicate_scopes( $options[ $key ] );

					if ( strlen( $no_duplicate_scope ) < strlen( $options[ $key ] ) ) {
						$options[ $key ] = $no_duplicate_scope;
						add_settings_error( 'scope', 'scope_no_duplicate', 'Duplicate scopes removed.', 'warning' );
					}

					$no_defaults_scope = Hello_Login_Util::remove_default_scopes( $options[ $key ] );

					if ( strlen( $no_defaults_scope ) < strlen( $options[ $key ] ) ) {
						$options[ $key ] = $no_defaults_scope;
						add_settings_error( 'scope', 'scope_no_default', 'Default scopes removed.', 'warning' );
					}
				}
			} else {
				$options[ $key ] = $this->settings->{ $key };
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style( 'hello-login-admin', plugin_dir_url( __DIR__ ) . 'css/styles-admin.css', array(), Hello_Login::VERSION, 'all' );

		$default_tab = 'general';
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_tab;
		?>
		<div class="wrap">
			<h2><?php print esc_html( get_admin_page_title() ); ?></h2>

			<nav class="nav-tab-wrapper">
				<a href="?page=hello-login-settings" class="nav-tab<?php print ( 'general' == $tab ) ? ' nav-tab-active' : ''; ?>">General</a>
				<a href="?page=hello-login-settings&tab=advanced" class="nav-tab<?php print ( 'advanced' == $tab ) ? ' nav-tab-active' : ''; ?>">Advanced</a>
			</nav>

			<div class="tab-content">
			<?php
			switch ( $tab ) {
				case 'advanced':
					$this->settings_page_advanced();
					break;
				default:
					if ( 'general' != $tab ) {
						$this->logger->log( "Unknown settings tab: $tab", 'settings' );
					}
					$this->settings_page_general();
			}
			?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the options/settings page, the general tab.
	 *
	 * @return void
	 */
	public function settings_page_general() {
		$redirect_uri = site_url( '?hello-login=callback' );
		$quickstart_uri = site_url( '?hello-login=quickstart' );
		$settings_page_not_now_url = admin_url( '/options-general.php?page=hello-login-settings&link_not_now=1' );

		$custom_logo_url = '';
		if ( has_custom_logo() ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$custom_logo_data = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			$custom_logo_url = $custom_logo_data[0];
		}

		$redirect_to_path = Hello_Login_Util::extract_path_and_query( admin_url( '/options-general.php?page=hello-login-settings' ) );
		$start_url = create_auth_request_start_url( $redirect_to_path );

		$debug = isset( $_GET['debug'] );
		$configured = ! empty( $this->settings->client_id );

		$link_not_now = ( 1 == $this->settings->link_not_now );
		if ( isset( $_GET['link_not_now'] ) ) {
			if ( '1' == $_GET['link_not_now'] ) {
				$link_not_now = true;
				$this->settings->link_not_now = 1;
			} else {
				$link_not_now = false;
				$this->settings->link_not_now = 0;
			}
			$this->settings->save();
		}

		?>
		<?php if ( ! $configured ) { ?>
			<h2>To use Hellō, you must configure your site. Hellō Quickstart will get you up and running in seconds. You will create a Hellō Wallet if you don't have one already.</h2>

			<form method="get" action="<?php print esc_attr( $this->settings->endpoint_quickstart ); ?>">
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
			<?php if ( empty( Hello_Login_Users::get_hello_sub() ) && ! $link_not_now && ! is_multisite() ) { ?>
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

			<p>Hellō user id: <code><?php print esc_html( Hello_Login_Users::get_hello_sub() ); ?></code></p>

			<p>Settings:</p>
			<pre>
			<?php var_dump( $this->settings->get_values() ); ?>
		</pre>
			}
		<?php } ?>
		<?php
	}

	/**
	 * Output the options/settings page, the federation tab.
	 *
	 * @return void
	 */
	public function settings_page_federation() {
		?>
		<h2>Use the <a href="https://console.hello.coop/?client_id=<?php print rawurlencode( $this->settings->client_id ); ?>" target="_blank">Hellō Console</a> to update your federation settings.</h2>

		<form method="post" action="options.php">
			<?php
			settings_fields( $this->settings_field_group );
			do_settings_sections( $this->federation_options_page_name );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Output the options/settings page, the advanced tab.
	 *
	 * @return void
	 */
	public function settings_page_advanced() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( $this->settings_field_group );
			do_settings_sections( $this->advanced_options_page_name );
			submit_button();
			?>
		</form>
		<?php if ( $this->settings->enable_logging ) { ?>

			<h2><?php esc_html_e( 'Logs', 'hello-login' ); ?></h2>
			<div id="logger-table-wrapper">
				<?php print wp_kses_post( $this->logger->get_logs_table() ); ?>
			</div>
		<?php } ?>
		<?php
	}

	/**
	 * Output a standard text field.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_text_field( array $field ) {
		$disabled = ! empty( $field['disabled'] ) && boolval( $field['disabled'] ) === true;
		$value = $this->settings->{ $field['key'] };

		$readonly = '';
		if ( 'client_id' == $field['key'] ) {
			$readonly = 'readonly';
		}
		if ( 'redirect_uri' == $field['key'] ) {
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
	public function do_checkbox( array $field ) {
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
	public function do_select( array $field ) {
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
	 * Output a role select control.
	 *
	 * @param array $field The settings field definition array.
	 *
	 * @return void
	 */
	public function do_role_select( array $field ) {
		$current_value = isset( $this->settings->{ $field['key'] } ) ? $this->settings->{ $field['key'] } : '';
		?>
		<select name="<?php print esc_attr( $field['name'] ); ?>">
			<option value=""><?php print esc_html( 'none' ); ?></option>
			<?php wp_dropdown_roles( $current_value ); ?>
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
	public function do_field_description( array $field ) {
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
