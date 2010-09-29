<?php
/**
 * @package Restrict_Usernames
 * @author Scott Reilly
 * @version 3.0
 */
/*
Plugin Name: Restrict Usernames
Version: 3.0
Plugin URI: http://coffee2code.com/wp-plugins/restrict-usernames/
Author: Scott Reilly
Author URI: http://coffee2code.com
Text Domain: restrict-usernames
Description: Restrict the usernames that new users may use when registering for your site.

Compatible with WordPress 2.8+, 2.9+, 3.0+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/restrict-usernames/

*/

/*
Copyright (c) 2008-2010 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( !class_exists( 'c2c_RestrictUsernames' ) ) :

require_once( 'c2c-plugin.php' );

class c2c_RestrictUsernames extends C2C_Plugin_016 {

	var $got_restricted = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function c2c_RestrictUsernames() {
		$this->C2C_Plugin_016( '3.0', 'restrict-usernames', 'c2c', __FILE__, array( 'settings_page' => 'users' ) );
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	function load_config() {
		$this->name = __( 'Restrict Usernames', $this->textdomain );
		$this->menu_name = __( 'Name Restrictions', $this->textdomain );

		$input_style = 'style="width:50%;" rows="6"';

		$this->config = array(
			'disallow_spaces' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Don\'t allow spaces in usernames.', $this->textdomain ),
					'help' => __( 'WordPress allows spaces in usernames.  Check this if you don\'t want to allow spaces.', $this->textdomain ) ),
			'usernames' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Restricted usernames', $this->textdomain ),
					'help' => __( 'List the usernames that newly-registering users cannot use.  Define one per line and use all lowercase.', $this->textdomain ) ),
			'partial_usernames' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Restricted usernames (partial matching)', $this->textdomain ),
					'help' => __( 'These are partial text values that cannot appear in usernames requested by newly-registering users.  Useful to prevent usage of bad language or prevent users from using a notation used to identify admins of the site, i.e. "admin_".  Be aware that anything listed here will then not be allowed as any part of a username.  Define one per line and use all lowercase.', $this->textdomain ) ),
			'required_partials' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __( 'Required username substring', $this->textdomain ),
					'help' => __( 'These are partial text values, one of which MUST appear in any username requested by newly-registering users.  Useful to force users to include some sort of identifier in their username, like "support_" (leading to "support_john") or "admin_" ("admin_steve"), etc.  A username needs to only include ONE of the listed partials.  Prepend a partial with "^" (i.e. "^support_" to require that partial as the start of a username) or end with "^" to require that partial be at the end (i.e. "_support^").  Without use of "^", the partial can appear in any position in the username.  Be aware that this plugin does not convey to the user what these requirements are, it only enforces the requirement.  Define one per line and use all lowercase.', $this->textdomain ) )
		);
	}

	/**
	 * Override the plugin framework's register_filters() to register actions and filters.
	 *
	 * @return void
	 */
	function register_filters() {
//		add_filter( 'login_message', array( &$this, 'login_message' ) );
		if ( !is_admin() ) {
			add_filter( 'validate_username', array( &$this, 'username_restrictor' ), 10, 2 );
			add_filter( 'registration_errors', array( &$this, 'registration_errors' ) );
		}
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	function options_page_description() {
		parent::options_page_description( __( 'Restrict Usernames Settings', $this->textdomain ) );
		echo '<p>' . __( 'If open registration is enabled for your site (via Settings &rarr; General &rarr; Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn\'t an already existing account and it doesn\'t include invalid (i.e. non-alphanumeric) characters.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Possible reasons for wanting to restrict certain usernames:', $this->textdomain ) . '</p>';
		echo '<ul class="c2c-plugin-list">';
		echo '<li>' . __( 'Prevent usernames that contain foul, offensive, or otherwise undesired words', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent squatting on usernames that you may want to use in the future (but don\'t want to actually create the account for just yet) (essentially placing a hold on the username)', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent official-sounding usernames from being used (i.e. help, support, pr, info)', $this->textdomain ) . '</li>';
		echo '<li>' . __( 'Prevent official username syntax from being used (i.e. if all of your admins use a prefix to identify themselves, you don\'t want a visitor to use that prefix)', $this->textdomain ) . '</li>';
		echo '</ul>';
		echo '<p>' . __( 'When attempting to register with a restricted username, the visitor will be given an error notice that says:', $this->textdomain ) . '<br />';
		echo __( 'ERROR: This username is invalid. Please enter a valid username.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.', $this->textdomain ) . '</p>';
	}

	/**
	 * Outputs message to login page to tell visitor valid user syntax for usernames.
	 *
	 * @param string $message Pending login message.
	 * @return string The incoming login message appended with text regarding valid user syntax.
	 */
/*	function login_message( $message ) {
		$options = $this->get_options();
		if ( $options['required_partials'] ) {
			$starts = array();
			$ends = array();
			$contains = array();
			foreach ( $options['required_partials'] as $partial ) {
				if ( $partial{0} == '^' )
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
	function username_restrictor( $valid, $username ) {
		if ( !$valid || ( is_user_logged_in() && current_user_can( 'create_users' ) ) )
			return $valid;
		$options = $this->get_options();
		$username = strtolower( $username );
		if ( $valid && $options['disallow_spaces'] && strpos( $username, ' ' ) !== false )
			$valid = false;
		if ( $valid && $options['usernames'] && in_array( $username, $options['usernames'] ) )
			$valid = false;
		if ( $valid && $options['partial_usernames'] ) {
			foreach ( $options['partial_usernames'] as $partial ) {
				if ( strpos( $username, $partial ) !== false )
					$valid = false;
					break;
			}
		}
		if ( $valid && $options['required_partials'] ) {
			$valid = false;
			foreach ( $options['required_partials'] as $partial ) {
				if ( $partial{0} == '^' ) {
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
		$this->got_restricted = !$valid;
		return $valid;
	}

	function registration_errors( $errors ) {
		if ( $this->got_restricted && isset( $errors->errors['invalid_username'] ) )
			$errors->errors['invalid_username'][0] = __( '<strong>ERROR</strong>: This username is invalid. Please choose another.', $this->textdomain );
		return $errors;
	}
} // end c2c_RestrictUsernames

$GLOBALS['c2c_restrict_usernames'] = new c2c_RestrictUsernames();

endif; // end if !class_exists()

?>