<?php
/**
 * @package Restrict_Usernames
 * @author Scott Reilly
 * @version 3.3
 */
/*
Plugin Name: Restrict Usernames
Version: 3.3
Plugin URI: http://coffee2code.com/wp-plugins/restrict-usernames/
Author: Scott Reilly
Author URI: http://coffee2code.com/
Text Domain: restrict-usernames
Domain Path: /lang/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: Restrict the usernames that new users may use when registering for your site.

Compatible with WordPress 3.1 through 3.5+ and BuddyPress 1.2+, 1.3+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/restrict-usernames/
*/

/*
	Copyright (c) 2008-2013 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! class_exists( 'c2c_RestrictUsernames' ) ) :

require_once( 'c2c-plugin.php' );

class c2c_RestrictUsernames extends C2C_Plugin_034 {

	public static $instance;
	private $got_restricted = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->c2c_RestrictUsernames();
	}

	public function c2c_RestrictUsernames() {
		// Be a singleton
		if ( ! is_null( self::$instance ) )
			return;

		parent::__construct( '3.3', 'restrict-usernames', 'c2c', __FILE__, array( 'settings_page' => 'users' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @return void
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * @return void
	 */
	public function uninstall() {
		delete_option( 'c2c_restrict_usernames' );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	public function load_config() {
		$this->name      = __( 'Restrict Usernames', $this->textdomain );
		$this->menu_name = __( 'Name Restrictions', $this->textdomain );

		$input_style = 'style="width:50%;" rows="6"';

		$this->config = array(
			'disallow_spaces' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Don\'t allow spaces in usernames.', $this->textdomain ),
					'help' => __( 'WordPress (single-site, not multisite) allows spaces in usernames.  Check this if you don\'t want to allow spaces.', $this->textdomain ) ),
			'usernames' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Restricted usernames', $this->textdomain ),
					'help' => __( 'List the usernames that newly-registering users cannot use.  Define one per line and use all lowercase.', $this->textdomain ) ),
			'partial_usernames' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Restricted usernames (partial matching)', $this->textdomain ),
					'help' => __( 'These are partial text values that cannot appear in usernames requested by newly-registering users.  Useful to prevent usage of bad language or prevent users from using a notation used to identify admins of the site, i.e. "admin_".  Be aware that anything listed here will then not be allowed as any part of a username.  Define one per line and use all lowercase.', $this->textdomain ) .
					( is_multisite() ? __( '<strong>NOTE: Multisite only allows numbers and lowercase letters in usernames.</strong>', $this->textdomain ) : '' ) ),
			'required_partials' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Required username substring', $this->textdomain ),
					'help' => __( 'These are partial text values, one of which MUST appear in any username requested by newly-registering users.  Useful to force users to include some sort of identifier in their username, like "support_" (leading to "support_john") or "admin_" ("admin_steve"), etc.  A username needs to only include ONE of the listed partials.  Prepend a partial with "^" (i.e. "^support_" to require that partial as the start of a username) or end with "^" to require that partial be at the end (i.e. "_support^").  Without use of "^", the partial can appear in any position in the username.  Be aware that this plugin does not convey to the user what these requirements are, it only enforces the requirement.  Define one per line and use all lowercase.', $this->textdomain ) .
					( is_multisite() ? __( '<strong>NOTE: Multisite only allows numbers and lowercase letters in usernames.</strong>', $this->textdomain ) : '' ) )
		);
	}

	/**
	 * Override the plugin framework's register_filters() to register actions and filters.
	 *
	 * @return void
	 */
	public function register_filters() {
//		add_filter( 'login_message', array( &$this, 'login_message' ) );
		if ( is_multisite() ) {
			add_action( 'network_admin_menu',     array( &$this, 'admin_menu' ) );
			remove_action( 'admin_menu',          array( &$this, 'admin_menu' ) );
		}

		if ( is_admin() ) {
			add_action( 'admin_init',                              array( &$this, 'maybe_test_usernames' ) );
			add_action( $this->get_hook( 'after_settings_form' ),  array( &$this, 'usernames_test_form' ) );
		} else {
			if ( is_multisite() || defined( 'BP_VERSION' ) )
				add_filter( 'wpmu_validate_user_signup', array( &$this, 'bp_members_validate_user_signup' ) );
			else
				add_filter( 'validate_username',         array( &$this, 'username_restrictor' ), 10, 2 );
			add_filter( 'registration_errors',           array( &$this, 'registration_errors' ) );
		}
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description() {
		parent::options_page_description( __( 'Restrict Usernames Settings', $this->textdomain ) );
		echo '<p>' . __( 'If open registration is enabled for your site (via Settings &rarr; General &rarr; Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn\'t an already existing account and it doesn\'t include invalid (i.e. non-alphanumeric) characters. This plugin allows you to add further restrictions.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Possible reasons for wanting to restrict certain usernames:', $this->textdomain ) . '</p>';
		echo '<ul class="c2c-plugin-list">';
		echo '<li>' . __( 'Prevent usernames that contain foul, offensive, or otherwise undesired words', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent squatting on usernames that you may want to use in the future (but don\'t want to actually create the account for just yet) (essentially placing a hold on the username)', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent official-sounding usernames from being used (i.e. help, support, pr, info)', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent official username syntax from being used (i.e. if all of your admins use a prefix to identify themselves, you don\'t want a visitor to use that prefix)', $this->textdomain ) . '</li>';
		echo '</ul>';
		echo '<p>' . __( 'When attempting to register with a restricted username, the visitor will be given an error notice that says:', $this->textdomain ) . '<br /><blockquote>';
		if ( is_multisite() )
			echo __( 'Sorry, this username is invalid. Please choose another.', $this->textdomain );
		else
			echo __( 'ERROR: This username is invalid. Please choose another.', $this->textdomain );
		echo '</blockquote></p>';
		echo '<p>' . __( 'NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Use the <a href="#username_check">namecheck tool</a> found below to test how the plugin would evaluate sample usernames.' ) . '</p>';
	}

	/**
	 * Configures help tabs content.
	 *
	 * @since 3.3
	 *
	 * @return void
	 */
	public function help_tabs_content( $screen ) {
		$screen->add_help_tab( array(
			'id'      => $this->id_base . '-' . 'advanced',
			'title'   => __( 'Advanced', $this->textdomain ),
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
	 * Outputs message to login page to tell visitor valid user syntax for usernames.
	 *
	 * @param string $message Pending login message.
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
			$msg = __( 'Usernames must', $this->textdomain ) . ' <br />';
			if ( $starts )
				$msg .= sprintf( __( 'start with: %s', $this->textdomain ), implode( ', ', $starts ) );
			if ( $starts && ( $contains || $ends ) )
				$msg .= '<br />' . __( 'and/or', $this->textdomain ) . ' ';
			if ( $contains )
				$msg .= sprintf( __( 'contain: %s', $this->textdomain ), implode( ', ', $contains ) );
			if ( $contains && $ends )
				$msg .= '<br />' . __( 'and/or', $this->textdomain ) . ' ';
			if ( $ends )
				$msg .= sprintf( __( 'end with: %s', $this->textdomain ), implode( ', ', $ends ) );
			$message .= '<p class="message">' . $msg . "</p>\n";
		}
		return $message;
	}
*/
	/**
	 * Assesses if a username is restricted from use.
	 *
	 * @param bool $valid A boolean indicating WordPress's built-in assessment of the validity of the username.
	 * @param string $username The username to check for possible restriction.
	 * @return bool Boolean indicating if the username is restricted. True means username is restricted (and hence not valid).
	 */
	public function username_restrictor( $valid, $username ) {
		if ( ! $valid || ( ! isset( $_POST['c2c_test_usernames'] ) && is_user_logged_in() && current_user_can( 'create_users' ) ) )
			return $valid;

		$options = $this->get_options();
		$username = strtolower( $username );

		if ( $valid && $options['disallow_spaces'] && strpos( $username, ' ' ) !== false )
			$valid = false;

		if ( $valid && $options['usernames'] && in_array( $username, $options['usernames'] ) )
			$valid = false;

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
	 * Register the invalid username error if it had been detected.
	 *
	 * @param WP_Error $errors Errors
	 * @return WP_Error
	 */
	public function registration_errors( $errors ) {
		if ( $this->got_restricted && isset( $errors->errors['invalid_username'] ) )
			$errors->errors['invalid_username'][0] = __( '<strong>ERROR</strong>: This username is invalid. Please choose another.', $this->textdomain );
		return $errors;
	}

	/**
	 * Check username for restrictions under BuddyPress
	 *
	 * The username restriction check under BuddyPress is different than under
	 * WP proper because BP employs its own user validation checks. While it
	 * does pass registering usernames to validate_username() -- which this
	 * plugin normally hooks -- any failures reported trigger BP's generic
	 * 'Only lowercase letters and numbers allowed' error message. It's just
	 * easier to let BP do its checks and at the very end do the username
	 * restriction checks.
	 *
	 * Note: This function is hooked against the 'wpmu_validate_user_signup'
	 * filter because it is consistently present across more BP versions,
	 * whereas its own 'bp_core_validate_user_signup' is slated to be renamed
	 * 'bp_members_validate_user_signup' in BP1.3.
	 *
	 *
	 * @since 3.1
	 *
	 * @param array $result BP signup validation result array consisting of 'user_name', 'user_email', and 'errors' elements
	 * @return array The possibly modified results array
	 */
	public function bp_members_validate_user_signup( $result ) {
		// Only check username for restrictions if the username hasn't already generated some other error.
		$errs = $result['errors']->get_error_messages( 'user_name' );
		if ( empty( $errs ) ) {
			$valid = $this->username_restrictor( true, $result['user_name'] );
			if ( ! $valid ) {
				$errors = $result['errors'];
				$errors->add( 'user_name', __( 'Sorry, this username is invalid. Please choose another.', $this->textdomain ) );
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
		if ( isset( $_POST[$this->get_form_submit_name( 'submit_test_usernames' )] ) && isset( $_POST['c2c_test_usernames'] ) ) {
			check_admin_referer( $this->nonce_field );
			$msg = __( 'Results of username checks:', $this->textdomain );
			$msg .= '<ul>';
			$unames= explode( ',', $_POST['c2c_test_usernames'] );
			// The filter isn't normally added in the admin, so do so.
			add_filter( 'validate_username', array( &$this, 'username_restrictor' ), 10, 2 );
			$do_msg = false;
			foreach ( $unames as $u ) {
				$u = trim( $u );
				if ( $u === '' )
					continue;
				$msg .= '<li>' . esc_html( $u ) . ' : ';
				$msg .= validate_username( $u ) ? 'valid' : 'invalid';
				$msg .= '</li>';
				$do_msg = true;
			}
			$msg .= '</ul>';
			$msg .= '<p>' . __( 'Bear in mind that this only checks the syntax of the username. It does not test additional criteria (such as
				if the username already exists) that WordPress also checks for on registration.' ) . '</p>';
			if ( $do_msg )
				add_settings_error( 'general', 'usernames_tested', $msg, 'updated' );
		}
	}

	/*
	 * Outputs form to test usernames.
	 *
	 * @since 3.3
	 *
	 * @return void (Text will be echoed.)
	 */
	public function usernames_test_form() {
		$user = wp_get_current_user();
		$email = $user->user_email;
		$action_url = $this->form_action_url();
		echo '<div class="wrap"><h2><a name="username_check"></a>' . __( 'Test Usernames', $this->textdomain ) . "</h2>\n";
		echo '<p>' . __( 'Use the input field below to list usernames you\'d like to test against the plugin\'s restrictions. Separate multiple usernames with commas.', $this->textdomain ) . "</p>\n";
		echo '<p><em>You must save any changes to the form above before attempting to test usernames.</em></p>';
		echo "<form name='c2c_restrict_usernames' action='$action_url' method='post'>\n";
		wp_nonce_field( $this->nonce_field );
		echo '<input type="hidden" name="' . $this->get_form_submit_name( 'submit_test_usernames' ) .'" value="1" />';
		echo '<input type="text" size="80" name="c2c_test_usernames" />';
		echo '<div class="submit"><input type="submit" name="Submit" class="button-primary" value="' . esc_attr__( 'Test', $this->textdomain ) . '" /></div>';
		echo '</form></div>';
	}

} // end c2c_RestrictUsernames

// To access plugin object instance use: c2c_RestrictUsernames::$instance
new c2c_RestrictUsernames();

endif; // end if !class_exists()
