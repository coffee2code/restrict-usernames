=== Restrict Usernames ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: registration, username, signup, users, restrictions, security, privacy, coffee2code
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 3.0
Version: 3.0

Restrict the usernames that new users may use when registering for your site.


== Description ==

Restrict the usernames that new users may use when registering for your site.

If open registration is enabled for your site (via Settings -> General -> Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn't an already existing account and it doesn't include invalid (i.e. non-alphanumeric) characters.

Possible reasons for wanting to restrict certain usernames:

* Prevent usernames that contain foul, offensive, or otherwise undesired words
* Prevent squatting on usernames that you may want to use in the future (but don't want to actually create the account for just yet) (essentially placing a hold on the username)
* Prevent official-sounding usernames from being used (i.e. help, support, pr, info)
* Prevent official username syntax from being used (i.e. if all of your administrators use a prefix to identify themselves, you don't want a visitor to use that prefix)
* Prevent spaces from being used in a username (which WordPress allows by default)
* Require that a username begin, end, or contain one of a set of substrings (i.e. "support_", "admin_")

When attempting to register with a restricted username, the visitor will be given an error notice that says:
ERROR: This username is invalid. Please enter a valid username.

NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.


== Installation ==

1. Unzip `restrict-usernames.zip` inside the `/wp-content/plugins/` directory for your site (or install via the built-in WordPress plugin installer)
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

= 3.0 =
* Re-implementation by extending C2C_Plugin_016, which among other things adds support for:
    * Reset of options to default values
    * Better sanitization of input values
    * Offload of core/basic functionality to generic plugin framework
    * Additional hooks for various stages/places of plugin operation
    * Easier localization support
* Full localization support
* Fix to display custom error message rather than the incorrect WordPress default validation error message
* Store plugin instance in global variable, $c2c_restrict_usernames, to allow for external manipulation
* Rename class from 'RestrictUsernames' to 'c2c_RestrictUsernames'
* Remove docs from top of plugin file (all that and more are in readme.txt)
* Note compatibility with WP 2.9+, 3.0+
* Drop compatibility with versions of WP older than 2.8
* Add PHPDoc documentation
* Minor tweaks to code formatting (spacing)
* Add Upgrade Notice section to readme.txt
* Remove trailing whitespace
* Update screenshot
* Update .pot file

= 2.0.1 =
* Fix to add accidentally omitted get_option_names()

= 2.0 =
* Add option to disallow use of spaces in usernames
* Add option to allow requirement of any of numerous substrings as part of usernames (including start with and end with requirements)
* Add reset button to reset options
* Move initialization of config array out of constructor and into new function load_config()
* Create init() to handle calling load_textdomain() and load_config() (textdomain must be loaded before initializing config)
* Return immediately if username being check is already invalid
* Use strpos() instead of stristr()
* Changed invocation of plugin's install function to action hooked in constructor rather than in global space
* Extract settings saving code into maybe_save_options()
* Extract settings display code into display_option()
* Update object's option buffer after saving changed submitted by user
* Add support for localization
* Add .pot file
* Add PHPDoc documentation
* Note compatibility with WP 2.9+
* Drop compatibility with versions of WP older than 2.8
* Update documentation
* Update copyright date

= 1.1 =
* Added 'Settings' link to plugin's plugin listing entry
* Used plugins_url() instead of hardcoded path
* Noted compatibility with WP2.8+
* Minor code formatting tweaks

= 1.0 =
* Initial release


== Upgrade Notice ==

= 3.0 =
Recommended update. Highlights: re-implementation using custom plugin framework; full localization support; misc non-functionality documentation and formatting tweaks; renamed class; verified WP 3.0 compatibility; dropped support for versions of WP older than 2.8.