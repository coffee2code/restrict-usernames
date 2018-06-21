<?php
/*
Plugin Name: Restrict Usernames
Version: 1.0
Plugin URI: http://coffee2code.com/wp-plugins/restrict-usernames
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Restrict the usernames that new users may use when registering for your site.

Compatible with WordPress 2.6+.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates

Installation:

1. Download the file http://coffee2code.com/wp-plugins/restrict-usernames.zip and unzip it into your 
/wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. Go to the Users -> Name Restrictions admin options page. Optionally customize the settings.

*/

/*
Copyright (c) 2008 by Scott Reilly (aka coffee2code)

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
	var $show_admin = true;	// Change this to false if you don't want the plugin's admin page shown.
	var $config = array();
	var $options = array(); // Don't use this directly

	function RestrictUsernames() {
		$this->config = array(
			'usernames' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => 'style="width:98%;" rows="6"',
					'label' => 'Restricted usernames',
					'help' => 'The usernames that newly registering users cannot use.  Define one per line and use all lowercase.'),
			'partial_usernames' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => 'style="width:98%;" rows="6"',
					'label' => 'Restricted usernames (partial matching)',
					'help' => 'These are partial text values that cannot appear in usernames requested by newly registering users.  Useful to prevent usage of bad language or prevent users from user a notation used to identify admins of the site, i.e. "admin_".  Be aware that anything listed here will then not be allowed as any part of a username.  Define one per line and use all lowercase.')
		);

		add_action('admin_menu', array(&$this, 'admin_menu'));
		
		if (!is_admin())
			add_filter('validate_username', array(&$this, 'username_restrictor'), 10, 2);		
	}

	function install() {
		$this->options = $this->get_options();
		update_option($this->admin_options_name, $this->options);
	}

	function admin_menu() {
		if ( $this->show_admin )
			add_users_page('Name Restrictions', 'Name Restrictions', 9, basename(__FILE__), array(&$this, 'options_page'));
	}

	function get_options() {
		if ( !empty($this->options)) return $this->options;
		// Derive options from the config
		$options = array();
		foreach (array_keys($this->config) as $opt) {
			$options[$opt] = $this->config[$opt]['default'];
		}
        $existing_options = get_option($this->admin_options_name);
        if (!empty($existing_options)) {
            foreach ($existing_options as $key => $value)
                $options[$key] = $value;
        }            
		$this->options = $options;
        return $options;
	}

	function options_page() {
		$options = $this->get_options();
		// See if user has submitted form
		if ( isset($_POST['submitted']) ) {
			check_admin_referer($this->nonce_field);

			foreach (array_keys($options) AS $opt) {
				$options[$opt] = htmlspecialchars(stripslashes($_POST[$opt]));
				$input = $this->config[$opt]['input'];
				if (($input == 'checkbox') && !$options[$opt])
					$options[$opt] = 0;
				if ($this->config[$opt]['datatype'] == 'array') {
					if ($input == 'text')
						$options[$opt] = explode(',', str_replace(array(', ', ' ', ','), ',', $options[$opt]));
					else
						$options[$opt] = array_map('trim', explode("\n", trim($options[$opt])));
				}
				elseif ($this->config[$opt]['datatype'] == 'hash') {
					if ( !empty($options[$opt]) ) {
						$new_values = array();
						foreach (explode("\n", $options[$opt]) AS $line) {
							list($shortcut, $text) = array_map('trim', explode("=>", $line, 2));
							if (!empty($shortcut)) $new_values[str_replace('\\', '', $shortcut)] = str_replace('\\', '', $text);
						}
						$options[$opt] = $new_values;
					}
				}
			}
			// Remember to put all the other options into the array or they'll get lost!
			update_option($this->admin_options_name, $options);

			echo "<div class='updated'><p>Plugin settings saved.</p></div>";
		}

		$action_url = $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__);

		echo <<<END
		<div class='wrap'>
			<h2>Restrict Usernames Plugin Options</h2>
			<p>TODO</p>
			
			<form name="restrict_usernames" action="$action_url" method="post">	
END;
				wp_nonce_field($this->nonce_field);
		echo '<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform form-table"><tbody>';
				foreach (array_keys($options) as $opt) {
					$input = $this->config[$opt]['input'];
					if ($input == 'none') continue;
					$label = $this->config[$opt]['label'];
					$value = $options[$opt];
					if ($input == 'checkbox') {
						$checked = ($value == 1) ? 'checked=checked ' : '';
						$value = 1;
					} else {
						$checked = '';
					};
					if ($this->config[$opt]['datatype'] == 'array') {
						if (!is_array($value))
							$value = '';
						else {
							if ($input == 'textarea' || $input == 'inline_textarea')
								$value = implode("\n", $value);
							else
								$value = implode(', ', $value);
						}
					} elseif ($this->config[$opt]['datatype'] == 'hash') {
						if (!is_array($value))
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
					if ($input == 'textarea') {
						echo "<td colspan='2'>";
						if ($label) echo "<strong>$label</strong><br />";
						echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
					} else {
						echo "<th scope='row'>$label</th><td>";
						if ($input == "inline_textarea")
							echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
						elseif ($input == 'select') {
							echo "<select name='$opt' id='$opt'>";
							foreach ($this->config[$opt]['options'] as $sopt) {
								$selected = $value == $sopt ? " selected='selected'" : '';
								echo "<option value='$sopt'$selected>$sopt</option>";
							}
							echo "</select>";
						} else
							echo "<input name='$opt' type='$input' id='$opt' value='$value' $checked {$this->config[$opt]['input_attributes']} />";
					}
					if ($this->config[$opt]['help']) {
						echo "<br /><span style='color:#777; font-size:x-small;'>";
						echo $this->config[$opt]['help'];
						echo "</span>";
					}
					echo "</td></tr>";
				}
		echo <<<END
			</tbody></table>
			<input type="hidden" name="submitted" value="1" />
			<div class="submit"><input type="submit" name="Submit" value="Save Changes" /></div>
		</form>
			</div>
END;
		$logo = get_option('siteurl') . '/wp-content/plugins/' . basename($_GET['page'], '.php') . '/c2c_minilogo.png';
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
			This plugin brought to you by <a href="http://coffee2code.com" title="coffee2code.com">Scott Reilly, aka coffee2code</a>.
			<span><a href="http://coffee2code.com/donate" title="Please consider a donation">Did you find this plugin useful?</a></span>
			</div>
		</div>
END;
	}

	function username_restrictor($valid, $username) {
		if (is_user_logged_in() && current_user_can('create_users'))
			return $valid;
		$options = $this->get_options();
		$username = strtolower($username);
		if ($valid && $options['usernames'] && in_array($username, $options['usernames']))
			$valid = false;
		if ($valid && $options['partial_usernames']) {
			foreach ($options['partial_usernames'] as $partial) {
				if (stristr($username, $partial))
					$valid = false;
			}
		}
		return $valid;
	}
} // end RestrictUsernames

endif; // end if !class_exists()
if ( class_exists('RestrictUsernames') ) :
	// Get the ball rolling
	$restrict_usernames = new RestrictUsernames();
	// Actions and filters
	if (isset($restrict_usernames)) {
		register_activation_hook( __FILE__, array(&$restrict_usernames, 'install') );
	}
endif;

?>