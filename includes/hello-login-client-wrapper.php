<?php
/**
 * Plugin OIDC/oAuth client warpper class.
 *
 * @package   Hello_Login
 * @category  Authentication
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Client_Wrapper class.
 *
 * Plugin OIDC/oAuth client wrapper class.
 *
 * @package  Hello_Login
 * @category Authentication
 */
class Hello_Login_Client_Wrapper {

	/**
	 * The client object instance.
	 *
	 * @var Hello_Login_Client
	 */
	private Hello_Login_Client $client;

	/**
	 * The settings object instance.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private Hello_Login_Option_Settings $settings;

	/**
	 * The logger object instance.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * User linking error code.
	 *
	 * @var string
	 */
	const LINK_ERROR_CODE = 'user_link_error';

	/**
	 * User linking error message.
	 *
	 * @var string
	 */
	const LINK_ERROR_MESSAGE = 'User already linked to a different Hellō account.';

	/**
	 * Inject necessary objects and services into the client.
	 *
	 * @param Hello_Login_Client          $client   A plugin client object instance.
	 * @param Hello_Login_Option_Settings $settings A plugin settings object instance.
	 * @param Hello_Login_Option_Logger   $logger   A plugin logger object instance.
	 */
	public function __construct( Hello_Login_Client $client, Hello_Login_Option_Settings $settings, Hello_Login_Option_Logger $logger ) {
		$this->client = $client;
		$this->settings = $settings;
		$this->logger = $logger;
	}

	/**
	 * Hook the client into WordPress.
	 *
	 * @param Hello_Login_Client          $client   The plugin client instance.
	 * @param Hello_Login_Option_Settings $settings The plugin settings instance.
	 * @param Hello_Login_Option_Logger   $logger   The plugin logger instance.
	 *
	 * @return Hello_Login_Client_Wrapper
	 */
	public static function register( Hello_Login_Client $client, Hello_Login_Option_Settings $settings, Hello_Login_Option_Logger $logger ): Hello_Login_Client_Wrapper {
		return new self( $client, $settings, $logger );
	}

	/**
	 * Generate an authorization request URL and redirect to it.
	 *
	 * @return WP_Error
	 */
	public function start_auth(): WP_Error {
		$atts = array();

		if ( isset( $_GET['redirect_to_path'] ) ) {
			$redirect_to_path = sanitize_text_field( wp_unslash( $_GET['redirect_to_path'] ) );

			// Validate that only a path was passed in.
			$p = parse_url( $redirect_to_path );

			if ( isset( $p['scheme'] ) || isset( $p['host'] ) || isset( $p['port'] ) || isset( $p['user'] ) || isset( $p['pass'] ) ) {
				return new WP_Error( 'invalid_path', 'Invalid redirect_to_path', array( 'status' => 400 ) );
			}

			$redirect_to_path = '/' . ltrim( $redirect_to_path, '/' );
			$redirect_to = rtrim( home_url(), '/' ) . $redirect_to_path;
			$atts['redirect_to'] = $redirect_to;
		}

		$scope_set = 'auth';
		if ( isset( $_GET['scope_set'] ) ) {
			$scope_set = sanitize_text_field( wp_unslash( $_GET['scope_set'] ) );
		}

		$atts['scope_set'] = $scope_set;

		nocache_headers();

		wp_redirect( $this->get_authentication_url( $atts ) );
		exit();
	}

	/**
	 * Process the Quickstart response.
	 *
	 * @return void
	 */
	public function quickstart_callback() {
		$message_id = 'quickstart_success';

		if ( isset( $_GET['client_id'] ) ) {
			$client_id = sanitize_text_field( wp_unslash( $_GET['client_id'] ) );

			if ( preg_match( '/^[a-z0-9_-]{1,64}$/', $client_id ) ) {
				if ( empty( $this->settings->client_id ) ) {
					$this->settings->client_id = $client_id;
					$this->settings->link_not_now = 0;
					$this->settings->save();
					$this->logger->log( "Client ID set through Quickstart: {$this->settings->client_id}", 'quickstart' );
				} else {
					$message_id = 'quickstart_existing_client_id';
					$this->logger->log( 'Client id already set', 'quickstart' );
				}
			} else {
				$message_id = 'quickstart_missing_client_id';
				$this->logger->log( 'Invalid client id', 'quickstart' );
			}
		} else {
			$message_id = 'quickstart_missing_client_id';
			$this->logger->log( 'Missing client id', 'quickstart' );
		}

		wp_redirect( admin_url( '/options-general.php?page=hello-login-settings&hello-login-msg=' . $message_id ) );
		exit();
	}

	/**
	 * Get the client login redirect.
	 *
	 * @return string
	 */
	public function get_redirect_to(): string {
		// @var WP $wp WordPress environment setup class.
		global $wp;

		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' == $GLOBALS['pagenow'] && isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) {
			return '';
		}

		// If using the login form, default redirect to the home page.
		if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' == $GLOBALS['pagenow'] ) {
			return home_url();
		}

		if ( is_admin() ) {
			return admin_url( sprintf( basename( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) );
		}

		// Default redirect to the homepage.
		$redirect_url = home_url();

		// Honor Core WordPress & other plugin redirects.
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirect_url = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );
		}

		// Capture the current URL if set to redirect back to origin page.
		if ( $this->settings->redirect_user_back ) {
			if ( ! empty( $wp->request ) ) {
				if ( ! empty( $wp->did_permalink ) && true === $wp->did_permalink ) {
					$redirect_url = home_url( add_query_arg( $_GET, trailingslashit( $wp->request ) ) );
				} else {
					$redirect_url = home_url( add_query_arg( null, null ) );
				}
			} else {
				if ( ! empty( $wp->query_string ) ) {
					$redirect_url = home_url( '?' . $wp->query_string );
				}
			}
		}

		return $redirect_url;
	}

	/**
	 * Create a single use authentication url
	 *
	 * @param array<string> $atts An optional array of override/feature attributes.
	 *
	 * @return string
	 */
	public function get_authentication_url( array $atts = array() ): string {
		$scope_set = $atts['scope_set'] ?? 'auth';
		switch ( $scope_set ) {
			case 'update_email':
				$scope = 'openid profile_update email';
				break;
			default:
				$scope = Hello_Login_Util::add_default_scopes( $this->settings->scope );
		}

		$atts = shortcode_atts(
			array(
				'endpoint_login' => $this->settings->endpoint_login,
				'scope' => $scope,
				'client_id' => $this->settings->client_id,
				'redirect_uri' => $this->client->get_redirect_uri(),
				'redirect_to' => $this->get_redirect_to(),
				'provider_hint' => $this->settings->provider_hint,
			),
			$atts,
			'hello_login_auth_url'
		);

		// Validate the redirect to value to prevent a redirection attack.
		if ( ! empty( $atts['redirect_to'] ) ) {
			$atts['redirect_to'] = wp_validate_redirect( $atts['redirect_to'], home_url() );
		}

		$separator = '?';
		if ( stripos( $this->settings->endpoint_login, '?' ) !== false ) {
			$separator = '&';
		}

		$url_format = '%1$s%2$sresponse_type=code&scope=%3$s&client_id=%4$s&state=%5$s&redirect_uri=%6$s';

		$pkce_data = $this->pkce_code_generator();
		$url_format .= '&code_challenge=%7$s&code_challenge_method=%8$s';

		if ( ! empty( $atts['provider_hint'] ) ) {
			$url_format .= '&provider_hint=%9$s';
		}

		$url = sprintf(
			$url_format,
			$atts['endpoint_login'],
			$separator,
			rawurlencode( $atts['scope'] ),
			rawurlencode( $atts['client_id'] ),
			$this->client->new_state( $atts['redirect_to'], $pkce_data['code_verifier'] ),
			rawurlencode( $atts['redirect_uri'] ),
			rawurlencode( $pkce_data['code_challenge'] ),
			rawurlencode( $pkce_data['code_challenge_method'] ),
			rawurlencode( $atts['provider_hint'] )
		);

		$this->logger->log( $url, 'get_authentication_url' );
		return $url;
	}

	/**
	 * Handle errors by redirecting the user to the login form along with an
	 * error code
	 *
	 * @param WP_Error $error A WordPress error object.
	 *
	 * @return void
	 */
	public function error_redirect( WP_Error $error ) {
		$this->logger->log( $error );

		// Redirect user back to login page.
		wp_redirect(
			wp_login_url() .
			'?login-error=' . $error->get_error_code() .
			'&message=' . urlencode( $error->get_error_message() )
		);
		exit;
	}

	/**
	 * Control the authentication and subsequent authorization of the user when
	 * returning from the IDP.
	 *
	 * @return void
	 */
	public function authentication_request_callback() {
		$client = $this->client;

		// Start the authentication flow.
		$authentication_request = $client->validate_authentication_request( $_GET );

		if ( is_wp_error( $authentication_request ) ) {
			$this->error_redirect( $authentication_request );
		}

		// Retrieve the authentication code from the authentication request.
		$code = $client->get_authentication_code( $authentication_request );

		if ( is_wp_error( $code ) ) {
			$this->error_redirect( $code );
		}

		// Retrieve the authentication state from the authentication request.
		$state = $client->get_authentication_state( $authentication_request );

		if ( is_wp_error( $state ) ) {
			$this->error_redirect( $state );
		}

		// Attempting to exchange an authorization code for an authentication token.
		$token_result = $client->exchange_authorization_code( $code, $state );

		if ( is_wp_error( $token_result ) ) {
			$this->error_redirect( $token_result );
		}

		// Get the decoded response from the authentication request result.
		$token_response = $client->get_token_response( $token_result );

		if ( is_wp_error( $token_response ) ) {
			$this->error_redirect( $token_response );
		}

		// Ensure the that response contains required information.
		$valid = $client->validate_token_response( $token_response );

		if ( is_wp_error( $valid ) ) {
			$this->error_redirect( $valid );
		}

		/**
		 * The id_token is used to identify the authenticated user, e.g. for SSO.
		 * The access_token must be used to prove access rights to protected
		 * resources e.g. for the userinfo endpoint
		 */
		$user_claim = $client->get_id_token_claim( $token_response );

		if ( is_wp_error( $user_claim ) ) {
			$this->error_redirect( $user_claim );
		}

		// Validate our id_token has required values.
		$valid = $client->validate_id_token_claim( $user_claim );

		if ( is_wp_error( $valid ) ) {
			$this->error_redirect( $valid );
		}

		/**
		 * End authorization
		 * -
		 * Request is authenticated and authorized - start user handling
		 */
		$subject_identity = $client->get_subject_identity( $user_claim );
		$user = $this->get_user_by_identity( $subject_identity );

		$link_error = new WP_Error( self::LINK_ERROR_CODE, __( self::LINK_ERROR_MESSAGE, 'hello-login' ) );
		$link_error->add_data( $subject_identity );
		$message_id = '';

		if ( ! $user ) {
			// A pre-existing Hellō mapped user wasn't found.
			if ( is_user_logged_in() ) {
				// Current user session, no user found based on 'sub'.

				// Check if current user is already linked to a different Hellō account.
				$current_user_hello_sub = get_user_meta( get_current_user_id(), 'hello-login-subject-identity', true );
				if ( ! empty( $current_user_hello_sub ) && $current_user_hello_sub !== $subject_identity ) {
					$link_error->add_data( $current_user_hello_sub );
					$link_error->add_data( get_current_user_id() );
					$this->error_redirect( $link_error );
				}

				// Link accounts.
				$user = wp_get_current_user();
				add_user_meta( $user->ID, 'hello-login-subject-identity', (string) $subject_identity, true );

				$this->save_extra_claims( $user->ID, $user_claim );

				$this->update_user_claims( $user, $user_claim );

				$message_id = 'link_success';
			} else {
				// No current user session and no user found based on 'sub'.

				if ( $this->settings->link_existing_users || $this->settings->create_if_does_not_exist ) {
					// If linking existing users or creating new ones call the `create_new_user` method which
					// handles both cases. Linking uses email.
					$user = $this->create_new_user( $subject_identity, $user_claim );
					if ( is_wp_error( $user ) ) {
						$this->error_redirect( $user );
					}

					$this->save_extra_claims( $user->ID, $user_claim );

					$this->update_user_claims( $user, $user_claim );
				} else {
					$this->error_redirect( new WP_Error( 'identity-not-map-existing-user', __( 'User identity is not linked to an existing WordPress user.', 'hello-login' ), $user_claim ) );
				}
			}
		} else {
			// Pre-existing Hellō mapped user was found.

			if ( is_user_logged_in() && get_current_user_id() !== $user->ID ) {
				$link_error->add_data( get_user_meta( get_current_user_id(), 'hello-login-subject-identity', true ) );
				$link_error->add_data( get_current_user_id() );
				$link_error->add_data( $user->ID );
				$this->error_redirect( $link_error );
			}

			$this->save_extra_claims( $user->ID, $user_claim );

			$this->update_user_claims( $user, $user_claim );
		}

		// Validate the found / created user.
		$valid = $this->validate_user( $user );

		if ( is_wp_error( $valid ) ) {
			$this->error_redirect( $valid );
		}

		// Login the found / created user.
		$this->login_user( $user, $user_claim );

		// Allow plugins / themes to take action once a user is logged in.
		do_action( 'hello-login-user-logged-in', $user );

		// Log our success.
		$this->logger->log( "Successful login for: $user->user_login ($user->ID)", 'login-success' );

		// Default redirect to the homepage.
		$redirect_url = home_url();
		// Redirect user according to redirect set in state.
		$state_object = get_transient( 'hello-login-state--' . $state );
		// Get the redirect URL stored with the corresponding authentication request state.
		if ( ! empty( $state_object ) && ! empty( $state_object[ $state ] ) && ! empty( $state_object[ $state ]['redirect_to'] ) ) {
			$redirect_url = $state_object[ $state ]['redirect_to'];
		}

		// Only do redirect-user-back action hook when the plugin is configured for it.
		if ( $this->settings->redirect_user_back ) {
			do_action( 'hello-login-redirect-user-back', $redirect_url, $user );
		}

		if ( ! empty( $message_id ) ) {
			$redirect_url .= ( parse_url( $redirect_url, PHP_URL_QUERY ) ? '&' : '?' ) . 'hello-login-msg=' . $message_id;
		}

		wp_redirect( $redirect_url );

		exit;
	}

	/**
	 * Unlink the current WordPress user from Hellō user.
	 *
	 * @return void
	 */
	public function unlink_hello() {
		$message_id = 'unlink_success';
		$wp_user_id = get_current_user_id();
		$target_user_id = $wp_user_id;

		if ( isset( $_GET['user_id'] ) ) {
			$target_user_id = sanitize_text_field( wp_unslash( $_GET['user_id'] ) );
		}

		if ( $wp_user_id == $target_user_id || current_user_can( 'edit_user' ) ) {
			if ( 0 == $wp_user_id ) {
				// No valid session found, or current user is not an administrator.
				$this->logger->log( 'No current user', 'unlink_hello' );
				$message_id = 'unlink_no_session';
			} else {
				if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'unlink' . $target_user_id ) ) {
					$hello_user_id = get_user_meta( $target_user_id, 'hello-login-subject-identity', true );

					if ( empty( $hello_user_id ) ) {
						$this->logger->log( 'User not linked', 'unlink_hello' );
						$message_id = 'unlink_not_linked';
					} else {
						delete_user_meta( $target_user_id, 'hello-login-subject-identity' );
						$this->logger->log( "WordPress user $target_user_id unlinked from Hellō user $hello_user_id.", 'unlink_hello' );
					}
				} else {
					$this->logger->log( 'CSRF nonce verification failed: ' . $_GET['_wpnonce'], 'unlink_hello' );
					$message_id = 'unlink_no_session';
				}
			}
		} else {
			$this->logger->log( 'Current user has no edit_user capability', 'unlink_hello' );
			$message_id = 'unlink_no_session';
		}

		$profile_url = get_edit_user_link( $target_user_id );
		$profile_url .= ( parse_url( $profile_url, PHP_URL_QUERY ) ? '&' : '?' ) . 'hello-login-msg=' . $message_id;
		wp_redirect( $profile_url );
		exit;
	}

	/**
	 * Validate the potential WP_User.
	 *
	 * @param WP_User|WP_Error|false $user The user object.
	 *
	 * @return true|WP_Error
	 */
	public function validate_user( $user ) {
		// Ensure the found user is a real WP_User.
		if ( ! is_a( $user, 'WP_User' ) || ! $user->exists() ) {
			return new WP_Error( 'invalid-user', __( 'Invalid user.', 'hello-login' ), $user );
		}

		return true;
	}

	/**
	 * Record user meta data, and provide an authorization cookie.
	 *
	 * @param WP_User $user             The user object.
	 * @param array   $user_claim       The authenticated user claim.
	 *
	 * @return void
	 */
	public function login_user( WP_User $user, array $user_claim ) {
		update_user_meta( $user->ID, 'hello-login-last-user-claim', $user_claim );
		// Allow plugins / themes to take action using current claims on existing user (e.g. update role).
		do_action( 'hello-login-update-user-using-current-claim', $user, $user_claim );

		// Create the WP session, so we know its token.
		$expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user->ID, false );
		$manager = WP_Session_Tokens::get_instance( $user->ID );
		$token = $manager->create( $expiration );

		// you did great, have a cookie!
		wp_set_auth_cookie( $user->ID, false, '', $token );
		do_action( 'wp_login', $user->user_login, $user );
	}

	/**
	 * Get the user that has metadata matching a
	 *
	 * @param string $subject_identity The IDP identity of the user.
	 *
	 * @return false|WP_User
	 */
	public function get_user_by_identity( string $subject_identity ) {
		// Look for user by their hello-login-subject-identity value.
		$user_query = new WP_User_Query(
			array(
				'meta_query' => array(
					array(
						'key'   => 'hello-login-subject-identity',
						'value' => $subject_identity,
					),
				),
				// Override the default blog_id (get_current_blog_id) to find users on different sites of a multisite install.
				'blog_id' => 0,
			)
		);

		// If we found existing users, grab the first one returned.
		if ( $user_query->get_total() > 0 ) {
			$users = $user_query->get_results();
			return $users[0];
		}

		return false;
	}

	/**
	 * Avoid user_login collisions by incrementing.
	 *
	 * @param array $user_claim The IDP authenticated user claim data.
	 *
	 * @return string|WP_Error
	 */
	private function get_username_from_claim( array $user_claim ) {

		// @var string $desired_username
		$desired_username = '';

		// Allow settings to take first stab at username.
		if ( ! empty( $user_claim['nickname'] ) ) {
			$desired_username = $user_claim['nickname'];
		}
		if ( empty( $desired_username ) && ! empty( $user_claim['preferred_username'] ) ) {
			$desired_username = $user_claim['preferred_username'];
		}
		if ( empty( $desired_username ) && ! empty( $user_claim['name'] ) ) {
			$desired_username = $user_claim['name'];
			$desired_username = strtolower( str_replace( ' ', '', $desired_username ) );
		}
		if ( empty( $desired_username ) && ! empty( $user_claim['email'] ) ) {
			$tmp = explode( '@', $user_claim['email'] );
			$desired_username = $tmp[0];
		}
		if ( empty( $desired_username ) ) {
			// Nothing to build a name from.
			return new WP_Error( 'no-username', __( 'No appropriate username found.', 'hello-login' ), $user_claim );
		}

		// Don't use the full email address for a username.
		$_desired_username = explode( '@', $desired_username );
		$desired_username = $_desired_username[0];
		// Use WordPress Core to sanitize the IDP username.
		$sanitized_username = sanitize_user( $desired_username, true );
		if ( empty( $sanitized_username ) ) {
			// translators: %1$s is the sanitized version of the username from the IDP.
			return new WP_Error( 'username-sanitization-failed', sprintf( __( 'Username %1$s could not be sanitized.', 'hello-login' ), $desired_username ), $desired_username );
		}

		return $sanitized_username;
	}

	/**
	 * Get a nickname.
	 *
	 * @param array $user_claim The IDP authenticated user claim data.
	 *
	 * @return string
	 */
	private function get_nickname_from_claim( array $user_claim ): string {
		$desired_nickname = '';
		// Allow settings to take first stab at nickname.
		if ( isset( $user_claim['nickname'] ) ) {
			$desired_nickname = $user_claim['nickname'];
		}

		if ( empty( $desired_nickname ) && isset( $user_claim['name'] ) ) {
			$desired_nickname = $user_claim['name'];
		}

		return $desired_nickname;
	}

	/**
	 * Checks if $claimname is in the body or _claim_names of the userinfo.
	 * If yes, returns the claim value. Otherwise, returns false.
	 *
	 * @param string $claimname the claim name to look for.
	 * @param array  $userinfo the JSON to look in.
	 * @param string $claimvalue the source claim value ( from the body of the JWT of the claim source).
	 * @return bool
	 */
	private function get_claim( string $claimname, array $userinfo, string &$claimvalue ): bool {
		/**
		 * If we find a simple claim, return it.
		 */
		if ( array_key_exists( $claimname, $userinfo ) ) {
			$claimvalue = $userinfo[ $claimname ];
			return true;
		}
		/**
		 * If there are no aggregated claims, it is over.
		 */
		if ( ! array_key_exists( '_claim_names', $userinfo ) ||
			! array_key_exists( '_claim_sources', $userinfo ) ) {
			return false;
		}
		$claim_src_ptr = $userinfo['_claim_names'];
		if ( ! isset( $claim_src_ptr ) ) {
			return false;
		}
		/**
		 * No reference found
		 */
		if ( ! array_key_exists( $claimname, $claim_src_ptr ) ) {
			return false;
		}
		$src_name = $claim_src_ptr[ $claimname ];
		// Reference found, but no corresponding JWT. This is a malformed userinfo.
		if ( ! array_key_exists( $src_name, $userinfo['_claim_sources'] ) ) {
			return false;
		}
		$src = $userinfo['_claim_sources'][ $src_name ];
		// Source claim is not a JWT. Abort.
		if ( ! array_key_exists( 'JWT', $src ) ) {
			return false;
		}
		/**
		 * Extract claim from JWT.
		 * FIXME: We probably want to verify the JWT signature/issuer here.
		 * For example, using JWKS if applicable. For symmetrically signed
		 * JWTs (HMAC), we need a way to specify the acceptable secrets
		 * and each possible issuer in the config.
		 */
		$jwt = $src['JWT'];
		list ( $header, $body, $rest ) = explode( '.', $jwt, 3 );
		$body_str = base64_decode( $body, false );
		if ( ! $body_str ) {
			return false;
		}
		$body_json = json_decode( $body_str, true );
		if ( ! isset( $body_json ) ) {
			return false;
		}
		if ( ! array_key_exists( $claimname, $body_json ) ) {
			return false;
		}
		$claimvalue = $body_json[ $claimname ];
		return true;
	}


	/**
	 * Build a string from the user claim according to the specified format.
	 *
	 * @param string $format               The format of the user identity.
	 * @param array  $user_claim           The authorized user claim.
	 * @param bool   $error_on_missing_key Whether to return and error on a missing key.
	 *
	 * @return string|WP_Error
	 */
	private function format_string_with_claim( string $format, array $user_claim, bool $error_on_missing_key = false ) {
		$matches = null;
		$string = '';
		$info = '';
		$i = 0;
		if ( preg_match_all( '/\{[^}]*\}/u', $format, $matches, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $matches[0] as $match ) {
				$key = substr( $match[0], 1, -1 );
				$string .= substr( $format, $i, $match[1] - $i );
				if ( ! $this->get_claim( $key, $user_claim, $info ) ) {
					if ( $error_on_missing_key ) {
						return new WP_Error(
							'incomplete-user-claim',
							__( 'User claim incomplete.', 'hello-login' ),
							array(
								'message'    => 'Unable to find key: ' . $key . ' in user_claim',
								'hint'       => 'Verify OpenID Scope includes a scope with the attributes you need',
								'user_claim' => $user_claim,
								'format'     => $format,
							)
						);
					}
				} else {
					$string .= $info;
				}
				$i = $match[1] + strlen( $match[0] );
			}
		}
		$string .= substr( $format, $i );
		return $string;
	}

	/**
	 * Get a displayname.
	 *
	 * @param array $user_claim The authorized user claim.
	 *
	 * @return string|WP_Error
	 */
	private function get_displayname_from_claim( array $user_claim ) {
		return $this->format_string_with_claim( $this->settings->displayname_format, $user_claim, true );
	}

	/**
	 * Get an email.
	 *
	 * @param array $user_claim The authorized user claim.
	 *
	 * @return string|WP_Error
	 */
	private function get_email_from_claim( array $user_claim ) {
		return $this->format_string_with_claim( '{email}', $user_claim, true );
	}

	/**
	 * Create a new user from details in a user_claim.
	 *
	 * @param string $subject_identity The authenticated user's identity with the IDP.
	 * @param array  $user_claim       The authorized user claim.
	 *
	 * @return WP_Error|WP_User
	 */
	public function create_new_user( string $subject_identity, array $user_claim ) {
		$email = $this->get_email_from_claim( $user_claim );
		if ( is_wp_error( $email ) ) {
			return $email;
		}

		$username = $this->get_username_from_claim( $user_claim );
		if ( is_wp_error( $username ) ) {
			return $username;
		}

		$nickname = $this->get_nickname_from_claim( $user_claim );
		if ( empty( $nickname ) ) {
			$nickname = $username;
		}

		$displayname = $this->get_displayname_from_claim( $user_claim );
		if ( is_wp_error( $displayname ) ) {
			return $displayname;
		}

		// Before trying to create the user, first check if a matching user exists.
		if ( $this->settings->link_existing_users ) {
			$uid = email_exists( $email );

			if ( ! empty( $uid ) ) {
				$user = $this->update_existing_user( $uid, $subject_identity );

				if ( is_wp_error( $user ) ) {
					return $user;
				}

				do_action( 'hello-login-update-user-using-current-claim', $user, $user_claim );
				return $user;
			}
		}

		/**
		 * Allow other plugins / themes to determine authorization of new accounts
		 * based on the returned user claim.
		 */
		$create_user = apply_filters( 'hello-login-user-creation-test', $this->settings->create_if_does_not_exist, $user_claim );

		if ( ! $create_user ) {
			return new WP_Error( 'cannot-authorize', __( 'Can not authorize.', 'hello-login' ), $create_user );
		}

		// Copy the username for incrementing.
		$_username = $username;
		// Ensure prevention of linking usernames & collisions by incrementing the username if it exists.
		// @example Original user gets "name", second user gets "name2", etc.
		$count = 1;
		while ( username_exists( $username ) ) {
			$count ++;
			$username = $_username . $count;
		}

		$user_data = array(
			'user_login' => $username,
			'user_pass' => wp_generate_password( 32, true, true ),
			'user_email' => $email,
			'display_name' => $displayname,
			'nickname' => $nickname,
			'first_name' => $user_claim['given_name'] ?? '',
			'last_name' => $user_claim['family_name'] ?? '',
		);
		$user_data = apply_filters( 'hello-login-alter-user-data', $user_data, $user_claim );

		// Create the new user.
		$uid = wp_insert_user( $user_data );

		// Make sure we didn't fail in creating the user.
		if ( is_wp_error( $uid ) ) {
			return new WP_Error( 'failed-user-creation', __( 'Failed user creation.', 'hello-login' ), $uid );
		}

		// Retrieve our new user.
		$user = get_user_by( 'id', $uid );

		// Save some metadata about this new user for the future.
		add_user_meta( $user->ID, 'hello-login-subject-identity', (string) $subject_identity, true );

		// Log the results.
		$this->logger->log( "New user created: {$user->user_login} ($uid)", 'success' );

		// Allow plugins / themes to take action on new user creation.
		do_action( 'hello-login-user-create', $user, $user_claim );

		return $user;
	}

	/**
	 * Save extra user claims as user metadata.
	 *
	 * @param int   $uid The WordPress User ID.
	 * @param array $user_claim The user claim.
	 * @return void
	 */
	public function save_extra_claims( int $uid, array $user_claim ) {
		foreach ( $user_claim as $key => $value ) {
			if ( ! in_array( $key, array( 'iss', 'sub', 'aud', 'exp', 'iat', 'jti', 'auth_time', 'nonce', 'acr', 'amr', 'azp' ) ) ) {
				if ( update_user_meta( $uid, 'hello-login-claim-' . $key, $value ) ) {
					$this->logger->log( 'User claim saved as meta: hello-login-claim-' . $key . ' = ' . $value, 'user-claims' );
				}
			}
		}
	}

	/**
	 * Update user claims as user metadata.
	 *
	 * @param WP_User $user The WordPress User.
	 * @param array   $user_claim The user claim.
	 *
	 * @return void
	 */
	public function update_user_claims( WP_User $user, array $user_claim ) {
		$uid = $user->ID;

		if ( isset( $user_claim['given_name'] ) && empty( get_user_meta( $uid, 'first_name', true ) ) ) {
			if ( update_user_meta( $uid, 'first_name', $user_claim['given_name'] ) ) {
				$this->logger->log( 'User first name saved: ' . $user_claim['given_name'], 'user-claims' );
			} else {
				$this->logger->log( 'Failed saving user first name.', 'user-claims' );
			}
		}

		if ( isset( $user_claim['family_name'] ) && empty( get_user_meta( $uid, 'last_name', true ) ) ) {
			if ( update_user_meta( $uid, 'last_name', $user_claim['family_name'] ) ) {
				$this->logger->log( 'User last name saved: ' . $user_claim['family_name'], 'user-claims' );
			} else {
				$this->logger->log( 'Failed saving user last name.', 'user-claims' );
			}
		}

		if ( isset( $user_claim['email'] ) && $user_claim['email'] != $user->user_email ) {
			$res = wp_update_user(
				array(
					'ID' => $uid,
					'user_email' => $user_claim['email'],
				)
			);

			if ( is_wp_error( $res ) ) {
				$this->logger->log( $res );
				$this->logger->log( "Email update failed for user $uid to email {$user_claim['email']}", 'user-claims' );
			} else {
				$this->logger->log( "User email updated from $user->user_email to {$user_claim['email']}.", 'user-claims' );
				$user->user_email = $user_claim['email'];
			}
		}
	}

	/**
	 * Update an existing user with OpenID Connect metadata
	 *
	 * @param int    $uid              The WordPress User ID.
	 * @param string $subject_identity The subject identity from the IDP.
	 *
	 * @return WP_Error|WP_User
	 */
	public function update_existing_user( int $uid, string $subject_identity ) {
		$uid_hello_sub = get_user_meta( $uid, 'hello-login-subject-identity', true );
		if ( ! empty( $uid_hello_sub ) && $uid_hello_sub !== $subject_identity ) {
			$link_error = new WP_Error( self::LINK_ERROR_CODE, __( self::LINK_ERROR_MESSAGE, 'hello-login' ) );
			$link_error->add_data( $subject_identity );
			$link_error->add_data( $uid_hello_sub );
			$link_error->add_data( $uid );

			return $link_error;
		}

		// Add the OpenID Connect metadata.
		update_user_meta( $uid, 'hello-login-subject-identity', strval( $subject_identity ) );

		// Allow plugins / themes to take action on user update.
		do_action( 'hello-login-user-update', $uid );

		// Return our updated user.
		return get_user_by( 'id', $uid );
	}

	/**
	 * Generate PKCE code for OAuth flow.
	 *
	 * @see : https://help.aweber.com/hc/en-us/articles/360036524474-How-do-I-use-Proof-Key-for-Code-Exchange-PKCE-
	 *
	 * @return array<string, mixed> Code challenge array.
	 */
	private function pkce_code_generator(): array {
		try {
			$verifier_bytes = random_bytes( 64 );
		} catch ( Exception $e ) {
			$msg = sprintf( 'Fail to generate PKCE code challenge : %s', $e->getMessage() );
			$this->logger->log( $msg, 'pkce_code_generator' );
			exit( $msg );
		}

		$verifier = rtrim( strtr( base64_encode( $verifier_bytes ), '+/', '-_' ), '=' );

		// Very important, "raw_output" must be set to true or the challenge will not match the verifier.
		$challenge_bytes = hash( 'sha256', $verifier, true );
		$challenge       = rtrim( strtr( base64_encode( $challenge_bytes ), '+/', '-_' ), '=' );

		return array(
			'code_verifier'         => $verifier,
			'code_challenge'        => $challenge,
			'code_challenge_method' => 'S256',
		);
	}
}
