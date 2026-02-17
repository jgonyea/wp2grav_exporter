<?php
/**
 * WP-CLI custom command: Exports WP content in GravCMS format by enumerating all plugins found under the `plugins` directory.
 * Syntax: wp wp2grav-all
 *
 * @package wp2grav
 */

/**
 * Exports all wp2grav content by running each individual exporter found in the plugins directory.
 *
 * @throws Exception If any individual exporter fails.
 */
function wp2grav_export_all() {
	// Find all exporter plugins. Assumes filename uses the wp2grav-* convention.
	$plugin_dir     = plugin_dir_path( __FILE__ );
	$export_plugins = glob( $plugin_dir . 'wp2grav-*.php' );
	$completed      = array();

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::line( 'Exporting all available content' );

		$options = array(
			'return'     => true,
			'launch'     => false,
			'exit_error' => true,
		);
	}

	foreach ( $export_plugins as $exporter ) {
		$command = substr( $exporter, strlen( $plugin_dir ) );
		$command = substr( $command, 0, -4 );

		if ( 'wp2grav-all' !== $command ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::runcommand( $command, $options );
			} else {
				$command = convert_command( $command );
				call_user_func( $command );
			}
			$completed[] = substr( $command, 8 );
		}
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		$plugins_completed = WP_CLI::colorize( '%M' . implode( ', ', $completed ) . '%n' );
		WP_CLI::line();
		WP_CLI::line( WP_CLI::colorize( '%GSuccess:%n Completed ' . count( $completed ) . ' exporter plugins (' . $plugins_completed . ')' ) );
		WP_CLI::line( 'Exported content can be found at ' . get_export_dir() );
		WP_CLI::line( WP_CLI::colorize( '%CNote: After copying the exported content to Grav, you must navigate to the `wordpress-exporter-helper` plugin and run `composer install` %n' ) );
	}
}

/**
 * Converts a WP-CLI plugin name to the actual function call.
 *
 * @param string $command WP-CLI command name (e.g. 'wp2grav-roles').
 * @return string PHP function name for the exporter (e.g. 'wp2grav_export_roles').
 */
function convert_command( $command ) {
	$new_command      = 'wp2grav_export_';
	$exploded_command = explode( '-', $command );
	$exploded_command = array_slice( $exploded_command, 1 );
	$imploded_command = implode( '_', $exploded_command );
	return $new_command . $imploded_command;
}
