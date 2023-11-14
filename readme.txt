=== Hellō Login ===
Contributors: marius1hello, DickHardt, remotelychris, rohanharikr
Donate link: https://www.hello.dev/
Tags: security, login, oauth2, openidconnect, apps, authentication, sso
Requires at least: 4.9
Tested up to: 6.4
Stable tag: 1.5.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Free and simple to setup plugin provides registration and login with the Hellō Wallet. Users choose from popular social login, email, or phone.

== Description ==

Provide your users registration and login using their choice of popular social login, email, or phone. No need for you to configure your application at each provider or pay for a premium plugin.

Hellō Login verifies your users' email addresses so you don't have to. No longer do they have to manage another username and password to use your site.

Hellō is a cloud identity wallet cooperatively operated with a mission to empower users to control their identity. Learn more at [hello.coop](https://www.hello.coop/).

* Hellō Login installs with Hellō Quickstart to get you up and running in 7 clicks.
* Users manage how they login at [wallet.hello.coop](https://wallet.hello.coop). No need for you to manage how they login or help them recover their account.
* Hellō Login uses the Hellō service, which provides login and verified email for free. See [hello.dev/pricing](https://www.hello.dev/pricing/) for details.

Documentation, configuration, and settings can be found in Settings >  Hellō Login

[Watch a video](https://www.youtube.com/watch?v=kCWY3viT368) showing installation and key features, and how Hellō Login relates to popular alternatives.

== Installation ==

= Automatic Installation =

1. Search for “hello openid” through 'Plugins > Add New' interface.
1. Find the plugin box of Hellō Login and click on the **Install Now** button.
1. Activate the Hellō Login plugin, then click **Settings**.
1. Click the **Quickstart** button and complete the Quickstart flow.
1. Once back at the Hellō Login Settings page, link your admin account with your Hellō Wallet.

= Manual Installation =

1. Download [Hellō Login](https://downloads.wordpress.org/plugin/hello-login.zip).
1. Upload Hellō Login through 'Plugins > Add New > Upload' interface or upload hello-login folder to the `/wp-content/plugins/` directory.
1. Activate the Hellō Login plugin, then click **Settings**.
1. Click the **Quickstart** button and complete the Quickstart flow.
1. Once back at the Hellō Login Settings page, link your admin account with your Hellō Wallet.

== Frequently Asked Questions ==

= What is Hellō? =

Hellō is a cloud identity wallet that empowers users to prove who they are to any site that accepts Hellō. Learn more at [hello.coop](https://www.hello.coop/).

= How can users login to their wallet? =

Hellō supports all popular ways to login including Apple, Facebook, GitHub, Google, Line, Microsoft, Twitch, Twitter, Yahoo, as well as email, phone and crypto wallets. We are adding more methods on a regular basis. See [hello.coop](https://www.hello.coop/) for a complete list.

= Does Hellō sell user data? =

No. Hellō provides sites user data only with informed consent. User data is only accessible while the user has unlocked their wallet by logging in with their preferred provider.

= How does Hellō make money? =

Hellō has a freemium business model where basic claims such as login, verified email, and profile data is free. In the future we will have premium claims such as verified name, age, citizenship, residency, affiliations, and entitlements.

= How can I change the image for my site? =

You can update your site configuration at [console.hello.coop](https://console.hello.coop/).

= My blog is on Wordpress.com. Why is Hellō not showing up for log in / comments? =

If you have "Allow users to log in to this site using WordPress.com accounts" enabled under Settings / Security, then you can only use WordPress accounts for logging in, and Hellō is not available. If you have "Let visitors use a WordPress.com, Twitter, Facebook, or Google account to comment." enabled under Settings / Discussion, then you can only use WordPress for leaving comments. You will need to disable these for Hellō to be available.

= Where do I submit feature requests or bugs? =

Please submit to [https://github.com/hellocoop/wordpress/issues](https://github.com/hellocoop/wordpress/issues)

== Screenshots ==

1. `/wp-login.php` page with Hellō Login
2. Hellō Wallet login page
3. 16+ ways to login
4. Verified email address
5. Protect comments

== Changelog ==

= 1.5.4 =

* Improvement: clarifications regarding business model

= 1.5.3 =

* Improvement: WordPress 6.4 support

= 1.5.2 =

* Improvement: WordPress 6.3 support

= 1.5.1 =

* Improvement: updated screenshot list
* Improvement: added link to video showing installation and key features

= 1.5.0 =

* Improvement: tabbed Settings page

= 1.4.1 =

* Improvement: WordPress 6.2 support

= 1.4.0 =

* Improvement: added support for provider hint

= 1.3.0 =

* Improvement: added "Update Email with Hellō" functionality
* Improvement: internal restructuring for better testability and added basic unit tests
* Improvement: PHP 7.4 is the new minimum required version

= 1.2.1 =

* Fix: fixed parsing of empty scope

= 1.2.0 =

* Improvement: disable account linking on settings page when in multisite mode
* Improvement: simplified scope related settings and added scope validation
* Improvement: scroll to comment form after log in to post a comment
* Improvement: FAQ to clarify WordPress.com behavior
* Fix: handle cancelled log in attempts

= 1.1.3 =

* Improvement: set first and last name on sign-in if previously empty and if now available
* Improvement: save extra claims under user meta
* Improvement: add default scopes and reduce required scopes to `openid name email`
* Improvement: set username and nickname even if only full name is available
* Fix: alter comment links only if plugin is configured

= 1.1.2 =

* Improvement: redirect back to blog post or page after sign-in
* Improvement: use Hellō link and button to sign in to leave a comment

= 1.1.1 =

* Fix: add cache control HTTP headers to auth request start endpoint response

= 1.1.0 =

* Feature: added Hellō section to profile page with link / unlink functionality
* Feature: added admin notices for Quickstart and link/unlink actions
* Feature: redirect to settings page on plugin activation
* Improvement: more restructuring of the settings page
* Improvement: moved away from all REST APIs

= 1.0.12 =

* Improvement: restructured the settings page
* Improvement: added information about what data is being sent through Quickstart
* Improvement: increased state time limit to 10 minutes
* Improvement: updated the short description of the plugin
* Fix: logged out message on login page moved to top

= 1.0.11 =

* Improvement: disable logging by default
* Improvement: login page layout fixes and improvements
* Improvement: logins from wp-login.php redirect users to admin area
* Improvement: show "User Settings" section

= 1.0.10 =

* Improvement: show settings form in debug mode

= 1.0.9 =

* Fix: disable caching on REST API response
* Improvement: enable logging by default
* Improvement: content changes on plugin settings page

= 1.0.8 =

* Fix: use query parameter based redirect URI

= 1.0.7 =

* Fix: authentication request URL generated through REST API on button click
* Improvement: removed the WordPress User Settings section
* Improvement: removed the Authorization Settings section
* Improvement: use /hello-login/callback path for redirect URI
* Improvement: added endpoint for Quickstart response
* Fix: client id field being reset on settings save
* Fix: automatic configuration of rewrite rules

= 1.0.6 =

* Feature: added screenshots
* Update: plugin details
* Fix: plugin settings and login page redirects after connecting with Hellō

= 1.0.5 =

* Feature: added `given_name` and `family_name` scopes as defaults
* Fix: admin account linking done based on curren session
* Feature: link user account on sign-in, when account is matched on email
* Fix: map `nickname` to new username, instead of `sub`

= 1.0.4 =

* Feature: added "Settings" link right in plugin list
* Fix: show "Continue with Hellō" button on login page only if the plugin is configured

= 1.0.3 =

* Feature: added `integration` parameter to Quickstart request

= 1.0.2 =

* First release in WordPress plugin repository
* Feature: toggle settings page content based on settings and current user state
* Feature: collapse username / password form on login page
* Feature: send Privacy Policy and Custom Logo URLs to Quickstart
* Feature: added "Link Hellō" button to settings page

= 1.0.1 =

* WordPress plugin submission feedback
* Improvement: updated "Tested Up To" to 6.1.0
* Fix: input/output sanitization and generation
* Improvement: removed unused global functions
* Improvement: enabled user linking and redirect after login

= 1.0.0 =

* Forked https://github.com/oidc-wp/openid-connect-generic
* Feature: merged PR that adds [PKCE support](https://github.com/oidc-wp/openid-connect-generic/pull/421)
* Feature: integrated Hellō Quickstart
* Feature: removed unnecessary configuration options
* Improvement: renamed all relevant identifiers to be Hellō Login specific

--------

[See pre-fork changelog up to 3.9.1 here](https://github.com/oidc-wp/openid-connect-generic/blob/main/CHANGELOG.md)
