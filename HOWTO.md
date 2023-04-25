# Hellō Login

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Free and simple to setup plugin provides registration and login with the Hellō Wallet. Users choose from popular social
login, email, or phone.

## Description

This plugin allows to authenticate users against OpenID Connect OAuth2 API with Authorization Code Flow.
Once installed, it can be configured to automatically authenticate users (SSO), or provide a "Login with OpenID Connect"
button on the login form. After consent has been obtained, an existing user is automatically logged into WordPress, while 
new users are created in WordPress database.

Much of the documentation can be found on the Settings > Hellō Login dashboard page.

## Table of Contents

- [Installation](#installation)
    - [Composer](#composer)
- [Hooks](#hooks)
    - [Filters](#filters)
        - [hello-login-user-login-test](#hello-login-user-login-test)
        - [hello-login-user-creation-test](#hello-login-user-creation-test)
        - [hello-login-alter-user-data](#hello-login-alter-user-data)
    - [Actions](#actions)
        - [hello-login-user-create](#hello-login-user-create)
        - [hello-login-user-update](#hello-login-user-update)
        - [hello-login-update-user-using-current-claim](#hello-login-update-user-using-current-claim)
        - [hello-login-redirect-user-back](#hello-login-redirect-user-back)
        - [hello-login-user-logged-in](#hello-login-user-logged-in)


## Installation

See "Installation" section in README.md.

### Composer

[Hellō Login on packagist](https://packagist.org/packages/hellocoop/wordpress)

Installation:

`composer require hellocoop/wordpress`

## Hooks

This plugin provides a number of hooks to allow for a significant amount of customization of the plugin operations from 
elsewhere in the WordPress system.

### Filters

Filters are WordPress hooks that are used to modify data. The first argument in a filter hook is always expected to be
returned at the end of the hook.

WordPress filters API - [`add_filter()`](https://developer.wordpress.org/reference/functions/add_filter/) and 
[`apply_filters()`](https://developer.wordpress.org/reference/functions/apply_filters/).

Most often you'll only need to use `add_filter()` to hook into this plugin's code.

#### `hello-login-user-login-test`

Determine whether or not the user should be logged into WordPress.

Provides 2 arguments: the boolean result of the test (default `TRUE`), and the `$user_claim` array from the server.

```php
add_filter('hello-login-user-login-test', function( $result, $user_claim ) {
    // Don't let Terry login.
    if ( $user_claim['email'] == 'terry@example.com' ) {
        $result = FALSE;
    }
    
    return $result;
}, 10, 2);
```

#### `hello-login-user-creation-test`

Determine whether or not the user should be created. This filter is called when a new user is trying to login and they
do not currently exist within WordPress.

Provides 2 arguments: the boolean result of the test (default `TRUE`), and the `$user_claim` array from the server.

```php
add_filter('hello-login-user-creation-test', function( $result, $user_claim ) {
    // Don't let anyone from example.com create an account.
    $email_array = explode( '@', $user_claim['email'] );
    if ( $email_array[1] == 'example.com' ) {
        $result = FALSE;
    }
    
    return $result;
}, 10, 2) 
```

#### `hello-login-alter-user-data`

Modify a new user's data immediately before the user is created.

Provides 2 arguments: the `$user_data` array that will be sent to `wp_insert_user()`, and the `$user_claim` from the 
server.

```php
add_filter('hello-login-alter-user-data', function( $user_data, $user_claim ) {
    // Don't register any user with their real email address. Create a fake internal address.
    if ( !empty( $user_data['user_email'] ) ) {
        $email_array = explode( '@', $user_data['user_email'] );
        $email_array[1] = 'my-fake-domain.co';
        $user_data['user_email'] = implode( '@', $email_array );
    }
    
    return $user_data;
}, 10, 2);
```

### Actions

WordPress actions are generic events that other plugins can react to.

Actions API: [`add_action`](https://developer.wordpress.org/reference/functions/add_action/) and [`do_actions`](https://developer.wordpress.org/reference/functions/do_action/)

You'll probably only ever want to use `add_action` when hooking into this plugin.

#### `hello-login-user-create`

React to a new user being created by this plugin.

Provides 2 arguments: the `\WP_User` object that was created, and the `$user_claim` from the IDP server.

```php
add_action('hello-login-user-create', function( $user, $user_claim ) {
    // Send the user an email when their account is first created.
    wp_mail( 
        $user->user_email,
        __('Welcome to my web zone'),
        "Hi {$user->first_name},\n\nYour account has been created at my cool website.\n\n Enjoy!"
    ); 
}, 10, 2);
``` 

#### `hello-login-user-update`

React to the user being updated after login. This is the event that happens when a user logins and they already exist as 
a user in WordPress, as opposed to a new WordPress user being created.

Provides 1 argument: the user's WordPress user ID.

```php
add_action('hello-login-user-update', function( $uid ) {
    // Keep track of the number of times the user has logged into the site.
    $login_count = get_user_meta( $uid, 'my-user-login-count', TRUE);
    $login_count += 1;
    add_user_meta( $uid, 'my-user-login-count', $login_count, TRUE);
});
```

#### `hello-login-update-user-using-current-claim`

React to an existing user logging in (after authentication and authorization).

Provides 2 arguments: the `WP_User` object, and the `$user_claim` provided by the IDP server.

```php
add_action('hello-login-update-user-using-current-claim', function( $user, $user_claim) {
    // Based on some data in the user_claim, modify the user.
    if ( !empty( $user_claim['wp_user_role'] ) ) {
        if ( $user_claim['wp_user_role'] == 'should-be-editor' ) {
            $user->set_role( 'editor' );
        }
    }
}, 10, 2); 
```

#### `hello-login-redirect-user-back`

React to a user being redirected after a successful login. This hook is the last hook that will fire when a user logs 
in. It will only fire if the plugin setting "Redirect Back to Origin Page" is enabled at Dashboard > Settings >
Hellō Login. It will fire for both new and existing users.

Provides 2 arguments: the url where the user will be redirected, and the `WP_User` object.

```php
add_action('hello-login-redirect-user-back', function( $redirect_url, $user ) {
    // Take over the redirection complete. Send users somewhere special based on their capabilities.
    if ( $user->has_cap( 'edit_users' ) ) {
        wp_redirect( admin_url( 'users.php' ) );
        exit();
    }
}, 10, 2); 
```

#### `hello-login-user-logged-in`

React to a user being logged in.

Provides 1 argument: the `WP_User` object.

```php
add_action('hello-login-user-logged-in', function( $user ) {
    // Keep track of the number of times the user has logged into the site.
    $login_count = get_user_meta( $user->ID, 'my-user-login-count', TRUE);
    $login_count += 1;
    add_user_meta( $user->ID, 'my-user-login-count', $login_count, TRUE);
});
```

### User Metadata

This plugin stores metadata about the user for both practical and debugging purposes.

* `hello-login-subject-identity` - The identity of the user provided by the IDP server.
* `hello-login-last-user-claim` - The user's most recent `user_claim`, stored as an array.
