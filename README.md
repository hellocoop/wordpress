# Hellō Login#
**Contributors:** [mariuss](https://profiles.wordpress.org/mariuss/)  
**Donate link:** https://www.hello.dev/
**Tags:** security, login, oauth2, openidconnect, apps, authentication, sso  
**Requires at least:** 4.9  
**Tested up to:** 6.0.1  
**Stable tag:** 1.0.0  
**Requires PHP:** 7.2  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

A login and registration plugin for the Hellō service.

## Description ##

This plugin integrates the Hellō service with your site, simplifying login and registration of users.
You can request name, nickname, profile picture, as well as a verified email, phone, or ethereum address.

This plugin uses the Hellō Quickstart service to get your site up and running in minutes.

Configuration and settings can be found in the Settings > Hellō Login dashboard page

For details on the Hellō service see [hello.dev](https://hello.dev)

Please submit issues and feature requests to the Github repo: [https://github.com/hellocoop/wordpress](https://github.com/hellocoop/wordpress)

## Installation ##

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin
1. Visit Settings > Hellō Login
1. Click Hellō Quickstart button and complete Quickstart flow
1. Enable user registration and login
	1. Visit Settings > General
	1. Scroll to "Membership" and check "Anyone can register"
	1. Make sure that "New User Default Role" below has the correct value for your case, most likely "Subscriber"
	1. Add Registration and Login links. Depending on what Theme is being used you can add login related widgets, custom
	   forms or the simplest is to add a short code. The supported short codes are listed at the bottom of the Hellō
	   settings page.

## Frequently Asked Questions ##

### How do users login? ###

Hellō offers users all popular social login methods including Apple, Facebook, Google, Line, Microsoft, Twitch, and Yahoo;
email or phone; or popular crypto wallets including MetaMask. The current choices can be seen at [https://wallet.hello.coop](https://wallet.hello.coop)
Hellō lets users change their provider without any effort on your part.

### What claims can I ask for about a user? ###

Hellō supports all popular OpenID Connect claims and we are continually adding claims to Hellō. You can see the full list at [Hellō Claims](https://www.hello.dev/documentation/hello-claims.html)

## Changelog ##

### 1.0.0 ###

* Forked https://github.com/oidc-wp/openid-connect-generic
* Improvement: @mariuss - Merged PR that adds [PKCE support](https://github.com/oidc-wp/openid-connect-generic/pull/421).
* Feature: @mariuss - Integrated Hellō Quickstart
* Feature: @mariuss - Removed unnecessary configuration options


--------

[See pre-fork changelogs here](https://github.com/oidc-wp/openid-connect-generic/blob/main/CHANGELOG.md)
