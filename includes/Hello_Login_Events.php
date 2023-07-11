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
 * Endpoint and handlers for Push-Based Security Event Token (SET) delivery. This endpoint handles Hellō specific
 * events.
 *
 * @see https://openid.net/wg/sharedsignals/                       Shared Signals – A Secure Webhooks Framework
 * @see https://openid.net/specs/openid-sse-framework-1_0-ID1.html OpenID Shared Signals and Events Framework Specification 1.0 - draft 01
 * @see https://www.rfc-editor.org/rfc/rfc8935.html                RFC 8935 - Push-Based Security Event Token (SET) Delivery Using HTTP
 *
 * @package Hello_Login
 */
class Hello_Login_Events {

	/**
	 * Event URI for the invite creation.
	 *
	 * @var string
	 */
	const INVITE_CREATED_EVENT_URI = 'https://hello.coop/invite/created';

	/**
	 * Event URI for the invite creation.
	 *
	 * @var string
	 */
	const INVITE_DECLINED_EVENT_URI = 'https://hello.coop/invite/declined';

	/**
	 * Event URI for the invite creation.
	 *
	 * @var string
	 */
	const INVITE_RETRACTED_EVENT_URI = 'https://hello.coop/invite/retracted';

	/**
	 * Event URI for the federation groups sync.
	 *
	 * @var string
	 */
	const FEDERATION_GROUPS_SYNC_EVENT_URI = 'https://hello.coop/federation/groups/sync';

	/**
	 * Event URI for the federation user sync.
	 *
	 * @var string
	 */
	const FEDERATION_USER_SYNC_EVENT_URI = 'https://hello.coop/federation/user/sync';

	/**
	 * Event URI for the federation user disable.
	 *
	 * @var string
	 */
	const FEDERATION_USER_DISABLE_EVENT_URI = 'https://hello.coop/federation/user/disable';

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
	 * Users service.
	 *
	 * @var Hello_Login_Users $users
	 */
	private Hello_Login_Users $users;

	/**
	 * Federation groups logic.
	 *
	 * @var Hello_Login_Federation_Groups
	 */
	private Hello_Login_Federation_Groups $federation_groups;

	/**
	 * The class constructor.
	 *
	 * @param Hello_Login_Option_Logger   $logger   The plugin logger instance.
	 * @param Hello_Login_Option_Settings $settings The plugin settings object instance.
	 * @param Hello_Login_Users           $users    The users service.
	 */
	public function __construct( Hello_Login_Option_Logger $logger, Hello_Login_Option_Settings $settings, Hello_Login_Users $users ) {
		$this->logger = $logger;
		$this->settings = $settings;
		$this->users = $users;
		$this->federation_groups = new Hello_Login_Federation_Groups( $this->logger );
	}

	/**
	 * Hook the client into WordPress.
	 *
	 * @param Hello_Login_Option_Logger   $logger   The plugin logger instance.
	 * @param Hello_Login_Option_Settings $settings The plugin settings instance.
	 * @param Hello_Login_Users           $users    The users service.
	 *
	 * @return Hello_Login_Events
	 */
	public static function register( Hello_Login_Option_Logger $logger, Hello_Login_Option_Settings $settings, Hello_Login_Users $users ): Hello_Login_Events {
		$invites  = new self( $logger, $settings, $users );

		return $invites;
	}

	/**
	 * Check current user permissions to decide if they can invite users.
	 *
	 * @return bool
	 */
	public static function can_invite(): bool {
		return current_user_can( 'create_users' );
	}

	/**
	 * Add Hellō user invites to the top of add new user form.
	 *
	 * @return void
	 */
	public function hello_login_in_admin_header_invite() {
		$inviter_id = Hello_Login_Users::get_hello_sub();
		if ( empty( $inviter_id ) ) {
			return;
		}
		$return_uri = admin_url( 'user-new.php' );
		$initiated_login_uri = site_url( '?hello-login=start' );
		$event_uri = site_url( '?hello-login=event' );
		$local = str_contains( $event_uri, 'localhost' ) // TODO: use an env var set in docker-compose to determine local.
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
								<?php self::hello_invite_dropdown_roles(); ?>
							</select>
							<input type="hidden" name="inviter" value="<?php print esc_attr( $inviter_id ); ?>" />
							<input type="hidden" name="return_uri" value="<?php print esc_attr( $return_uri ); ?>" />
							<input type="hidden" name="initiate_login_uri" value="<?php print esc_attr( $initiated_login_uri ); ?>" />
							<input type="hidden" name="client_id" value="<?php print esc_attr( $this->settings->client_id ); ?>" />
							<input type="hidden" name="prompt" value="Subscriber to <?php print esc_attr( get_bloginfo( 'name' ) ); ?>" />
							<input type="hidden" name="events_uri" value="<?php print esc_attr( $event_uri ); ?>" />
							<?php if ( $local ) { ?>
							<input type="hidden" name="local" value="true" />
							<?php } ?>
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
	 * Generate and output the HTML option list of roles available for the Hellō invitation.
	 *
	 * @return void
	 */
	public static function hello_invite_dropdown_roles() {
		$options_html = '';
		$selected = get_option( 'default_role' );
		$roles = array_reverse( get_editable_roles() );

		if ( ! current_user_can( 'promote_users' ) ) {
			$roles = array_filter(
				$roles,
				function ( $role ) {
					return 'subscriber' == $role;
				},
				ARRAY_FILTER_USE_KEY
			);
		}

		foreach ( $roles as $role => $details ) {
			$name = translate_user_role( $details['name'] );
			// Preselect specified role.
			if ( $selected === $role ) {
				$options_html .= "\n\t<option selected='selected' value='" . esc_attr( $role ) . "'>$name</option>";
			} else {
				$options_html .= "\n\t<option value='" . esc_attr( $role ) . "'>$name</option>";
			}
		}

		echo $options_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handle the event endpoint.
	 *
	 * @return void
	 */
	public function handle_event() {
		$this->validate_request();

		$body = file_get_contents( 'php://input' );

		$event = $this->decode_event( $body );

		if ( is_wp_error( $event ) ) {
			http_response_code( 400 );
			exit();
		}

		$res = $this->validate_event( $event );

		if ( is_wp_error( $res ) ) {
			http_response_code( 400 );
			exit();
		}

		$this->logger->log( $event, 'events' );

		foreach ( $event['events'] as $type => $sub_event ) {
			switch ( $type ) {
				case self::INVITE_CREATED_EVENT_URI:
					$this->handle_invite_created( $event, $sub_event, $body );
					break;
				case self::INVITE_RETRACTED_EVENT_URI:
					$this->handle_retracted( $event );
					break;
				case self::INVITE_DECLINED_EVENT_URI:
					$this->handle_declined( $event );
					break;
				case self::FEDERATION_GROUPS_SYNC_EVENT_URI:
					$this->handle_federation_groups_sync( $sub_event );
					break;
				case self::FEDERATION_USER_SYNC_EVENT_URI:
				case self::FEDERATION_USER_DISABLE_EVENT_URI:
					$this->logger->log( "Event type not implemented: $type", 'events' );
					break;
				default:
					$this->logger->log( "Unknown event type: $type", 'events' );
			}
		}
	}

	/**
	 * Validate HTTP request.
	 *
	 * @return void
	 */
	protected function validate_request() {
		$request_method = '';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
		}
		if ( 'POST' !== $request_method ) {
			$this->logger->log( "POST method expected, got: $request_method", 'events' );
			http_response_code( 405 );
			exit();
		}

		if ( ! isset( $_SERVER['CONTENT_LENGTH'] ) ) {
			$this->logger->log( 'Content length missing', 'events' );
			http_response_code( 411 );
			exit();
		}

		$content_length = intval( sanitize_text_field( wp_unslash( $_SERVER['CONTENT_LENGTH'] ) ) );
		if ( $content_length > 1024 * 1024 ) {
			$this->logger->log( "Content length too large: $content_length", 'events' );
			http_response_code( 413 );
			exit();
		}

		$content_type = '';
		if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
			$content_type = explode( ';', sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ), 2 )[0];
		}
		if ( 'application/secevent+jwt' !== $content_type ) {
			$this->logger->log( "Invalid content type: $content_type", 'events' );
			http_response_code( 400 );
			exit();
		}

		$accept = '';
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			$accept = explode( ';', sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ), 2 )[0];
		}
		if ( 'application/json' !== $accept ) {
			$this->logger->log( "Invalid accept header: $accept", 'events' );
			http_response_code( 400 );
			exit();
		}
	}

	/**
	 * Handle an incoming invite created event.
	 *
	 * @param array  $event         The full invite created event.
	 * @param array  $sub_event     The nested invite created event.
	 * @param string $encoded_event The raw encoded event.
	 *
	 * @return void
	 */
	protected function handle_invite_created( array $event, array $sub_event, string $encoded_event ) {
		$sub = $event['sub'];
		$email = $event['email'];
		$role = $sub_event['role'];

		if ( is_null( get_role( $role ) ) ) {
			$this->logger->log( "role not found: $role", 'invites' );
			http_response_code( 404 );
			exit();
		}

		$inviter_sub = $sub_event['inviter']['sub'];

		$inviter = $this->users->get_user_by_identity( $inviter_sub );

		if ( empty( $inviter ) ) {
			$this->logger->log( "inviter not found: $inviter_sub", 'invites' );
			http_response_code( 404 );
			exit();
		}

		if ( ! user_can( $inviter, 'create_users' ) ) {
			$this->logger->log( "inviter with no create_user: $inviter_sub", 'invites' );
			http_response_code( 403 );
		}

		if ( ! user_can( $inviter, 'promote_users' ) && 'subscriber' != $role ) {
			$this->logger->log( "inviter cannot promote users: $inviter_sub, role: $role", 'invites' );
			http_response_code( 403 );
			exit();
		}

		$user = $this->users->get_user_by_identity( $sub );

		if ( ! empty( $user ) ) {
			if ( ! in_array( $role, $user->roles ) ) {
				$user->set_role( $role );

				Hello_Login_Users::update_invite_created( $user, $event );
			}

			Hello_Login_Users::update_last_token( $user, $encoded_event );

			return;
		}

		$new_user = false === email_exists( $email );

		$user_data = array(
			'user_login' => $email,
			'user_email' => $email,
			'role'       => $role,
		);

		$user = $this->users->create_new_user( $sub, $user_data, true );

		if ( is_wp_error( $user ) ) {
			$this->logger->log( 'User creation failed', 'invites' );
			http_response_code( 400 );
			exit();
		}

		Hello_Login_Users::update_last_token( $user, $encoded_event );
		Hello_Login_Users::update_invite_created( $user, $event );
		if ( $new_user ) {
			Hello_Login_Users::set_invited_unused( $user );
		}

		http_response_code( 202 );
	}

	/**
	 * Handle an incoming invite retracted event.
	 *
	 * @param array $event The full invite created event.
	 *
	 * @return void
	 */
	protected function handle_retracted( array $event ) {
		self::delete_unused_invited_user( $event['sub'], 'retracted' );

		http_response_code( 202 );
	}

	/**
	 * Handle an incoming invite declined event.
	 *
	 * @param array $event The full invite created event.
	 *
	 * @return void
	 */
	protected function handle_declined( array $event ) {
		self::delete_unused_invited_user( $event['sub'], 'declined' );

		http_response_code( 202 );
	}

	/**
	 * Remove an unused user that was created through a Hellō invite.
	 *
	 * @param string $sub        The Hellō subject identifier of the user to be deleted.
	 * @param string $event_type The even type that triggerred this deletion: retracted or declined. Used in logging.
	 *
	 * @return void
	 */
	private function delete_unused_invited_user( string $sub, string $event_type ) {
		$user = $this->users->get_user_by_identity( $sub );

		if ( empty( $user ) ) {
			$this->logger->log( "Cannot handle invite $event_type, user not found: $sub", 'invites' );
			http_response_code( 404 );
			exit();
		}

		if ( Hello_Login_Users::is_invited_unused( $user ) ) {
			if ( wp_delete_user( $user->ID ) ) {
				$this->logger->log( "Deleted unused user on invite $event_type: $sub", 'invites' );
			} else {
				$this->logger->log( "Failed deleting unused user on invite $event_type: $sub ($user->ID)", 'invites' );
				http_response_code( 500 );
				exit();
			}
		} else {
			$this->logger->log( "Cannot delete used user on invite $event_type: $sub ($user->ID)", 'invites' );
			http_response_code( 409 );
			exit();
		}
	}

	/**
	 * Decode the JWT corresponding to and event.
	 *
	 * TODO: use the introspection endpoint when available.
	 *
	 * @see $this->settings->endpoint_introspection
	 * @see https://www.hello.dev/documentation/Integrating-hello.html#_5-1-introspection
	 *
	 * @param string $event_jwt
	 *
	 * @return array|WP_Error
	 */
	public function decode_event( string $event_jwt ) {
		// TODO: use JWT parser, validate header and also payload iss and aud before using the introspection endpoint.
		$jwt_parts = explode( '.', $event_jwt );

		if ( 3 != count( $jwt_parts ) ) {
			$this->logger->log( "Invalid event, not 3 parts: $event_jwt", 'decode_event' );

			return new WP_Error( 'invalid_event', 'not 3 parts' );
		}

		// TODO: decode and validate header as well: check alg == 'RS256' and typ == 'application/secevent+jwt'.
		$payload_b64 = $jwt_parts[1];
		$payload_b64 = str_replace( '_', '/', str_replace( '-', '+', $payload_b64 ) );

		$payload_json = base64_decode( $payload_b64, true );
		if ( false === $payload_json ) {
			$this->logger->log( "Invalid event, base64 decode of payload failed: $payload_b64", 'decode_event' );

			return new WP_Error( 'invalid_event', 'base64 decode failed' );
		}

		$payload_array = json_decode( $payload_json, true );

		if ( ! is_array( $payload_array ) ) {
			$this->logger->log( "Invalid event, JSON decode of payload failed: $payload_json", 'decode_event' );

			return new WP_Error( 'invalid_event', 'json decode failed' );
		}

		return $payload_array;
	}

	/**
	 * General validation for events.
	 *
	 * @param array $event The event.
	 *
	 * @return WP_Error|true
	 */
	public function validate_event( array $event ) {
		$iss = $event['iss'];
		$aud = $event['aud'];

		if ( Hello_Login_Util::hello_issuer( $this->settings->endpoint_login ) !== $iss ) {
			$this->logger->log( "Invalid issuer: $iss", 'events' );
			return new WP_Error( 'invalid_event', 'invalid issuer' );
		}

		if ( $this->settings->client_id !== $aud ) {
			$this->logger->log( "Invalid audience: $aud", 'events' );
			return new WP_Error( 'invalid_event', 'invalid audience' );
		}

		// A transient could be saved and checked, based on $event['jti'], to prevent duplicate submissions. Since all
		// events can be repeated with no side effects, this is not implemented for now.

		return true;
	}

	/**
	 * Handle a federation groups sync event.
	 *
	 * @param array $sub_event The federation groups sync sub-event.
	 *
	 * @return void
	 */
	protected function handle_federation_groups_sync( array $sub_event ) {
		$res = $this->federation_groups->sync( $sub_event );

		if ( is_wp_error( $res ) ) {
			$this->logger->log( $res, 'handle_federation_groups_sync' );
			http_response_code( 400 );
			exit();
		}

		http_response_code( 202 );
	}
}
