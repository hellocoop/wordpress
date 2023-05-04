<?php
/**
 * Plugin OIDC/oAuth client class.
 *
 * @package   Hello_Login
 * @category  Authentication
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Client class.
 *
 * Plugin OIDC/oAuth client class.
 *
 * @package  Hello_Login
 * @category Authentication
 */
class Hello_Login_Client {

	/**
	 * The OIDC/oAuth client ID.
	 *
	 * @see Hello_Login_Option_Settings::client_id
	 *
	 * @var string
	 */
	private string $client_id;

	/**
	 * The OIDC/oAuth scopes.
	 *
	 * @see Hello_Login_Option_Settings::scope
	 *
	 * @var string
	 */
	private string $scope;

	/**
	 * The OIDC/oAuth token validation endpoint URL.
	 *
	 * @see Hello_Login_Option_Settings::endpoint_token
	 *
	 * @var string
	 */
	private string $endpoint_token;

	/**
	 * The login flow "ajax" endpoint URI.
	 *
	 * @see Hello_Login_Option_Settings::redirect_uri
	 *
	 * @var string
	 */
	private string $redirect_uri;

	/**
	 * The state time limit. States are only valid for 10 minutes.
	 *
	 * @see Hello_Login_Option_Settings::state_time_limit
	 *
	 * @var int
	 */
	private int $state_time_limit;

	/**
	 * The timeout for HTTP requests.
	 *
	 * @see Hello_Login_Option_Settings::http_request_timeout
	 *
	 * @var int
	 */
	private int $http_request_timeout;

	/**
	 * The logger object instance.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * Client constructor.
	 *
	 * @param string                    $client_id            @see Hello_Login_Option_Settings::client_id for description.
	 * @param string                    $scope                @see Hello_Login_Option_Settings::scope for description.
	 * @param string                    $endpoint_token       @see Hello_Login_Option_Settings::endpoint_token for description.
	 * @param string                    $redirect_uri         @see Hello_Login_Option_Settings::redirect_uri for description.
	 * @param int                       $state_time_limit     @see Hello_Login_Option_Settings::state_time_limit for description.
	 * @param int                       $http_request_timeout @see Hello_Login_Option_Settings::http_request_timeout for description.
	 * @param Hello_Login_Option_Logger $logger               The plugin logging object instance.
	 */
	public function __construct( string $client_id, string $scope, string $endpoint_token, string $redirect_uri, int $state_time_limit, int $http_request_timeout, Hello_Login_Option_Logger $logger ) {
		$this->client_id = $client_id;
		$this->scope = $scope;
		$this->endpoint_token = $endpoint_token;
		$this->redirect_uri = $redirect_uri;
		$this->state_time_limit = $state_time_limit;
		$this->http_request_timeout = $http_request_timeout;
		$this->logger = $logger;
	}

	/**
	 * Provides the configured Redirect URI supplied to the IDP.
	 *
	 * @return string
	 */
	public function get_redirect_uri(): string {
		return $this->redirect_uri;
	}

	/**
	 * Validate the request for login authentication
	 *
	 * @param array<string> $request The authentication request results.
	 *
	 * @return array<string>|WP_Error
	 */
	public function validate_authentication_request( array $request ) {
		// Look for an existing error of some kind.
		if ( isset( $request['error'] ) ) {
			$this->logger->log( "Error response: {$request['error']}: {$request['error_description']} {$request['error_uri']}", 'validate_authentication_request' );

			if ( 'access_denied' == $request['error'] ) {
				return new WP_Error( 'access_denied', 'Authorization cancelled.', $request );
			}
			return new WP_Error( 'unknown-error', 'An unknown error occurred.', $request );
		}

		// Make sure we have a legitimate authentication code and valid state.
		if ( ! isset( $request['code'] ) ) {
			return new WP_Error( 'no-code', 'No authentication code present in the request.', $request );
		}

		// Check the client request state.
		if ( ! isset( $request['state'] ) ) {
			return new WP_Error( 'missing-state', __( 'Missing state.', 'hello-login' ), $request );
		}

		if ( ! $this->check_state( $request['state'] ) ) {
			return new WP_Error( 'invalid-state', __( 'Invalid state.', 'hello-login' ), $request );
		}

		return $request;
	}

	/**
	 * Get the authorization code from the request
	 *
	 * @param array<string>|WP_Error $request The authentication request results.
	 *
	 * @return string|WP_Error
	 */
	public function get_authentication_code( $request ) {
		if ( ! isset( $request['code'] ) ) {
			return new WP_Error( 'missing-authentication-code', __( 'Missing authentication code.', 'hello-login' ), $request );
		}

		return $request['code'];
	}

	/**
	 * Using the authorization_code, request an authentication token from the IDP.
	 *
	 * @param string $code The authorization code.
	 * @param string $state
	 *
	 * @return array|WP_Error
	 */
	public function exchange_authorization_code( string $code, string $state ) {
		// Add Host header - required for when the openid-connect endpoint is behind a reverse-proxy.
		$parsed_url = parse_url( $this->endpoint_token );
		$host = $parsed_url['host'];

		$state_object  = get_transient( 'hello-login-state--' . $state );
		$code_verifier = $state_object[ $state ]['code_verifier'] ?? '';

		$request = array(
			'body' => array(
				'code'          => $code,
				'client_id'     => $this->client_id,
				'redirect_uri'  => $this->redirect_uri,
				'grant_type'    => 'authorization_code',
				'scope'         => $this->scope,
				'code_verifier' => $code_verifier,
			),
			'headers' => array( 'Host' => $host ),
			'timeout' => $this->http_request_timeout,
		);

		// Call the server and ask for a token.
		$this->logger->log( $this->endpoint_token, 'exchange_authorization_code' );
		$response = wp_remote_post( $this->endpoint_token, $request );

		if ( is_wp_error( $response ) ) {
			$response->add( 'exchange_authorization_code', __( 'Request for authentication token failed.', 'hello-login' ) );
		}

		return $response;
	}

	/**
	 * Extract and decode the token body of a token response
	 *
	 * @param array|WP_Error $token_result The token response.
	 *
	 * @return array|WP_Error
	 */
	public function get_token_response( $token_result ) {
		if ( ! isset( $token_result['body'] ) ) {
			return new WP_Error( 'missing-token-body', __( 'Missing token body.', 'hello-login' ), $token_result );
		}

		// Extract the token response from token.
		$token_response = json_decode( $token_result['body'], true );

		// Check that the token response body was parsed.
		if ( is_null( $token_response ) ) {
			return new WP_Error( 'invalid-token', __( 'Invalid token.', 'hello-login' ), $token_result );
		}

		if ( isset( $token_response['error'] ) ) {
			$error = $token_response['error'];
			$error_description = $error;
			if ( isset( $token_response['error_description'] ) ) {
				$error_description = $token_response['error_description'];
			}
			return new WP_Error( $error, $error_description, $token_result );
		}

		return $token_response;
	}

	/**
	 * Generate a new state, save it as a transient, and return the state hash.
	 *
	 * @param string $redirect_to        The redirect URL to be used after IDP authentication.
	 * @param string $pkce_code_verifier The PKCE code verifier to be sent during the authorization code exchange request.
	 *
	 * @return string
	 */
	public function new_state( string $redirect_to, string $pkce_code_verifier = '' ): string {
		// New state w/ timestamp.
		$state = md5( mt_rand() . microtime( true ) );
		$state_value = array(
			$state => array(
				'redirect_to'   => $redirect_to,
				'code_verifier' => $pkce_code_verifier,
			),
		);
		set_transient( 'hello-login-state--' . $state, $state_value, $this->state_time_limit );

		return $state;
	}

	/**
	 * Check the existence of a given state transient.
	 *
	 * @param string $state The state hash to validate.
	 *
	 * @return bool
	 */
	public function check_state( string $state ): bool {
		return boolval( get_transient( 'hello-login-state--' . $state ) );
	}

	/**
	 * Get the authorization state from the request
	 *
	 * @param array<string> $request The authentication request results.
	 *
	 * @return string|WP_Error
	 */
	public function get_authentication_state( array $request ) {
		if ( ! isset( $request['state'] ) ) {
			return new WP_Error( 'missing-authentication-state', __( 'Missing authentication state.', 'hello-login' ), $request );
		}

		return sanitize_text_field( wp_unslash( $request['state'] ) );
	}

	/**
	 * Ensure that the token meets basic requirements.
	 *
	 * @param array $token_response The token response.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_token_response( array $token_response ) {
		/*
		 * Ensure 2 specific items exist with the token response in order
		 * to proceed with confidence:  id_token and token_type == 'Bearer'
		 */
		if ( ! isset( $token_response['id_token'] ) ||
			 ! isset( $token_response['token_type'] ) || strcasecmp( $token_response['token_type'], 'Bearer' )
		) {
			return new WP_Error( 'invalid-token-response', 'Invalid token response', $token_response );
		}

		return true;
	}

	/**
	 * Extract the id_token_claim from the token_response.
	 *
	 * @param array $token_response The token response.
	 *
	 * @return array|WP_Error
	 */
	public function get_id_token_claim( array $token_response ) {
		// Validate there is an id_token.
		if ( ! isset( $token_response['id_token'] ) ) {
			return new WP_Error( 'no-identity-token', __( 'No identity token.', 'hello-login' ), $token_response );
		}

		// Break apart the id_token in the response for decoding.
		$tmp = explode( '.', $token_response['id_token'] );

		if ( ! isset( $tmp[1] ) ) {
			return new WP_Error( 'missing-identity-token', __( 'Missing identity token.', 'hello-login' ), $token_response );
		}

		// Extract the id_token's claims from the token.
		$id_token = json_decode(
			base64_decode(
				str_replace( // Because token is encoded in base64 URL (and not just base64).
					array( '-', '_' ),
					array( '+', '/' ),
					$tmp[1]
				)
			),
			true
		);

		if ( ! is_array( $id_token ) ) {
			return new WP_Error( 'invalid-id-claim', __( 'Invalid Id Token', 'hello-login' ), $id_token );
		}

		return $id_token;
	}

	/**
	 * Ensure the id_token_claim contains the required values.
	 *
	 * @param array $id_token_claim The ID token claim.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_id_token_claim( array $id_token_claim ) {
		// Validate the identification data and it's value.
		if ( empty( $id_token_claim['sub'] ) ) {
			return new WP_Error( 'no-subject-identity', __( 'No subject identity.', 'hello-login' ), $id_token_claim );
		}

		// Allow for errors from the IDP.
		if ( isset( $id_token_claim['error'] ) ) {
			if ( empty( $id_token_claim['error_description'] ) ) {
				$message = __( 'Error from the IDP.', 'hello-login' );
			} else {
				$message = $id_token_claim['error_description'];
			}
			return new WP_Error( 'invalid-id-token-claim-' . $id_token_claim['error'], $message, $id_token_claim );
		}

		// Allow for other plugins to alter the login success.
		$login_user = apply_filters( 'hello-login-user-login-test', true, $id_token_claim );

		if ( ! $login_user ) {
			return new WP_Error( 'unauthorized', __( 'Unauthorized access.', 'hello-login' ), $login_user );
		}

		return true;
	}

	/**
	 * Retrieve the subject identity from the id_token.
	 *
	 * @param array $id_token_claim The ID token claim.
	 *
	 * @return string
	 */
	public function get_subject_identity( array $id_token_claim ): string {
		return $id_token_claim['sub'];
	}

}
