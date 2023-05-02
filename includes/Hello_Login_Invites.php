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
			<form action="https://wallet.hello.coop/invite" method="get">
				<table class="form-table">
					<tbody>
					<tr class="form-field">
						<th scope="row"><label for="invite_role">Role </label></th>
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

	}
}
