<?php
/**
 * No-op progress bar for non-CLI contexts.
 *
 * This is needed for when running the export from the admin UI,
 * where we don't have a progress bar.
 *
 * @package wp2grav
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * No-op progress bar for non-CLI contexts.
 */
class Wp2grav_Noop_Progress {
	/**
	 * No-op tick.
	 */
	public function tick() {}

	/**
	 * No-op finish.
	 */
	public function finish() {}
}
