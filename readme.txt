=== Restrict Usernames ===
Contributors: coffee2code
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6ARCFJ9TX3522
Tags: registration, username, signup, users, restrictions, security, privacy, coffee2code, multisite, buddypress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.6
Tested up to: 3.8
Stable tag: 3.4

Restrict the usernames that new users may use when registering for your site.


== Description ==

This plugin allows you to restrict the usernames that new users may use when registering for your site.

If open registration is enabled for your site (via Settings -> General -> Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog. By default, any username they choose is allowed so long as it isn't an already existing account and it doesn't include invalid (i.e. non-alphanumeric) characters.

Possible reasons for wanting to restrict certain usernames:

* Prevent usernames that contain foul, offensive, or otherwise undesired words
* Prevent squatting on usernames that you may want to use in the future (but don't want to actually create the account for just yet) (essentially placing a hold on the username)
* Prevent official-sounding usernames from being used (i.e. help, support, pr, info, sales)
* Prevent official username syntax from being used (i.e. if all of your administrators use a prefix to identify themselves, you don't want a visitor to use that prefix)
* Prevent spaces from being used in a username (which WordPress allows by default)
* Require that a username starts with, ends with, or contain one of a set of substrings (i.e. "support_", "admin_")
* Require a minimum number of characters for usernames
* Limit usernames to a maximum number of characters

When attempting to register with a restricted username, the visitor will be given an error notice that says:
ERROR: This username is invalid. Please enter a valid username.

NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin. This only restricts the names that users choose themselves when registering for your site.

Compatible with Multisite and BuddyPress as well.

Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/restrict-usernames/) | [Plugin Directory Page](http://wordpress.org/plugins/restrict-usernames/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Whether installing or updating, whether this plugin or any other, it is always advisable to back-up your data before starting
1. Unzip `restrict-usernames.zip` inside the `/wp-content/plugins/` directory for your site (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress. In Multisite, Network Activate the plugin.
1. Go to the Users -> Name Restrictions admin settings page (which you can also get to via the Settings link next to the plugin on the Manage Plugins page) and specify username restrictions. On a Multisite install, go to My Sites -> Network Admin -> Users -> Name Restrictions.


== Frequently Asked Questions ==

= So if I restrict a username from being registered, does that mean that username can't be used at all? =

No. The plugin only prevents the usernames visitors can use when registering for an account. An admin that has user creation privileges can still create a user account, from within the admin, using any otherwise valid username. Username restrictions don't apply to admins.

= Does this plugin provide any default username restrictions? =

No.

= If I specify restricted usernames and a required username substring, must all criteria be matched by potential usernames?

Yes.

= What if I define username restrictions that some existing user accounts would violate? =

Nothing happens to those accounts. The plugin does not do anything with existing accounts. Existing usernames are not checked against any username restriction rules; that only happens for accounts being newly registered.

= Is this Multisite compatible? =

Yes.

= Is this BuddyPress compatible? =

Yes, for at least BuddyPress 1.2+ and 1.3+, and perhaps other versions.

= Does this plugin include unit tests? =

Yes.


== Screenshots ==

1. A screenshot of the plugin's admin settings page.


== Filters ==

The plugin exposes one filter for hooking. Typically, customizations utilizing this hook would be put into your active theme's functions.php file, or used by another plugin.

= c2c_restrict_usernames-validate (filter) =

The 'c2c_restrict_usernames-validate' hook allows you to add your own customized checks for the username being registered. You can add additional restrictions or override the assessment performed by the plugin.

Arguments:

* $valid (boolean): The assessment by the plugin about the validity of the username based on settings. True means username can be used.
* $username (string): The username being registered.
* $settings (array): The plugin's settings.

Example:

`
/**
 * Add custom checks on usernames.
 *
 * Specifically, prevent use of usernames ending in numbers.
 */
function my_restrict_usernames_check( $valid, $username, $options ) {
	// Only do additional checking if the plugin has already performed its
	// checks and deemed the username valid.
	if ( $valid ) {
		// Don't allow usernames to end in numbers.
		if ( preg_match( '/[0-9]+$/', $username ) )
			$valid = false;
	}
	return $valid;
}
add_filter( 'c2c_restrict_usernames-validate', 'my_restrict_usernames_check', 10, 3 );
`

== Changelog ==

= 3.4 (2014-01-14) =
* Fix to display settings page update messages and username test tool results
* Add setting to optionally enforce minimum length for usernames
* Add setting to optionally enforce maximum length for usernames
* Improve display of username test tool results:
    * Display as list
    * Color-code results as green or red
    * Make 'valid' and 'invalid' translatable
* Add is_buddypress() to determine if BuddyPress's user validation checks are being used
* Move descriptive text from top of settings page into Help panel under new 'About' tab
* Add unit tests
* Update plugin framework to 037
* Better singleton implementation:
    * Add `get_instance()` static method for returning/creating singleton instance
    * Make static variable 'instance' private
    * Make constructor protected
    * Make class final
    * Additional related changes in plugin framework (protected constructor, erroring `__clone()` and `__wakeup()`)
* Add checks to prevent execution of code if file is directly accessed
* Use explicit path for require_once()
* Discontinue use of PHP4-style constructor
* Discontinue use of explicit pass-by-reference for objects
* Minor documentation improvements
* Minor code reformatting (spacing, bracing)
* Note compatibility through WP 3.8+
* Drop compatibility with version of WP older than 3.6
* Add comments explaining use of base64_encode and base64_decode
* Update copyright date (2014)
* Regenerate .pot
* Change donate link
* Update screenshot
* Add banner

= 3.3 =
* Fix bug with partial username restriction
* Add setting page tool for testing multiple usernames for validity
* Add filter c2c_restrict_usernames-validate to allow adding custom restrictions
* Add contextual help 'Advanced' tab to explain new filter
* Add Filters section to readme
* Use square bracket notation for string character index reference
* Regenerate .pot
* Re-license as GPLv2 or later (from X11)
* Add 'License' and 'License URI' header tags to readme.txt and plugin file
* Note compatibility through WP 3.5+
* Update copyright date (2013)
* Remove ending PHP close tag
* Create repo's WP.org assets directory
* Move screenshot into repo's assets directory

= 3.2 =
* Add support for Multisite
* Update plugin framework to 034
* Remove support for 'c2c_restrict_usernames' global
* Note compatibility through WP 3.3+
* Drop compatibility with versions of WP older than 3.1
* Change parent constructor invocation
* Create 'lang' subdirectory and move .pot file into it
* Regenerate .pot
* Add 'Domain Path' directive to top of main plugin file
* Add link to plugin directory page to readme.txt
* Tweak installation instructions in readme.txt
* Update screenshots for WP 3.3
* Update copyright date (2012)

= 3.1 =
* Add support for BuddyPress
* Add bp_members_validate_user_signup()
* Fix to properly register activation and uninstall hooks
* Update plugin framework to 023
* Save a static version of itself in class variable $instance
* Deprecate use of global variable $c2c_restrict_usernames to store instance
* Add __construct(), activation()
* Note deprecation of 'c2c_restrict_usernames' global
* Note compatibility through WP 3.2+
* Drop support for versions of WP older than 3.0
* Add more FAQ questions
* Minor code formatting changes (spacing)
* Fix plugin homepage and author links in description in readme.txt

= 3.0.1 =
* Update plugin framework to version 021
* Explicitly declare all class functions public
* Delete plugin options upon uninstallation
* Note compatibility through WP 3.1+
* Update copyright date (2011)

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

= 3.4 =
Recommended update: minor bug fixes; added ability to enforce username minimum and maximum length; added unit tests; updated plugin framework; compatibility now WP 3.6-3.8+

= 3.3 =
Recommended update. Fixed bug with partial username restrictions; added filter to allow adding custom restrictions; added tool to test username restrictions; noted compatibility through WP 3.5+; and more.

= 3.2 =
Recommended update. Added Multisite compatibility; noted WP 3.3 compatibility; dropped support for versions of WP older than 3.1; updated plugin framework; and more.

= 3.1 =
Recommended update. Added BuddyPress compatibility; noted WP 3.2 compatibility; dropped support for versions of WP older than 3.0; updated plugin framework; and more.

= 3.0 =
Recommended update. Highlights: re-implementation using custom plugin framework; full localization support; misc non-functionality documentation and formatting tweaks; renamed class; verified WP 3.0 compatibility; dropped support for versions of WP older than 2.8.
