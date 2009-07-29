=== Restrict Usernames ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: registration, username, signup, users, restrictions, security, privacy, coffee2code
Requires at least: 2.6
Tested up to: 2.8.2
Stable tag: 1.1
Version: 1.1

Restrict the usernames that new users may use when registering for your site.

== Description ==

Restrict the usernames that new users may use when registering for your site.

If open registration is enabled for your site (via Settings -> General -> Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn't an already existing account and it doesn't include invalid (i.e. non-alphanumeric) characters.

Possible reasons for wanting to restrict certain usernames:

* Prevent usernames that contain foul, offensive, or otherwise undesired words
* Prevent squatting on usernames that you may want to use in the future (but don't want to actually create the account for just yet) (essentially placing a hold on the username)
* Prevent official-sounding usernames from being used (i.e. help, support, pr, info)
* Prevent official username syntax from being used (i.e. if all of your administrators use a prefix to identify themselves, you don't want a visitor to use that prefix)

When attempting to register with a restricted username, the visitor will be given an error notice that says:
ERROR: This username is invalid. Please enter a valid username.

NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.


== Installation ==

1. Unzip `restrict-usernames.zip` inside the `/wp-content/plugins/` directory for your site
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. Go to the Users -> Name Restrictions admin settings page.  Specify username restrictions.


== Frequently Asked Questions ==

= So if I restrict a username from being registered, does that mean that username can't be used? =

No.  The plugin only prevents the usernames visitors can use when registering for an account.  An admin that has user creation privileges can still create a user account, from within the admin, using any otherwise valid username.  Username restrictions don't apply to admins.

= Does this plugin provide any default username restrictions? =

No.


== Screenshots ==

1. A screenshot of the plugin's admin settings page.

== Changelog ==

= 1.1 =
* Added 'Settings' link to plugin's plugin listing entry
* Used plugins_url() instead of hardcoded path
* Noted compatibility with WP2.8+
* Minor code formatting tweaks

= 1.0 =
* Initial release
