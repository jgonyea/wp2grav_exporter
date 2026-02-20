<?php
/**
 * Admin page template for WP2Grav Exporter.
 *
 * Expected variables provided by wp2grav_admin_page_callback():
 *   $export_dir   string  Absolute path to today's export directory.
 *
 * @package wp2grav
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1>WP2Grav Exporter</h1>
	<p>Export your WordPress content for use in a GravCMS instance.</p>

	<div class="card" style="max-width: 800px;">
		<h2>Export Directory</h2>
		<p><code><?php echo esc_html( $export_dir ); ?></code></p>
		<?php
		$exports_base = dirname( rtrim( $export_dir, '/' ) );
		if ( is_dir( $exports_base ) ) {
			$siblings = glob( $exports_base . '/user-*', GLOB_ONLYDIR );
			if ( ! empty( $siblings ) ) {
				rsort( $siblings );
				echo '<p><strong>Previous exports:</strong></p><ul>';
				foreach ( $siblings as $sibling ) {
					$label = basename( $sibling );
					echo '<li><code>' . esc_html( $label ) . '</code> <button class="button button-small wp2grav-delete-btn" data-folder="' . esc_attr( $label ) . '">Delete</button></li>';
				}
				echo '</ul>';
			}
		}
		?>
	</div>

	<div class="card" style="max-width: 800px;">
		<h2>Individual Plugin Exports</h2>
		<p>Run each exporter individually:</p>
		<p>
			<button class="button button-secondary wp2grav-export-btn" data-exporter="posts">Export Posts</button>
			<button class="button button-secondary wp2grav-export-btn" data-exporter="post_types">Export Post Types</button>
			<button class="button button-secondary wp2grav-export-btn" data-exporter="users">Export Users</button>
			<button class="button button-secondary wp2grav-export-btn" data-exporter="roles">Export User Roles</button>
			<button class="button button-secondary wp2grav-export-btn" data-exporter="site">Export Site Configuration</button>
		</p>
	</div>

	<div class="card" style="max-width: 800px;">
		<h2>Export All</h2>
		<p>Run all exporters at once:</p>
		<p>
			<button class="button button-primary wp2grav-export-btn" data-exporter="all">Export All</button>
		</p>
	</div>

	<div class="card" style="max-width: 800px;">
		<h2>Results</h2>
		<div id="wp2grav-results">Ready to export.</div>
	</div>
</div>
