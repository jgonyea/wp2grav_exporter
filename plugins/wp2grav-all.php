<?php
/**
 * WP-CLI custom command: Exports WP content in GravCMS format.
 * Syntax: wp wp2grav-all
 */



function wp2grav_export_all( $args, $assoc_args ) {
	WP_CLI::line( 'Exporting all available content' );

	$options = array(
		'return'     => TRUE,   // Return 'STDOUT'; use 'all' for full object.
		'launch'     => FALSE,  // Reuse the current process.
		'exit_error' => TRUE,   // Halt script execution on error.
	);

	// Find all exporter plugins.  Assumes filename is using the wp2grav-* command.
	$plugin_dir     = plugin_dir_path( __FILE__ );
	$export_plugins = glob( $plugin_dir . 'wp2grav-*' );

	foreach ( $export_plugins as $exporter ) {
		$command = substr( $exporter, strlen( $plugin_dir ) );
		// Drops '.php' extension.
		$command = substr( $command, 0, ( strlen( $command ) - 4 ) );

		if ( $command != 'wp2grav-all' ) {
			WP_CLI::runcommand( $command, $options );
		}
	}
	WP_CLI::line( WP_CLI::colorize('%GSuccess:%n Completed exporting ' . ( count( $export_plugins ) - 1 ) . ' plugins' ));
}
