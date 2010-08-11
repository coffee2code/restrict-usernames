<?php
/**
 * @package Restrict_Usernames
 * @author Scott Reilly
 * @version 2.0.1
 */
/*
Plugin Name: Restrict Usernames
Version: 2.0.1
Plugin URI: http://coffee2code.com/wp-plugins/restrict-usernames
Author: Scott Reilly
Author URI: http://coffee2code.com
Text Domain: restrict-usernames
Description: Restrict the usernames that new users may use when registering for your site.

If open registration is enabled for your site (via Settings -> General -> Membership ("Anyone can register")), WordPress allows 
visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn't an already
existing account and it doesn't include invalid (i.e. non-alphanumeric) characters.

Possible reasons for wanting to restrict certain usernames:
	* Prevent usernames that contain foul, offensive, or otherwise undesired words
	* Prevent squatting on usernames that you may want to use in the future (but don't want to actually create the account for
		just yet) (essentially placing a hold on the username)
	* Prevent official-sounding usernames from being used (i.e. help, support, pr, info)
	* Prevent official username syntax from being used (i.e. if all of your admins use a prefix to identify themselves, you don't
		want a visitor to use that prefix)
	* Prevent spaces from being used in a username (which WordPress allows by default)
	* Require that a username begin, end, or contain one of a set of substrings (i.e. "support_", "admin_")

When attempting to register with a restricted username, the visitor will be given an error notice that says:
ERROR: This username is invalid. Please enter a valid username.

NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within
the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.

Compatible with WordPress 2.8+, 2.9+.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates

Installation:

1. Download the file http://coffee2code.com/wp-plugins/restrict-usernames.zip and unzip it into your 
/wp-content/plugins/ directory (or install via the built-in WordPress plugin installer).
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. Go to the Users -> Name Restrictions admin settings page. Specify username restrictions.

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

if ( !class_exists('RestrictUsernames') ) :

class RestrictUsernames {
	var $admin_options_name = 'c2c_restrict_usernames';
	var $nonce_field = 'update-restrict_usernames';
	var $textdomain = 'restrict-usernames';
	var $show_admin = true;	// Change this to false if you don't want the plugin's admin page shown.
	var $config = array();
	var $options = array(); // Don't use this directly
	var $plugin_basename = '';
	var $plugin_name = '';
	var $short_name = '';
	var $got_restricted = false;

	/**
	 * Handles installation tasks, such as ensuring plugin options are instantiated and saved to options table.
	 *
	 * @return void
	 */
	function RestrictUsernames() {
		$this->plugin_basename = plugin_basename(__FILE__);

		add_action('init', array(&$this, 'init'));
		add_action('activate_' . str_replace(trailingslashit(WP_PLUGIN_DIR), '', __FILE__), array(&$this, 'install'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
//		add_filter('login_message', array(&$this, 'login_message'));

		if ( !is_admin() )
			add_filter('validate_username', array(&$this, 'username_restrictor'), 10, 2);
	}

	/**
	 * Handles installation tasks, such as ensuring plugin options are instantiated and saved to options table.
	 *
	 * @return void
	 */
	function install() {
		$this->options = $this->get_options();
		update_option($this->admin_options_name, $this->options);
	}

	/**
	 * Handles actions to be hooked to 'init' action, such as loading text domain and loading plugin config data array.
	 *
	 * @return void
	 */
	function init() {
		load_plugin_textdomain( $this->textdomain, false, basename(dirname(__FILE__)) );
		$this->load_config();
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	function load_config() {
		$this->plugin_name = __('Restrict Usernames', $this->textdomain);
		$this->short_name = __('Name Restrictions', $this->textdomain);
		$input_style = 'style="width:50%;" rows="6"';
		$this->config = array(
			'disallow_spaces' => array('input' => 'checkbox', 'default' => false,
					'label' => __('Don\'t allow spaces in usernames.', $this->textdomain),
					'help' => __('WordPress allows spaces in usernames.  Check this if you don\'t want to allow spaces.', $this->textdomain)),
			'usernames' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __('Restricted usernames', $this->textdomain),
					'help' => __('List the usernames that newly-registering users cannot use.  Define one per line and use all lowercase.', $this->textdomain)),
			'partial_usernames' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __('Restricted usernames (partial matching)', $this->textdomain),
					'help' => __('These are partial text values that cannot appear in usernames requested by newly-registering users.  Useful to prevent usage of bad language or prevent users from using a notation used to identify admins of the site, i.e. "admin_".  Be aware that anything listed here will then not be allowed as any part of a username.  Define one per line and use all lowercase.', $this->textdomain)),
			'required_partials' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => $input_style,
					'label' => __('Required username substring', $this->textdomain),
					'help' => __('These are partial text values, one of which MUST appear in any username requested by newly-registering users.  Useful to force users to include some sort of identifier in their username, like "support_" (leading to "support_john") or "admin_" ("admin_steve"), etc.  A username needs to only include ONE of the listed partials.  Prepend a partial with "^" (i.e. "^support_" to require that partial as the start of a username) or end with "^" to require that partial be at the end (i.e. "_support^").  Without use of "^", the partial can appear in any position in the username.  Be aware that this plugin does not convey to the user what these requirements are, it only enforces the requirement.  Define one per line and use all lowercase.', $this->textdomain))
		);
	}

	/**
	 * Registers the admin options page and the Settings link.
	 *
	 * @return void
	 */
	function admin_menu() {
		if ( $this->show_admin && current_user_can('manage_options') ) {
			add_filter( 'plugin_action_links_' . $this->plugin_basename, array(&$this, 'plugin_action_links') );
			add_users_page($this->plugin_name, $this->short_name, 'manage_options', $this->plugin_basename, array(&$this, 'options_page'));
		}
	}

	/**
	 * Adds a 'Settings' link to the plugin action links.
	 *
	 * @param int $limit The default limit value for the current posts query.
	 * @return array Links associated with a plugin on the admin Plugins page
	 */
	function plugin_action_links( $action_links ) {
		$settings_link = '<a href="users.php?page='.$this->plugin_basename.'">' . __('Settings', $this->textdomain) . '</a>';
		array_unshift( $action_links, $settings_link );
		return $action_links;
	}

	/**
	 * Returns either the buffered array of all options for the plugin, or
	 * obtains the options and buffers the value.
	 *
	 * @param bool $with_current_values (optional) Should the currently saved values be returned? If false, then the plugin's defaults are returned. Default is true.
	 * @return array The options array for the plugin (which is also stored in $this->options if !$with_options).
	 */
	function get_options( $with_current_values = true ) {
		if ( $with_current_values && !empty( $this->options ) ) return $this->options;
		// Derive options from the config
		$options = array();
		foreach ( array_keys($this->config) as $opt )
			$options[$opt] = $this->config[$opt]['default'];
		if ( !$with_current_values )
			return $options;
		$this->options = wp_parse_args( get_option( $this->admin_options_name ), $options );
		return $this->options;
	}

	/**
	 *
	 */
	function get_option_names( $include_non_options = false ) {
		if ( !$include_non_options && !empty( $this->option_names ) ) return $this->option_names;
		if ( $include_non_options )
			return array_keys( $this->config );
		$this->option_names = array();
		foreach ( array_keys( $this->config ) as $opt ) {
			if ( $this->config[$opt]['input'] != '' )
				$this->option_names[] = $opt;
		}
		return $this->option_names;
	}

	/**
	 * Saves updates to options, if being POSTed.  In either case, also return the options.
	 *
	 * @return array $options The array of options.
	 */
	function maybe_save_options() {
		$options = $this->get_options();
		// See if user has submitted form
		if ( isset( $_POST['submitted'] ) ) {
			check_admin_referer( $this->nonce_field );
			if ( isset( $_POST['Reset'] ) ) {
				$options = $this->get_options( false );
				$msg = __( 'Settings reset.', $this->textdomain );
			} else {
				foreach ( $this->get_option_names() AS $opt ) {
					$options[$opt] = htmlspecialchars( stripslashes( trim( $_POST[$opt] ) ) );
					$input = $this->config[$opt]['input'];
					if ( ($input == 'checkbox') && !$options[$opt] )
						$options[$opt] = 0;
					if ( $this->config[$opt]['datatype'] == 'array' ) {
						if ( empty( $options[$opt] ) )
							$options[$opt] = array();
						elseif ( $input == 'text' )
							$options[$opt] = explode( ',', str_replace( array( ', ', ' ', ',' ), ',', $options[$opt] ) );
						else
							$options[$opt] = array_map( 'trim', explode( "\n", trim( $options[$opt] ) ) );
					}
					elseif ( $this->config[$opt]['datatype'] == 'hash' ) {
						if ( !empty( $options[$opt] ) ) {
							$new_values = array();
							foreach ( explode( "\n", $options[$opt] ) AS $line ) {
								list( $shortcut, $text ) = array_map( 'trim', explode( "=>", $line, 2 ) );
								if ( !empty($shortcut) ) $new_values[str_replace( '\\', '', $shortcut )] = str_replace( '\\', '', $text );
							}
							$options[$opt] = $new_values;
						}
					}
				}
				$msg = __( 'Settings saved.', $this->textdomain );
			}
			// Remember to put all the other options into the array or they'll get lost!
			update_option( $this->admin_options_name, $options );
			$this->options = $options;
			echo "<div id='message' class='updated fade'><p><strong>" . $msg . '</strong></p></div>';
		}
		return $options;
	}

	/**
	 * Outputs the markup for an option's form field (and surrounding markup)
	 *
	 * @param string $opt The name/key of the option.
	 * @return void
	 */
	function display_option( $opt ) {
		$options = $this->get_options();
		$input = $this->config[$opt]['input'];
		if ( $input == 'none' ) continue;
		$label = $this->config[$opt]['label'];
		$value = $options[$opt];
		if ( $input == 'checkbox' ) {
			$checked = ($value == 1) ? 'checked=checked ' : '';
			$value = 1;
		} else {
			$checked = '';
		};
		if ( $this->config[$opt]['datatype'] == 'array' ) {
			if ( !is_array($value) )
				$value = '';
			else {
				if ( $input == 'textarea' || $input == 'inline_textarea' )
					$value = implode("\n", $value);
				else
					$value = implode(', ', $value);
			}
		} elseif ( $this->config[$opt]['datatype'] == 'hash' ) {
			if ( !is_array($value) )
				$value = '';
			else {
				$new_value = '';
				foreach ($value AS $shortcut => $replacement) {
					$new_value .= "$shortcut => $replacement\n";
				}
				$value = $new_value;
			}
		}
		echo "<tr valign='top'>";
		if ( $input == 'textarea' ) {
			echo "<td colspan='2'>";
			if ( $label ) echo "<strong>$label</strong><br />";
			echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . esc_html($value) . '</textarea>';
		} else {
			echo "<th scope='row'>$label</th><td>";
			if ( $input == "inline_textarea" )
				echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . esc_html($value) . '</textarea>';
			elseif ( $input == 'select' ) {
				echo "<select name='$opt' id='$opt'>";
				foreach ($this->config[$opt]['options'] as $sopt) {
					$selected = $value == $sopt ? " selected='selected'" : '';
					echo "<option value='$sopt'$selected>$sopt</option>";
				}
				echo "</select>";
			} else {
				$tclass = ($input == 'short_text') ? 'small-text' : 'regular-text';
				if ($input == 'short_text') $input = 'text';
				echo "<input name='$opt' type='$input' id='$opt' value='" . esc_attr($value) .
					"' class='$tclass' $checked {$this->config[$opt]['input_attributes']} />";
			}
		}
		if ( $this->config[$opt]['help'] ) {
			echo "<br /><span style='color:#777; font-size:x-small;'>";
			echo $this->config[$opt]['help'];
			echo "</span>";
		}
		echo "</td></tr>";
	}

	/**
	 * Outputs the options page for the plugin, and saves user updates to the
	 * options.
	 *
	 * @return void
	 */
	function options_page() {
		$options = $this->maybe_save_options();

		$action_url = $_SERVER['PHP_SELF'] . '?page=' . $this->plugin_basename;
		$logo = plugins_url(basename($_GET['page'], '.php') . '/c2c_minilogo.png');

		echo "<div class='wrap'>";
		echo "<div class='icon32' style='width:44px;'><img src='$logo' alt='" . esc_attr__('A plugin by coffee2code', $this->textdomain) . "' /><br /></div>";
		echo '<h2>' . __('Restrict Usernames Settings', $this->textdomain) . '</h2>';
		echo '<p>' . __('If open registration is enabled for your site (via Settings &rarr; General &rarr; Membership ("Anyone can register")), WordPress allows visitors to register for an account on your blog.  By default, any username they choose is allowed so long as it isn\'t an already existing account and it doesn\'t include invalid (i.e. non-alphanumeric) characters.', $this->textdomain) . '</p>';
		echo '<p>' . __('Possible reasons for wanting to restrict certain usernames:', $this->textdomain) . '</p>';
		echo '<ul>';
		echo '<li> * ' . __('Prevent usernames that contain foul, offensive, or otherwise undesired words', $this->textdomain) . '</li>';
		echo '<li> * ' . __('Prevent squatting on usernames that you may want to use in the future (but don\'t want to actually create the account for just yet) (essentially placing a hold on the username)', $this->textdomain) . '</li>';
		echo '<li> * ' . __('Prevent official-sounding usernames from being used (i.e. help, support, pr, info)', $this->textdomain) . '</li>';
		echo '<li> * ' . __('Prevent official username syntax from being used (i.e. if all of your admins use a prefix to identify themselves, you don\'t want a visitor to use that prefix)', $this->textdomain) . '</li>';
		echo '</ul>';
		echo '<p>' . __('When attempting to register with a restricted username, the visitor will be given an error notice that says:', $this->textdomain) . '<br />';
		echo __('ERROR: This username is invalid. Please enter a valid username.', $this->textdomain) . '</p>';
		echo '<p>' . __('NOTE: This plugin does not put any restrictions on usernames that the admin chooses for users when creating user accounts from within the WordPress admin.  This only restricts the names that users choose themselves when registering for your site.', $this->textdomain) . '</p>';

		echo "<form name='restrict_usernames' action='$action_url' method='post'>";
		wp_nonce_field($this->nonce_field);
		echo '<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform form-table"><tbody>';
		foreach ( array_keys($options) as $opt ) {
			$this->display_option($opt);
		}
		$txt = esc_attr__('Save Changes', $this->textdomain);
		$reset_txt = esc_attr__('Reset Settings', $this->textdomain);
		echo <<<END
			</tbody></table>
			<input type="hidden" name="submitted" value="1" />
			<div class="submit"><input type="submit" name="Submit" class="button-primary" value="{$txt}" />
			<input type="submit" name="Reset" class="button" value="{$reset_txt}" /></div>
		</form>
			</div>
END;

		echo <<<END
		<style type="text/css">
			#c2c {
				text-align:center;
				color:#888;
				background-color:#ffffef;
				padding:5px 0 0;
				margin-top:12px;
				border-style:solid;
				border-color:#dadada;
				border-width:1px 0;
			}
			#c2c div {
				margin:0 auto;
				padding:5px 40px 0 0;
				width:45%;
				min-height:40px;
				background:url('$logo') no-repeat top right;
			}
			#c2c span {
				display:block;
				font-size:x-small;
			}
		</style>
		<div id='c2c' class='wrap'>
			<div>
END;
		$c2c = '<a href="http://coffee2code.com" title="coffee2code.com">' . __('Scott Reilly, aka coffee2code', $this->textdomain) . '</a>';
		echo sprintf(__('This plugin brought to you by %s.', $this->textdomain), $c2c);
		echo '<span><a href="http://coffee2code.com/donate" title="' . esc_attr__('Please consider a donation', $this->textdomain) . '">' .
		__('Did you find this plugin useful?', $this->textdomain) . '</a></span>';
		echo '</div></div>';
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
					$starts[] = substr($partial, 1);
				elseif ( substr($partial, -1, 1) == '^' )
					$ends[] = substr($partial, 0, -1);
				else
					$contains[] = $partial;
			}
			$msg = __('Usernames must', $this->textdomain) . ' <br />';
			if ( $starts )
				$msg .= sprintf(__('start with: %s', $this->textdomain), implode(', ', $starts));
			if ( $starts && ($contains || $ends) )
				$msg .= '<br />' . __('and/or', $this->textdomain) . ' ';
			if ( $contains )
				$msg .= sprintf(__('contain: %s', $this->textdomain), implode(', ', $contains));
			if ( $contains && $ends )
				$msg .= '<br />' . __('and/or', $this->textdomain) . ' ';
			if ( $ends )
				$msg .= sprintf(__('end with: %s', $this->textdomain), implode(', ', $ends));
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
		if ( !$valid || (is_user_logged_in() && current_user_can('create_users')) )
			return $valid;
		$options = $this->get_options();
		$username = strtolower($username);
		if ( $valid && $options['disallow_spaces'] && strpos($username, ' ') !== false )
			$valid = false;
		if ( $valid && $options['usernames'] && in_array($username, $options['usernames']) )
			$valid = false;
		if ( $valid && $options['partial_usernames'] ) {
			foreach ( $options['partial_usernames'] as $partial ) {
				if ( strpos($username, $partial) !== false )
					$valid = false;
					break;
			}
		}
		if ( $valid && $options['required_partials'] ) {
			$valid = false;
			foreach ( $options['required_partials'] as $partial ) {
				if ( $partial{0} == '^' ) {
					$partial = substr($partial, 1);
					if ( ($username != $partial) && (strpos($username, $partial) === 0) ) {
						$valid = true;
						break;
					}
				}
				elseif ( substr($partial, -1, 1) == '^' ) {
					$partial = substr($partial, 0, -1);
					if ( ($username != $partial) && (substr($username, -strlen($partial)) == $partial) ) {
						$valid = true;
						break;
					}
				}
				elseif ( strpos($username, $partial) !== false ) {
					$valid = true;
					break;
				}
			}
		}
		$this->got_restricted = $valid;
		return $valid;
	}
} // end RestrictUsernames

endif; // end if !class_exists()

if ( class_exists('RestrictUsernames') )
	new RestrictUsernames();

?>