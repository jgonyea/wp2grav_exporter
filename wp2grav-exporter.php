<?php
/**
 * Plugin Name: Grav Export
 * Plugin URI:  https://www.github.com/jgonyea/wp2grav_exporter
 * Description: This plugin converts WP content for use in a GravCMS instance.
 * Version:     0.4.1
 * Author:      Jeremy Gonyea
 * Author URI:  https://www.gonyea.io
 * License:     MIT
 * License URI: https://mit-license.org/
 *
 * @package wp2grav
 */

/**
 * Loads composer dependencies.
 */
require 'vendor/autoload.php';

add_action( 'admin_menu', 'wp2grav_admin_menu' );

// Create custom admin menu.
function wp2grav_admin_menu() {
	add_submenu_page(
		'tools.php',							// parent slug
		'WP2Grav Expoter Main Page',        // Page title
		'WP2Grav Exporter',              // Menu title
		'manage_options',           // Capability (who can access)
		'wp2grav-exporter',           // Menu slug
		'wp2grav_admin_page_callback',    // Function to display page content
		15,								// position
	);
}

function wp2grav_admin_page_callback() {
	?>
		<form action="<?php echo esc_url( admin_url( 'export-personal-data.php' ) ); ?>" method="post" class="wp-privacy-request-form">
		<h2><?php esc_html_e( 'WP2Grav Expoter' ); ?></h2>
		<div class="wp-wp2grav-data-request" style="width: 75%;">
			<label for="bo0ofw4v5pk" class="block text-sm font-medium mb-1 text-foreground/90">Please select what WordPress items you would like to export</label>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<input id="bo0ofw4v5pk-4" type="checkbox" name="bo0ofw4v5pk[]">
					<label for="bo0ofw4v5pk-4">-- All --</label>
				</div>
				<div class="flex items-center gap-2">
					<input id="bo0ofw4v5pk-0" type="checkbox" name="bo0ofw4v5pk[]">
					<label for="bo0ofw4v5pk-0">Users</label>
				</div>
				<div class="flex items-center gap-2">
					<input id="bo0ofw4v5pk-1" type="checkbox" name="bo0ofw4v5pk[]">
					<label for="bo0ofw4v5pk-1">Roles</label>
				</div>
				<div class="flex items-center gap-2">
					<input id="bo0ofw4v5pk-2" type="checkbox" name="bo0ofw4v5pk[]">
					<label for="bo0ofw4v5pk-2">Posts</label>
				</div>
				<div class="flex items-center gap-2">
						<input id="bo0ofw4v5pk-3" type="checkbox" name="bo0ofw4v5pk[]">
						<label for="bo0ofw4v5pk-3">Post Types</label>
				</div>
				<div class="flex items-center gap-2">
					<input id="bo0ofw4v5pk-4" type="checkbox" name="bo0ofw4v5pk[]">
					<label for="bo0ofw4v5pk-4">Site Configuration</label>
				</div>
			</div>

			<p class="submit">
				<?php submit_button( __( 'Send Request' ), 'secondary', 'submit', false ); ?>
			</p>
		</div>
		<?php wp_nonce_field( 'wp2grav-data-request' ); ?>
		<input type="hidden" name="action" value="add_export_personal_data_request" />
		<input type="hidden" name="type_of_action" value="export_personal_data" />
	</form>
	<hr />

	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$plugin_dir = plugin_dir_path( __FILE__ ) . 'plugins';
	// Load plugins.

	$files = glob( $plugin_dir . '/wp2grav-*.php' );

	foreach ( $files as $file ) {
		// Import plugins.
		require_once $file;

		// Derive expected function names from filenames.
		$plugin_name          = substr( $file, strlen( $plugin_dir ) + 1 );
		$plugin_name          = substr( $plugin_name, 0, -4 );
		$plugin_name_exploded = explode( '-', $plugin_name );
		array_shift( $plugin_name_exploded );
		$plugin_name_imploded = implode( '_', $plugin_name_exploded );

		// Register commands with wp-cli.
		WP_CLI::add_command( $plugin_name, 'wp2grav_export_' . $plugin_name_imploded );
	}
}

/**
 * Converts a WordPress role name to a valid Grav group name.
 *
 * @param string $role_name WordPress role to be converted for Grav group name.
 * @return string Grav compatible group name.
 */
function convert_role_wp_to_grav( $role_name ) {
	return preg_replace( '/\ /', '_', $role_name );
}

/**
 * Converts a WordPress username to Grav.
 *
 * @param WP_USER $user WordPress user to be converted.
 * @param int     $user_char_min_limit Minimum username character limit.
 * @param int     $user_char_max_limit Maximum username character limit.
 * @return string Grav username.
 */
function convert_username_wp_to_grav( $user, $user_char_min_limit = 4, $user_char_max_limit = 16 ) {

	// Default Grav settings are 4 to 16. Make sure to reflect changes in Grav's system.yaml.

	$username = $user->user_login;

	$username = mb_strtolower( $username, 'UTF-8' );

	// Replace invalid characters with underscore.
	$patterns     = array(
		'space'      => '/\ /',
		'period'     => '/\./',
		'apostrophe' => '/\'/',
	);
	$replacements = array(
		'space'      => '_',
		'period'     => '_',
		'apostrophe' => '_',
	);
	$username     = preg_replace( $patterns, $replacements, $username );

	// Pad short usernames.
	if ( strlen( $username ) < $user_char_min_limit ) {
		$username .= $user->ID;
		$username  = str_pad( $username, $user_char_min_limit, '_' );
	}

	// Trim long usernames.
	if ( strlen( $username ) > $user_char_max_limit ) {
		$uid_length = strlen( $user->ID );
		$username   = substr( $username, 0, ( $user_char_max_limit - $uid_length ) );
		$username  .= $user->ID;
	}

	return $username;
}

/**
 * Finds all posts of a post_type.
 *  This is used to find all posts of a certain post type.  The built-in function of get_post doesn't find drafts/ scheduled.
 *
 * @param string $type Specific post type to search for.
 * @return array WP posts search results.
 */
function wp2grav_find_posts_of_type( $type = 'post' ) {
	$stati = get_post_stati();
	// Don't export auto-drafts.
	unset( $stati['auto-draft'] );
	$posts = get_posts(
		array(
			'post_type'   => $type,
			'numberposts' => -1,
			'post_status' => $stati,
		)
	);

	return $posts;
}

/**
 * Get the default export directory.
 *
 * @return string Export directory.
 */
function get_export_dir() {
	return WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
}
	