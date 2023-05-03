<?php
/**
 * Hello Invites logic and handler.
 *
 * @package   Hello_Login
 * @category  Login
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2023  Hello Identity Co-op
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Invites class.
 *
 * Hello Invites logic and handler.
 *
 * @package Hello_Login
 */
class Hello_Login_Invites {

	/**
	 * Plugin logger.
	 *
	 * @var Hello_Login_Option_Logger
	 */
	private Hello_Login_Option_Logger $logger;

	/**
	 * Plugin settings object.
	 *
	 * @var Hello_Login_Option_Settings
	 */
	private Hello_Login_Option_Settings $settings;

	/**
	 * The class constructor.
	 *
	 * @param Hello_Login_Option_Logger   $logger   Plugin logs.
	 * @param Hello_Login_Option_Settings $settings A plugin settings object instance.
	 */
	public function __construct( Hello_Login_Option_Logger $logger, Hello_Login_Option_Settings $settings ) {
		$this->logger = $logger;
		$this->settings = $settings;
	}

	/**
	 * Hook the client into WordPress.
	 *
	 * @param Hello_Login_Option_Logger   $logger   The plugin logger instance.
	 * @param Hello_Login_Option_Settings $settings The plugin settings instance.
	 *
	 * @return Hello_Login_Invites
	 */
	public static function register( Hello_Login_Option_Logger $logger, Hello_Login_Option_Settings $settings ): Hello_Login_Invites {
		$invites  = new self( $logger, $settings );

		if ( is_admin() && 'user-new.php' == $GLOBALS['pagenow'] ) {
			add_action( 'in_admin_header', array( $invites, 'hello_login_in_admin_header_invite' ) );
		}

		return $invites;
	}

	/**
	 * Add Hellō user invites to the top of add new user form.
	 *
	 * @return void
	 */
	public function hello_login_in_admin_header_invite() {
		$inviter_id = get_user_meta( get_current_user_id(), 'hello-login-subject-identity', true );
		if ( empty( $inviter_id ) ) {
			return;
		}
		$return_uri = admin_url( 'user-new.php' );
		$initiated_login_uri = site_url( '?hello-login=start' );
		$event_uri = site_url( '?hello-login=event' );
		?>
		<div class="wrap">
			<h1 id="invite-new-user">Invite New User</h1>
			<p>Invite new users to this site.</p>
			<form action="<?php print esc_attr( $this->settings->endpoint_invite ); ?>" method="get">
				<table class="form-table">
					<tbody>
					<tr class="form-field">
						<th scope="row"><label for="invite-user-role">Role </label></th>
						<td>
							<select name="role" id="invite-user-role">
								<?php wp_dropdown_roles( get_option( 'default_role' ) ); ?>
							</select>
							<input type="hidden" name="inviter" value="<?php print esc_attr( $inviter_id ); ?>" />
							<input type="hidden" name="return_uri" value="<?php print esc_attr( $return_uri ); ?>" />
							<input type="hidden" name="initiated_login_uri" value="<?php print esc_attr( $initiated_login_uri ); ?>" />
							<input type="hidden" name="client_id" value="<?php print esc_attr( $this->settings->client_id ); ?>" />
							<input type="hidden" name="prompt" value="Subscriber to <?php print esc_attr( get_bloginfo( 'name' ) ); ?>" />
							<input type="hidden" name="event_uri" value="<?php print esc_attr( $event_uri ); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th scope="row"></th>
						<td>
							<button type="submit" class="hello-btn" data-label="ō&nbsp;&nbsp;&nbsp;Invite with Hellō">Invite with Hellō</button>
						</td>
					</tr>
					</tbody>
				</table>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the event endpoint.
	 *
	 * @return void
	 */
	public function handle_event() {
		$request_method = '';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
		}
		if ( 'POST' !== $request_method ) {
			$this->logger->log( "POST method expected, got: $request_method", 'invites' );
			http_response_code( 405 );
			exit();
		}

		if ( ! isset( $_SERVER['CONTENT_LENGTH'] ) ) {
			$this->logger->log( 'Content length missing', 'invites' );
			http_response_code( 411 );
			exit();
		}

		$content_length = intval( sanitize_text_field( wp_unslash( $_SERVER['CONTENT_LENGTH'] ) ) );
		if ( $content_length > 1024 * 1024 ) {
			$this->logger->log( "Content length too large: $content_length", 'invites' );
			http_response_code( 413 );
			exit();
		}

		$content_type = '';
		if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
			$content_type = explode( ';', sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ), 2 )[0];
		}
		if ( 'application/json' !== $content_type ) {
			$this->logger->log( "Invalid content type: $content_type", 'invites' );
			http_response_code( 400 );
			exit();
		}

		$body = file_get_contents( 'php://input' );

		$event = $this->decode_event( $body );

		if ( is_wp_error( $event ) ) {
			http_response_code( 400 );
			exit();
		}

		$this->logger->log( $event, 'invites' );
	}

	/**
	 * Decode the JWT corresponding to and event.
	 *
	 * @param string $event_jwt
	 *
	 * @return array|WP_Error
	 */
	public function decode_event( string $event_jwt ) {
		// TODO: use the introspection endpoint when available
		//       $this->settings->endpoint_introspection
		//       https://www.hello.dev/documentation/Integrating-hello.html#_5-1-introspection
		$jwt_parts = explode( '.', $event_jwt );

		if ( 3 != count( $jwt_parts ) ) {
			$this->logger->log( "Invalid event, not 3 parts: $event_jwt", 'decode_event' );

			return new WP_Error( 'invalid_event' );
		}

		$payload_b64 = $jwt_parts[1];
		$payload_b64 = str_replace( '_', '/', str_replace( '-', '+', $payload_b64 ) );

		$payload_json = base64_decode( $payload_b64, true );
		if ( false === $payload_json ) {
			$this->logger->log( "Invalid event, base64 decode of payload failed: $payload_b64", 'decode_event' );

			return new WP_Error( 'invalid_event' );
		}

		$payload_array = json_decode( $payload_json, true );

		if ( 'array' !== gettype( $payload_array ) ) {
			$this->logger->log( "Invalid event, JSON decode of payload failed: $payload_json", 'decode_event' );

			return new WP_Error( 'invalid_event' );
		}

		return $payload_array;
	}
}
