<?php
/**
 * WordPress options handling class.
 *
 * @package   Hello_Login
 * @category  Settings
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Option_Settings class.
 *
 * WordPress options handling.
 *
 * @package Hello_Login
 * @category  Settings
 *
 * OAuth Client Settings:
 *
 * @property string $client_id            The ID the client will be recognized as when connecting to the Identity provider.
 * @property string $scope                The list of additional scopes this client should access.
 * @property string $endpoint_login       The Hellō authorization endpoint URL.
 * @property string $endpoint_token       The Hellō token endpoint URL.
 * @property string $endpoint_quickstart  The Hellō Quickstart URL.
 * @property string $endpoint_invite      The Hellō invite endpoint URL.
 * @property string $endpoint_introspect  The Hellō introspect endpoint URL.
 * @property string $provider_hint        The provider hint.
 *
 * Non-standard Settings:
 *
 * @property int    $http_request_timeout   The timeout for requests made to the IDP. Default value is 5.
 * @property string $displayname_format     The key(s) in the user claim array to formulate the user's display name.
 * @property int    $state_time_limit       The valid time limit of the state, in seconds. Defaults to 180 seconds.
 *
 * Plugin Settings:
 *
 * @property bool $token_refresh_enable     The flag whether to support refresh tokens by IDPs.
 * @property bool $link_existing_users      The flag to indicate whether to link to existing WordPress-only accounts or return an error.
 * @property bool $create_if_does_not_exist The flag to indicate whether to create new users or not.
 * @property bool $redirect_user_back       The flag to indicate whether to redirect the user back to the page on which they started.
 * @property bool $enable_logging           The flag to enable/disable logging.
 * @property int  $log_limit                The maximum number of log entries to keep.
 * @property int  $link_not_now             On settings page do not prompt to link account.
 */
class Hello_Login_Option_Settings {

	/**
	 * WordPress option name/key.
	 *
	 * @var string
	 */
	private string $option_name;

	/**
	 * Stored option values array.
	 *
	 * @var array
	 */
	private array $values;

	/**
	 * Default plugin settings values.
	 *
	 * @var array
	 */
	private array $default_settings;

	/**
	 * List of settings that can be defined by environment variables.
	 *
	 * @var array<string,string>
	 */
	private array $environment_settings = array(
		'client_id'                 => 'HELLO_LOGIN_CLIENT_ID',
		'endpoint_login'            => 'HELLO_LOGIN_ENDPOINT_LOGIN_URL',
		'endpoint_token'            => 'HELLO_LOGIN_ENDPOINT_TOKEN_URL',
		'endpoint_quickstart'       => 'HELLO_LOGIN_ENDPOINT_QUICKSTART_URL',
		'endpoint_invite'           => 'HELLO_LOGIN_ENDPOINT_INVITE_URL',
		'endpoint_introspect'       => 'HELLO_LOGIN_ENDPOINT_INTROSPECT_URL',
		'scope'                     => 'HELLO_LOGIN_CLIENT_SCOPE',
		'create_if_does_not_exist'  => 'HELLO_LOGIN_CREATE_IF_DOES_NOT_EXIST',
		'link_existing_users'       => 'HELLO_LOGIN_LINK_EXISTING_USERS',
		'redirect_user_back'        => 'HELLO_LOGIN_REDIRECT_USER_BACK',
	);

	/**
	 * The class constructor.
	 *
	 * @param string $option_name       The option name/key.
	 * @param array  $default_settings  The default plugin settings values.
	 * @param bool   $granular_defaults The granular defaults.
	 */
	public function __construct( string $option_name, array $default_settings = array(), bool $granular_defaults = true ) {
		$this->option_name = $option_name;
		$this->default_settings = $default_settings;
		$this->values = array();

		if ( ! empty( $this->option_name ) ) {
			$this->values = (array) get_option( $this->option_name, $this->default_settings );
		}

		// For each defined environment variable/constant be sure the settings key is set.
		foreach ( $this->environment_settings as $key => $constant ) {
			if ( defined( $constant ) ) {
				$this->__set( $key, constant( $constant ) );
			}
		}

		if ( $granular_defaults ) {
			$this->values = array_replace_recursive( $this->default_settings, $this->values );
		}
	}

	/**
	 * Magic getter for settings.
	 *
	 * @param string $key The array key/option name.
	 *
	 * @return mixed
	 */
	public function __get( string $key ) {
		if ( isset( $this->values[ $key ] ) ) {
			return $this->values[ $key ];
		}

		return null;
	}

	/**
	 * Magic setter for settings.
	 *
	 * @param string $key   The array key/option name.
	 * @param mixed  $value The option value.
	 *
	 * @return void
	 */
	public function __set( string $key, $value ) {
		$this->values[ $key ] = $value;
	}

	/**
	 * Magic method to check is an attribute isset.
	 *
	 * @param string $key The array key/option name.
	 *
	 * @return bool
	 */
	public function __isset( string $key ): bool {
		return isset( $this->values[ $key ] );
	}

	/**
	 * Magic method to clear an attribute.
	 *
	 * @param string $key The array key/option name.
	 *
	 * @return void
	 */
	public function __unset( string $key ) {
		unset( $this->values[ $key ] );
	}

	/**
	 * Get the plugin settings array.
	 *
	 * @return array
	 */
	public function get_values(): array {
		return $this->values;
	}

	/**
	 * Get the plugin WordPress options name.
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return $this->option_name;
	}

	/**
	 * Save the plugin options to the WordPress options table.
	 *
	 * @return void
	 */
	public function save() {

		// For each defined environment variable/constant be sure it isn't saved to the database.
		foreach ( $this->environment_settings as $key => $constant ) {
			if ( defined( $constant ) ) {
				$this->__unset( $key );
			}
		}

		update_option( $this->option_name, $this->values );
	}
}
