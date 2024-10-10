<?php
/**
 * WP-CLI custom command: Exports WP roles in GravCMS format.
 * Syntax: wp wp2grav-roles
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Exports WP roles to a GravCMS-formatted group.yaml file.
 *
 * @param array $args CLI arguments.
 * @param array $assoc_args Associated CLI arguments.
 * @throws Exception Error if export folder unwriteable.
 * @return void
 */
function wp2grav_export_roles( $args, $assoc_args ) {
	WP_CLI::line( WP_CLI::colorize( '%YBeginning role export%n ' ) );
	$export_folder = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/config/';
	if ( ! wp_mkdir_p( $export_folder ) ) {
		WP_CLI::error( 'Could not create export folder' );
		die();
	}

	$roles    = get_editable_roles();
	$groups   = array();
	$progress = \WP_CLI\Utils\make_progress_bar( 'Generating roles data', count( $roles ), $interval = 100 );
	foreach ( $roles as $key => $role ) {
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
		$groups['wp_authenticated_user']['readableName']             = convert_role_wp_to_grav( $role['name'] );
		$groups['wp_authenticated_user']['description']              = 'Grant WordPress roles login rights to admin portal';
		$groups['wp_authenticated_user']['access']['admin']['login'] = true;
	}

	// Finish the progress bar.
	$progress->finish();

	WP_CLI::line( "Saving role export data to $export_folder/groups.yaml" );
	$group_content = Yaml::dump( $groups, 20, 4 );

	try {
		if ( ! file_put_contents( $export_folder . '/groups.yaml', $group_content ) ) {
			throw new Exception( 'Could not save groups.yaml export file' );
		}
	} catch ( Exception $e ) {
		WP_CLI::error( $e->getMessage(), $exit = true );
	}
	WP_CLI::success( ( count( $roles ) + 1 ) . ' roles exported' );
}
