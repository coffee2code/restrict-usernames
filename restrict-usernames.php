<?php
/**
 * Plugin Name: Restrict Usernames
 * Version:     3.6
 * Plugin URI:  http://coffee2code.com/wp-plugins/restrict-usernames/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * Text Domain: restrict-usernames
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Description: Restrict the usernames that new users may use when registering for your site.
 *
 * Compatible with WordPress 4.1 through 4.5+ and BuddyPress through 1.9 through 2.6+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/restrict-usernames/
 *
 * @package Restrict_Usernames
 * @author  Scott Reilly
 * @version 3.6
 */

/*
 * TODO:
 * - Add screenshot of username test tool and its results
 * - In test tool, provide reason for each invalid username
 * - Add option to have specific error message reported to user?
 * - More viable: add optional textarea to define a message to display to
 *   registrants. That way the admin can explain their registration if they so
 *   choose.
 * - Further investigation into the problem of membership plugins having custom
 *   user registration code that ultimately calls wp_create_user() or
 *   wp_insert_user() directly, often without a call to validate_username()
 *   (which would be called if they invoked register_new_user() instead).
 *   Ideally the plugin's handler could be located in wp_insert_user(), but as
 *   of WP 4.5 there is no adequate way to make it work (there are no related
 *   hooks, and all existing hooks don't know context of whether it is being
 *   fired for a new user or an update to a user. Maybe it's possibly to make
 *   such direct calls work with something hacky in wp_insert_user.)
 */

/*
	Copyright (c) 2008-2016 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_RestrictUsernames' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-plugin.php' );

final class c2c_RestrictUsernames extends c2c_RestrictUsernames_Plugin_042 {

	/**
	 * The one true instance.
	 *
	 * @var c2c_RestrictUsernames
	 */
	private static $instance;

	/**
	 * @var bool
	 */
	private $got_restricted = false;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.4
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct( '3.6', 'restrict-usernames', 'c2c', __FILE__, array( 'settings_page' => 'users' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 */
	public static function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 */
	public static function uninstall() {
		delete_option( 'c2c_restrict_usernames' );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 */
	public function load_config() {
		$this->name      = __( 'Restrict Usernames', 'restrict-usernames' );
		$this->menu_name = __( 'Name Restrictions', 'restrict-usernames' );

		$input_style = 'style="width:50%;" rows="6"';

		$this->config = array(
			'disallow_spaces' => array(
				'input'   => 'checkbox',
				'default' => false,
				'label'   => __( 'Don\'t allow spaces in usernames.', 'restrict-usernames' ),
				'help'    => __( 'WordPress (single-site, not multisite) allows spaces in usernames. Check this if you don\'t want to allow spaces.', 'restrict-usernames' ),
			),
			'min_length' => array(
				'input'   => 'short_text',
				'default' => '',
				'label'   => __( 'Minimum length', 'restrict-usernames' ),
				'help'    => __( 'Enter the mimimum number of characters that can be used in a username. Leave blank or 0 to have no limit.', 'restrict-usernames' ) .
							( $this->is_buddypress() ? __( 'Under BuddyPress, the minimum length is 4, which cannot be made any lower with this plugin.', 'restrict-usernames' ) : '' ),
			),
			'max_length' => array(
				'input'   => 'short_text',
				'default' => '',
				'label'   => __( 'Maximum length', 'restrict-usernames' ),
				'help'    => __( 'Enter the maximum number of characters that can be used in a username. Leave blank or 0 to have no limit. Note that WordPress already enforces a max length of 60, which cannot be made any higher with this plugin.', 'restrict-usernames' ),
			),
			'usernames' => array(
				'input'    => 'inline_textarea',
				'datatype' => 'array',
				'default'  => '',
				'input_attributes' => $input_style,
				'label'    => __( 'Restricted usernames', 'restrict-usernames' ),
				'help'     => __( 'List the usernames that newly-registering users cannot use. Define one per line and use all lowercase.', 'restrict-usernames' ),
			),
			'partial_usernames' => array(
				'input'    => 'inline_textarea',
				'datatype' => 'array',
				'default'  => '',
				'input_attributes' => $input_style,
				'label'   => __( 'Restricted usernames (partial matching)', 'restrict-usernames' ),
				'help'    => __( 'These are partial text values that cannot appear in usernames requested by newly-registering users. Useful to prevent usage of bad language or prevent users from using a notation used to identify admins of the site, i.e. "admin_". Be aware that anything listed here will then not be allowed as any part of a username. Define one per line and use all lowercase.', 'restrict-usernames' ) .
					( is_multisite() ? __( '<strong>NOTE: Multisite only allows numbers and lowercase letters in usernames.</strong>', 'restrict-usernames' ) : '' ),
			),
			'required_partials' => array(
				'input'    => 'inline_textarea',
				'datatype' => 'array',
				'default'  => '',
				'input_attributes' => $input_style,
				'label'    => __( 'Required username substring', 'restrict-usernames' ),
				'help'     => __( 'These are partial text values, one of which MUST appear in any username requested by newly-registering users. Useful to force users to include some sort of identifier in their username, like "support_" (leading to "support_john") or "admin_" ("admin_steve"), etc. A username needs to only include ONE of the listed partials. Prepend a partial with "^" (i.e. "^support_" to require that partial as the start of a username) or end with "^" to require that partial be at the end (i.e. "_support^"). Without use of "^", the partial can appear in any position in the username. Be aware that this plugin does not convey to the user what these requirements are, it only enforces the requirement. Define one per line and use all lowercase.', 'restrict-usernames' ) .
					( is_multisite() ? __( '<strong>NOTE: Multisite only allows numbers and lowercase letters in usernames.</strong>', 'restrict-usernames' ) : '' ),
			),
		);
	}

	/**
	 * Override the plugin framework's register_filters() to register actions and filters.
	 */
	public function register_filters() {
//		add_filter( 'login_message', array( $this, 'login_message' ) );
		if ( is_multisite() ) {
			add_action( 'network_admin_menu',     array( $this, 'admin_menu' ) );
			remove_action( 'admin_menu',          array( $this, 'admin_menu' ) );
		}

		if ( is_admin() ) {
			add_action( 'all_admin_notices',                       array( $this, 'show_settings_message' ), 20 );
			add_action( 'admin_init',                              array( $this, 'maybe_test_usernames' ) );
			add_action( $this->get_hook( 'after_settings_form' ),  array( $this, 'usernames_test_form' ) );
		} else {
			add_filter( 'illegal_user_logins', array( $this, 'illegal_user_logins' ) );

			if ( is_multisite() ) {
				// WordPress Multisite
				add_filter( 'wpmu_validate_user_signup',    array( $this, 'bp_members_validate_user_signup' ) );
			} elseif ( defined( 'BP_VERSION' ) ) {
				// BuddyPress
				add_filter( 'bp_core_validate_user_signup', array( $this, 'bp_members_validate_user_signup' ) );
			} else {
				// WordPress
				add_filter( 'validate_username',            array( $this, 'username_restrictor' ), 10, 2 );
			}

			add_filter( 'registration_errors', array( $this, 'registration_errors' ) );
		}
	}

	/**
	 * Shows the settings errors/messages.
	 *
	 * @since 3.4
	 */
	public function show_settings_message() {
		if ( $this->is_plugin_admin_page() ) {
			settings_errors();
		}
	}

	/**
	 * Outputs the text above the setting form.
	 *
	 * @param  string $localized_heading_text Optional. Localized page heading text.
	 */
	public function options_page_description( $localized_heading_text = '' ) {
		parent::options_page_description( __( 'Restrict Usernames Settings', 'restrict-usernames' ) );
		echo '<p>' . __( 'NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin. This only restricts the names that users choose when registering themselves for the site.', 'restrict-usernames' ) . '</p>';
		echo '<p>' . __( 'Use the <a href="#username_check">namecheck tool</a> found below to test how the plugin would evaluate sample usernames.' ) . '</p>';
	}

	/**
	 * Configures help tabs content.
	 *
	 * @since 3.3
	 */
	public function help_tabs_content( $screen ) {
		$screen->add_help_tab( array(
			'id'      => $this->id_base . '-' . 'about',
			'title'   => __( 'About', 'restrict-usernames' ),
			'content' =>
				'<p>' . __( 'If open registration is enabled for your site (via Settings &rarr; General &rarr; Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog. By default, any username they choose is allowed so long as it isn\'t an already existing account and it doesn\'t include invalid (i.e. non-alphanumeric) characters. This plugin allows you to add further restrictions.', 'restrict-usernames' ) . '</p>' .
				'<p>' . __( 'Possible reasons for wanting to restrict certain usernames:', 'restrict-usernames' ) . '</p>' .
				'<ul class="c2c-plugin-list">' .
				'<li>' . __( 'Prevent usernames that contain foul, offensive, or otherwise undesired words', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Prevent squatting on usernames that you may want to use in the future (but don\'t want to actually create the account for just yet) (essentially placing a hold on the username)', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Prevent official-sounding usernames from being used (i.e. help, support, pr, info)', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Prevent official username syntax from being used (i.e. if all of your admins use a prefix to identify themselves, you don\'t want a visitor to use that prefix)', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Prevent spaces from being used in a username (which WordPress allows by default)', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Require that a username starts with, ends with, or contain one of a set of substrings (i.e. "support_", "admin_")', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Require a minimum number of characters for usernames', 'restrict-usernames' ) . '</li>' .
				'<li>' . __( 'Limit usernames to a maximum number of characters', 'restrict-usernames' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'When attempting to register with a restricted username, the visitor will be given an error notice that says:', 'restrict-usernames' ) . '<br /><blockquote>' .
				( is_multisite() ?
					__( 'Sorry, this username is invalid. Please choose another.', 'restrict-usernames' ) :
					__( 'ERROR: This username is invalid. Please choose another.', 'restrict-usernames' )
				) .
				'</blockquote></p>'
		) );

		$screen->add_help_tab( array(
			'id'      => $this->id_base . '-' . 'advanced',
			'title'   => __( 'Advanced', 'restrict-usernames' ),
			'content' => <<<HTML
			<h4>Filter</h4>
			<p>This plugin provides a filter that can be used to do your own customized username restriction checks. Here's an example:</p>
			<p><pre>/**
  * Add custom checks on usernames.
  *
  * Specifically, prevent use of usernames ending in numbers.
  */
	function my_restrict_usernames_check( \$valid, \$username, \$options ) {
		// Only do additional checking if the plugin has already performed its
		// checks and deemed the username valid.
		if ( \$valid ) {
			// Don't allow usernames to end in numbers.
			if ( preg_match( '/[0-9]+$/', \$username ) )
				\$valid = false;
		}
		return \$valid;
	}
 add_filter( 'c2c_restrict_usernames-validate', 'my_restrict_usernames_check', 10, 3 );<pre></p>
HTML
		) );
		parent::help_tabs_content( $screen );
	}

	/**
	 * Determines if the plugin is running under BuddyPress and its username
	 * checks are being performed.
	 *
	 * @since 3.5
	 *
	 * @return bool
	 */
	protected function is_buddypress() {
		return ( defined( 'BP_VERSION' ) && ! function_exists( 'wpmu_validate_user_signup' ) );
	}

	/**
	 * Outputs message to login page to tell visitor valid user syntax for usernames.
	 *
	 * @param  string $message Pending login message.
	 * @return string The incoming login message appended with text regarding valid user syntax.
	 */
/*	public function login_message( $message ) {
		$options = $this->get_options();
		if ( $options['required_partials'] ) {
			$starts = array();
			$ends = array();
			$contains = array();
			foreach ( $options['required_partials'] as $partial ) {
				if ( $partial[0] == '^' )
					$starts[] = substr( $partial, 1 );
				elseif ( substr( $partial, -1, 1 ) == '^' )
					$ends[] = substr( $partial, 0, -1 );
				else
					$contains[] = $partial;
			}
			$msg = __( 'Usernames must', 'restrict-usernames' ) . ' <br />';
			if ( $starts )
				$msg .= sprintf( __( 'start with: %s', 'restrict-usernames' ), implode( ', ', $starts ) );
			if ( $starts && ( $contains || $ends ) )
				$msg .= '<br />' . __( 'and/or', 'restrict-usernames' ) . ' ';
			if ( $contains )
				$msg .= sprintf( __( 'contain: %s', 'restrict-usernames' ), implode( ', ', $contains ) );
			if ( $contains && $ends )
				$msg .= '<br />' . __( 'and/or', 'restrict-usernames' ) . ' ';
			if ( $ends )
				$msg .= sprintf( __( 'end with: %s', 'restrict-usernames' ), implode( ', ', $ends ) );
			$message .= '<p class="message">' . $msg . "</p>\n";
		}
		return $message;
	}
*/

	/**
	 * Assesses if a username is restricted from use.
	 *
	 * @param  bool   $valid    A boolean indicating WordPress's built-in assessment of the validity of the username.
	 * @param  string $username The username to check for possible restriction.
	 * @return bool   Boolean indicating if the username is restricted. False means username is restricted (and hence not valid).
	 */
	public function username_restrictor( $valid, $username ) {
		// If existing username checks have already rejected the username, there is no need to check further.
		// Or if the check is on behalf of a logged in user who can create users (and isn't running the
		// username tester) skip all of this plugin's checks and return current assessment.
		if ( ! $valid || ( ! isset( $_POST['c2c_test_usernames'] ) && is_user_logged_in() && current_user_can( 'create_users' ) ) ) {
			return $valid;
		}

		$options  = $this->get_options();
		$username = strtolower( $username );

		if ( $valid && $options['disallow_spaces'] && strpos( $username, ' ' ) !== false ) {
			$valid = false;
		}

		if ( $valid && $options['usernames'] && in_array( $username, $options['usernames'] ) ) {
			$valid = false;
		}

		if ( $valid && ! empty( $options['min_length'] ) && strlen( $username ) < intval( $options['min_length'] ) ) {
			$valid = false;
		}

		if ( $valid && ! empty( $options['max_length'] ) && strlen( $username ) > intval( $options['max_length'] ) ) {
			$valid = false;
		}

		if ( $valid && $options['partial_usernames'] ) {
			foreach ( $options['partial_usernames'] as $partial ) {
				if ( strpos( $username, $partial ) !== false ) {
					$valid = false;
					break;
				}
			}
		}

		if ( $valid && $options['required_partials'] ) {
			$valid = false;
			foreach ( $options['required_partials'] as $partial ) {
				if ( $partial[0] == '^' ) {
					$partial = substr( $partial, 1 );
					if ( ( $username != $partial ) && ( strpos( $username, $partial ) === 0 ) ) {
						$valid = true;
						break;
					}
				}
				elseif ( substr( $partial, -1, 1 ) == '^' ) {
					$partial = substr( $partial, 0, -1 );
					if ( ( $username != $partial ) && ( substr( $username, -strlen( $partial ) ) == $partial ) ) {
						$valid = true;
						break;
					}
				}
				elseif ( strpos( $username, $partial ) !== false ) {
					$valid = true;
					break;
				}
			}
		}
		$valid = apply_filters( 'c2c_restrict_usernames-validate', $valid, $username, $options );

		$this->got_restricted = !$valid;
		return $valid;
	}

	/**
	 * Returns a list of explicit illegal user logins.
	 *
	 * @since 3.6
	 *
	 * @param array $illegal_user_logins List of illegal user logins.
	 * @return array
	 */
	public function illegal_user_logins( $illegal_user_logins ) {
		$options = $this->get_options();

		if ( $options['usernames'] ) {
			$illegal_user_logins = array_merge( (array) $illegal_user_logins, (array) $options['usernames'] );
		}

		return $illegal_user_logins;
	}

	/**
	 * Register the invalid username error if it had been detected.
	 *
	 * @param  WP_Error $errors Errors.
	 * @return WP_Error
	 */
	public function registration_errors( $errors ) {
		if ( $this->got_restricted ) {
			if ( method_exists( $errors, 'remove' ) ) {
				$errors->remove( 'invalid_username' );
			} else { // Pre-WP4.1 compatibility
				unset( $errors->errors['invalid_username'] );
				unset( $errors->error_data['invalid_username'] );
			}

			$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid. Please choose another.', 'restrict-usernames' ), 'invalid_username' );
		}

		return $errors;
	}

	/**
	 * Check username for restrictions under BuddyPress.
	 *
	 * The username restriction check under BuddyPress is different than under
	 * WP proper because BP employs its own user validation checks. While it
	 * does pass registering usernames to validate_username() -- which this
	 * plugin normally hooks -- any failures reported trigger BP's generic
	 * 'Only lowercase letters and numbers allowed' error message. It's just
	 * easier to let BP do its checks and at the very end do the username
	 * restriction checks.
	 *
	 * @since 3.1
	 *
	 * @param  array $result BP signup validation result array consisting of 'user_name', 'user_email', and 'errors' elements.
	 * @return array The possibly modified results array.
	 */
	public function bp_members_validate_user_signup( $result ) {
		// Only check username for restrictions if the username hasn't already generated some other error.
		$errs = $result['errors']->get_error_messages( 'user_name' );
		if ( $errs ) {
			$valid = $this->username_restrictor( true, $result['user_name'] );
			if ( ! $valid ) {
				$errors = $result['errors'];
				$errors->add( 'user_name', __( 'Sorry, this username is invalid. Please choose another.', 'restrict-usernames' ) );
				$result['errors'] = $errors;
			}
		}

		return $result;
	}

	/**
	 * Evaluates submitted usernames against the restrictions imposed by just
	 * this plugin.
	 *
	 * @since 3.3
	 */
	public function maybe_test_usernames() {
		if ( isset( $_POST[ $this->get_form_submit_name( 'submit_test_usernames' ) ] ) && isset( $_POST['c2c_test_usernames'] ) ) {
			check_admin_referer( $this->nonce_field );

			$msg = __( 'Results of username checks:', 'restrict-usernames' );
			$msg .= '<ul style="padding-left: 20px; list-style: inherit;">';
			$unames = explode( ',', $_POST['c2c_test_usernames'] );

			// The filter isn't normally added in the admin, so do so.
			add_filter( 'validate_username', array( $this, 'username_restrictor' ), 10, 2 );
			$do_msg = false;

			foreach ( $unames as $u ) {
				$u = trim( $u );
				if ( $u === '' ) {
					continue;
				}
				$msg .= '<li style="margin-bottom: 0;">' . esc_html( $u ) . ' : ';
				$msg .= validate_username( $u ) ?
					sprintf( '<span style="color: green;">%s</span>', __( 'valid', 'restrict-usernames' ) ) :
					sprintf( '<span style="color: red;">%s</span>', __( 'invalid', 'restrict-usernames' ) );
				$msg .= '</li>';
				$do_msg = true;
			}
			$msg .= '</ul>';
			$msg .= '<p>' . __( 'Bear in mind that this only checks the syntax of the username against the rules and restrictions controlled below. It does not test additional criteria (such as
				if the username already exists) that WordPress also checks for on registration.' ) . '</p>';

			if ( $do_msg ) {
				add_settings_error( $this->admin_options_name, 'usernames_tested', $msg, 'updated' );
			}
		}
	}

	/**
	 * Outputs form to test usernames.
	 *
	 * @since 3.3
	 */
	public function usernames_test_form() {
		$user       = wp_get_current_user();
		$email      = $user->user_email;
		$action_url = $this->form_action_url();

		echo '<div class="wrap"><h2><a name="username_check"></a>' . __( 'Test Usernames', 'restrict-usernames' ) . "</h2>\n";
		echo '<p>' . __( 'Use the input field below to list usernames you\'d like to test against the plugin\'s restrictions. Separate multiple usernames with commas.', 'restrict-usernames' ) . "</p>\n";
		echo '<p><em>You must save any changes to the form above before attempting to test usernames.</em></p>';
		echo "<form name='c2c_restrict_usernames' action='$action_url' method='post'>\n";
		wp_nonce_field( $this->nonce_field );
		echo '<input type="hidden" name="' . $this->get_form_submit_name( 'submit_test_usernames' ) .'" value="1" />';
		echo '<input type="text" size="80" name="c2c_test_usernames" />';
		echo '<div class="submit"><input type="submit" name="Submit" class="button-primary" value="' . esc_attr__( 'Test', 'restrict-usernames' ) . '" /></div>';
		echo '</form></div>';
	}

} // end c2c_RestrictUsernames

c2c_RestrictUsernames::get_instance();

endif; // end if !class_exists()
