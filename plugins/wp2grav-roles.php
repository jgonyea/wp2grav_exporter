<?php
/**
 * WP-CLI custom command: Exports WP roles in GravCMS format.
 * Syntax: wp wp2grav-roles
 *
 * @package wp2grav
 */

use Symfony\Component\Yaml\Yaml;

// Prepare to save content.
if ( ! isset( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

/**
 * Exports WP roles to a GravCMS-formatted group.yaml file.
 *
 * @throws Exception Error if export folder unwriteable.
 * @return void
 */
function wp2grav_export_roles() {
	global $wp_filesystem;
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( WP_CLI::colorize( '%YBeginning role export%n ' ) );
	}
	$export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/config/';
	if ( ! wp_mkdir_p( $export_folder ) ) {
		WP_CLI::error( 'Could not create export folder' );
		die();
	}

	$wp_roles = wp_roles()->roles;
	$groups   = array();
	$progress = \WP_CLI\Utils\make_progress_bar( ' |- Generating ' . count( $wp_roles ) . ' user roles', count( $wp_roles ), $interval = 100 );
	foreach ( $wp_roles as $key => $role ) {
		$role_name                                       = 'wp_' . convert_role_wp_to_grav( $key );
		$groups[ $role_name ]['icon']                    = 'cog';
		$groups[ $role_name ]['readableName']            = convert_role_wp_to_grav( $role['name'] );
		$groups[ $role_name ]['description']             = 'Exported WordPress "' . convert_role_wp_to_grav( $key ) . '" role.';
		$groups[ $role_name ]['access']['site']['login'] = true;

		// Grant further Grav Admin access.
		if ( 'administrator' === $key ) {
			$groups[ $role_name ]['access']['admin'] = array(
				'login' => true,
				'super' => true,
			);
		}
		$progress->tick();
	}

	// Create new role of authenticated user that will grant basic admin login to all exported users.
	if ( ! array_key_exists( 'wp_authenticated_user', $groups ) ) {
		$groups['wp_authenticated_user']['icon']                     = 'cog';
		$groups['wp_authenticated_user']['readableName']             = convert_role_wp_to_grav( 'Authenticated User' );
		$groups['wp_authenticated_user']['description']              = 'Grants all WordPress roles login rights to admin portal';
		$groups['wp_authenticated_user']['access']['admin']['login'] = true;
	}

	// Finish the progress bar.
	$progress->finish();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( "Saving role export data to $export_folder/groups.yaml" );
	}
	$group_content = Yaml::dump( $groups, 20, 4 );

	try {
		if ( ! $wp_filesystem->put_contents( $export_folder . '/groups.yaml', $group_content ) ) {
			throw new Exception( 'Could not save groups.yaml export file' );
		}
	} catch ( Exception $e ) {
		WP_CLI::error( $e->getMessage(), $exit = true );
	}
	WP_CLI::success( ( count( $wp_roles ) ) . ' roles exported' );
}
