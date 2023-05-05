<?php
/**
 * Utility functions.
 *
 * @package   Hello_Login
 * @category  Util
 * @author    Marius Scurtescu <marius.scurtescu@hello.coop>
 * @copyright 2023 Hello Identity Co-op
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Hello_Login_Util class.
 *
 * Utility functions.
 *
 * @package Hello_Login
 * @category  Util
 */
class Hello_Login_Util {
	/**
	 * Default scopes added to all auth requests.
	 *
	 * @var array<string>
	 */
	public const DEFAULT_SCOPES = array( 'openid', 'email', 'name' );

	/**
	 * Remove the duplicates from a space separate scope list.
	 *
	 * @param string $scope Space separate scope to remove duplicates from.
	 * @return string A space separate list of scopes.
	 */
	public static function remove_duplicate_scopes( string $scope ): string {
		$scope = trim( $scope );
		if ( empty( $scope ) ) {
			return '';
		}

		$scope_arr = explode( ' ', $scope );

		return implode( ' ', array_unique( $scope_arr ) );
	}

	/**
	 * Extract the path and query part from a URL.
	 *
	 * @param string $url The URL to extract from.
	 *
	 * @return string
	 */
	public static function extract_path_and_query( string $url ): string {
		$result = '/';

		$parts = parse_url( $url );

		if ( ! empty( $parts['path'] ) ) {
			$result = $parts['path'];
		}

		if ( isset( $parts['query'] ) ) {
			$result .= '?' . $parts['query'];
		}

		if ( isset( $parts['fragment'] ) ) {
			$result .= '#' . $parts['fragment'];
		}

		return $result;
	}

	/**
	 * Remove the default scopes from a space separate scope list.
	 *
	 * @param string $scope Space separate scope to remove default scopes from.
	 *
	 * @return string A space separate list of scopes.
	 */
	public static function remove_default_scopes( string $scope ): string {
		$scope = trim( $scope );
		if ( empty( $scope ) ) {
			return '';
		}

		$scope_arr = explode( ' ', $scope );
		$result    = array();

		foreach ( $scope_arr as $s ) {
			if ( ! in_array( $s, self::DEFAULT_SCOPES ) ) {
				$result[] = $s;
			}
		}

		return implode( ' ', $result );
	}

	/**
	 * Add the default scopes to a space separate scope list.
	 *
	 * @param string $scope Space separate scope to add default scopes to.
	 *
	 * @return string A space separate list of scopes.
	 */
	public static function add_default_scopes( string $scope ): string {
		$scope = trim( $scope );
		if ( empty( $scope ) ) {
			return implode( ' ', self::DEFAULT_SCOPES );
		}

		$scope_arr = explode( ' ', $scope );

		foreach ( self::DEFAULT_SCOPES as $ds ) {
			if ( ! in_array( $ds, $scope_arr ) ) {
				$scope_arr[] = $ds;
			}
		}

		return implode( ' ', $scope_arr );
	}

	/**
	 * Get the Hellō issuer string based on the auth endpoint URL.
	 *
	 * @param string $endpoint_login The auth endpoint URL.
	 *
	 * @return string The issuer string.
	 */
	public static function hello_issuer( string $endpoint_login ): string {
		$p = parse_url( $endpoint_login );
		$issuer_host = str_replace( 'wallet.', 'issuer.', $p['host'] );

		return "{$p['scheme']}://{$issuer_host}";
	}
}
