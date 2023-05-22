<?php
/**
 * MailHog PhpMailer Setup
 *
 * @package Hello_Login_MuPlugins
 *
 * @wordpress-plugin
 * Plugin Name: MailHog PhpMailer Setup
 * Description: Establishes a connection between the PhpMailer library and the MailHog local-dev Docker container.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provides the configuration for PhpMailer to use MailHog.
 *
 * @param PHPMailer $phpmailer The PHPMailer instance.
 *
 * @return void
 */
function mailhog_phpmailer_setup( PHPMailer $phpmailer ) {
	// PHPMailer doesn't follow WordPress naming conventions so this can be ignored.
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->Host = 'mailhog';

	// PHPMailer doesn't follow WordPress naming conventions so this can be ignored.
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->Port = 1025;

	$phpmailer->SMTPAuth = false;
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->SMTPSecure = '';
	$phpmailer->FromName = 'Admin';
	$phpmailer->From = 'admin@localhost';
//	$phpmailer->Sender = 'admin@localhost';

	$phpmailer->isSMTP();
}

add_action( 'phpmailer_init', 'mailhog_phpmailer_setup' );
