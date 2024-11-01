<?php
/**
 * Plugin Name:       WP Reset Keys and Salts
 * Plugin URI:        https://wordpress.org/plugins/wp-reset-keys-and-salts/
 * Description:       Reset WordPress keys and salts at the click of a button.
 * Version:           1.0.4
 * Author:            Hawp Media
 * Author URI:        https://hawpmedia.com
 * License:           GPLv2 or later
 * Text Domain:       hawp-wp-ksreset
 */

/**
 * Exit if accessed directly.
 */
if (!defined('ABSPATH')) {
	exit();
}

class hm_ksreset_module {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_menu', array($this, 'add_menu_item'));
		add_action('admin_init', array($this, 'check_user_permissions'));
	}

	/**
	 * Load text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain('hawp-wp-ksreset', false, dirname(plugin_basename(__FILE__)) . '/lang');
	}

	/**
	 * Add plugin settings menu item under 'Tools'.
	 */
	public function add_menu_item() {
		add_management_page('Reset Keys & Salts', 'Reset Keys & Salts', 'manage_options', 'hawp-wp-ksreset', array($this, 'add_plugin_page'), '');
	}

	/**
	 * Add plugin settings page.
	 */
	public function add_plugin_page() {
		$settings='
		<div class="wrap">
			<h1>WP Reset Keys &amp; Salts</h1>
			<div class="card">
				<form action="options.php" method="post">
					<p>Click the button below to reset the wordpress keys and salts. NOTE: Immediately after you click this button you will be logged out, dont worry, that is completely normal.</p>
					<p><a href="'. esc_url(admin_url("?salt_reset=true")) .'" class="button-primary"> Reset Salts &amp; Log Out</a></p>
				</form>
				<h2 class="title">FAQ</h2>
				<h4>What are WordPress Keys and Salts?</h4>
				<p>In simple terms, a secret key is a password with elements that make it harder to generate enough options to break through your security barriers. A password like "password" or "test" is simple and easily broken. A random, long password which uses no dictionary words such as "88a7da62429ba6ad3cb3c76a09641fc" would take a brute force attacker millions of hours to crack. A salt is used to further enhance the security of the generated result. (Source: <a href="https://codex.wordpress.org/Editing_wp-config.php#Security_Keys" target="_blank">WordPress Codex: Editing wp-config.php</a>)</p>
				<h4>Can I delete this plugin after resetting the salts?</h4>
				<p>Of course! Your new keys and salts will remain in tact, and if you ever want to reset them again keep our plugin in mind.</p>
				<h4>Why am I logged out when the keys and salts are reset?</h4>
				<p>Your passwords and cookies have been re-encrypted, therefore in the database they are different (this does not change your regular login passwords, only the encrypted version in the back end)</p>
			</div>
		</div>';
		echo $settings;
	}

	/**
	 * Check user permissions.
	 */
	public function check_user_permissions() {
		if (is_admin() && current_user_can('manage_options') && !empty($_GET['salt_reset']) && $_GET['salt_reset'] == 'true') {
			$this->reset_salts();
		}
	}

	/**
	 * Reset Salts when admin button is pressed.
	 */
	public function reset_salts() {
		$salts_array = array(
			"define('AUTH_KEY',",
			"define('SECURE_AUTH_KEY',",
			"define('LOGGED_IN_KEY',",
			"define('NONCE_KEY',",
			"define('AUTH_SALT',",
			"define('SECURE_AUTH_SALT',",
			"define('LOGGED_IN_SALT',",
			"define('NONCE_SALT',",
		);
		$config_file = ABSPATH . 'wp-config.php'; // Defines the wp-config file
		$tmp_config_file = ABSPATH . 'wp-config.php.tmp'; // Defines temporary wp-config file
		if (file_exists($config_file)) {
			foreach($salts_array as $salt_value) {
				$read_config_file = fopen($config_file, 'r'); // Allows the function to read the config file
				$write_config_file = fopen($tmp_config_file, 'w'); // Allows the function to write to the temporary config file
				$replaced = false;
				while(!feof($read_config_file)) {
					$linevalue = fgets($read_config_file);
					if (stristr($linevalue, $salt_value)) {
						$linevalue = $salt_value.' \''.wp_generate_password(64, true, true).'\');'."\n"; // Generate password
						$replaced = true; // If salt value is new, change variable
					}
					fputs($write_config_file, $linevalue); // Add new 64 character values to the temporary wp-config file
				}
				fclose($read_config_file); // Close the open config file reading
				fclose($write_config_file); // Close temporary config file writing
				if ($replaced) {
					rename($tmp_config_file, $config_file); // If replaced is true, rename wp-config.php.tmp to wp-config.php
				} else {
					unlink($tmp_config_file); // If replaced is false, delete the temporary (wp-config.php.tmp) file
				}
			}
		}
		wp_redirect(admin_url());
		exit();
	}

}
$hm_ksreset_module = new hm_ksreset_module();