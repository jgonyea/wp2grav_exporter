<?php
/**
 * WP-CLI custom command: Exports WP content in GravCMS format by enumerating all plugins found under the `plugins` directory.
 * Syntax: wp wp2grav-all
 */
function wp2grav_export_all() {
	WP_CLI::line( 'Exporting all available content' );

	$options = array(
		'return'     => true,   // Return 'STDOUT'; use 'all' for full object.
		'launch'     => false,  // Reuse the current process.
		'exit_error' => true,   // Halt script execution on error.
	);

	// Find all exporter plugins.  Assumes filename is using the wp2grav-* command.
	$plugin_dir        = plugin_dir_path( __FILE__ );
	$export_plugins    = glob( $plugin_dir . 'wp2grav-*' );
	$plugins_completed = '';

	foreach ( $export_plugins as $exporter ) {
		$command = substr( $exporter, strlen( $plugin_dir ) );
		// Drops '.php' extension.
		$command = substr( $command, 0, ( strlen( $command ) - 4 ) );

		if ( 'wp2grav-all' !== $command ) {
			WP_CLI::runcommand( $command, $options );
			$plugins_completed .= substr( $command, 8 ) . ', ';
		}
	}
	$plugins_completed = WP_CLI::colorize( '%M' . substr( $plugins_completed, 0, -2 ) . '%n' );

	WP_CLI::line();
	WP_CLI::line( WP_CLI::colorize( '%GSuccess:%n Completed ' . ( count( $export_plugins ) - 1 ) . ' exporter plugins (' . $plugins_completed . ')' ) );

	$export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
	WP_CLI::line( 'Exported content can be found at ' . $export_dir );
	WP_CLI::line( WP_CLI::colorize( '%CNote: After copying the exported content to Grav, you must navigate to the `wordpress-exporter-helper` plugin and run `composer install` %n' ) );
}
