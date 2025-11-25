<?php
// phpcs:ignoreFile

/**
 * Mock WP_CLI\Utils namespace
 */
namespace WP_CLI\Utils {

	if ( ! class_exists( 'WP_CLI\\Utils\\ProgressBar' ) ) {

		class ProgressBar {
			public function __construct( $message, $count, $interval = 100 ) {}
			public function tick() {}
			public function finish() {}
		}

		function make_progress_bar( $message, $count, $interval = 100 ) {
			return new ProgressBar( $message, $count, $interval );
		}
	}
}
