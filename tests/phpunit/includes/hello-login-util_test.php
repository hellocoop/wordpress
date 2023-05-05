<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class Hello_Login_Util_Test extends TestCase {
	/**
	 * @dataProvider extract_path_provider
	 */
	public function test_extract_path_and_query( string $expected_path, string $url ): void {
		$this->assertSame( $expected_path, Hello_Login_Util::extract_path_and_query( $url ) );
	}

	public function extract_path_provider(): array {
		return [
			[ "/", "https://example.com" ],
			[ "/", "https://example.com/" ],
			[ "/path", "https://example.com/path" ],
			[ "/path/", "https://example.com/path/" ],
			[ "/deep/path", "https://example.com/deep/path" ],
			[ "/deep/path/", "https://example.com/deep/path/" ],
			[ "/path?n=v", "https://example.com/path?n=v" ],
			[ "/path/?n=v", "https://example.com/path/?n=v" ],
			[ "/path?n=v#f", "https://example.com/path?n=v#f" ],

			// No scheme and host name.
			[ "/", "" ],
			[ "/", "/" ],
			[ "/path", "/path" ],
			[ "/path/", "/path/" ],
			[ "/deep/path", "/deep/path" ],
			[ "/deep/path/", "/deep/path/" ],
			[ "/path?n=v", "/path?n=v" ],
			[ "/path/?n=v", "/path/?n=v" ],
			[ "/path?n=v#f", "/path?n=v#f" ],
		];
	}

	/**
	 * @dataProvider remove_default_provider
	 */
	public function test_remove_default_scopes( string $out_scope, string $in_scope ): void {
		$this->assertSame( $out_scope, Hello_Login_Util::remove_default_scopes( $in_scope ));
	}

	public function remove_default_provider(): array {
		return [
			[ "", "" ],
			[ "", " " ],
			[ "", " openid " ],
			[ "", "openid email" ],
			[ "nickname", "openid email nickname" ],
			[ "nickname", "openid nickname email" ],
			[ "nickname", "nickname openid email" ],
		];
	}

	/**
	 * @dataProvider add_default_provider
	 */
	public function test_add_default_scopes( $out_scope, $in_scope ): void {
		$this->assertSame( $out_scope, Hello_Login_Util::add_default_scopes( $in_scope ) );
	}

	public function add_default_provider(): array {
		return [
			[ "openid email name", "" ],
			[ "openid email name", " " ],
			[ "name openid email", " name " ],
			[ "nickname openid email name", "nickname" ],
			[ "nickname email openid name", "nickname email" ],
		];
	}

	/**
	 * @dataProvider remove_duplicate_provider
	 */
	public function test_remove_duplicate_scopes( string $out_scope, string $in_scope ): void {
		$this->assertSame( $out_scope, Hello_Login_Util::remove_duplicate_scopes( $in_scope ) );
	}

	public function remove_duplicate_provider(): array {
		return [
			[ "", "" ],
			[ "", " " ],
			[ "openid", " openid " ],
			[ "openid", " openid openid" ],
			[ "openid name email", " openid name openid email name" ],
		];
	}

	/**
	 * @dataProvider hello_issuer_provider
	 */
	public function test_hello_issuer( string $endpoint_url, string $issuer): void {
		$this->assertSame( $issuer, Hello_Login_Util::hello_issuer( $endpoint_url ) );
	}

	public function hello_issuer_provider(): array {
		return [
			[ 'https://wallet.hello.coop/authorize', 'https://issuer.hello.coop' ],
			[ 'http://wallet.hello.coop/authorize', 'http://issuer.hello.coop' ],
			[ 'https://wallet.hello.coop/', 'https://issuer.hello.coop' ],
			[ 'https://wallet.hello.coop', 'https://issuer.hello.coop' ],
		];
	}
}
