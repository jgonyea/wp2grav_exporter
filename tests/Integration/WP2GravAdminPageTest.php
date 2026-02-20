<?php
/**
 * Class WP2GravAdminPageTest
 *
 * @package wp2grav
 */

/**
 * Integration tests for the WP2Grav admin page and its AJAX handlers.
 *
 * Covers:
 *  - Admin menu hook registration
 *  - Page callback HTML output
 *  - wp2grav_ajax_run_export: nonce, capability, invalid/valid exporter names
 *  - wp2grav_ajax_delete_export: nonce, capability, folder-pattern validation,
 *    missing directory, successful deletion
 */
class WP2GravAdminPageTest extends WP_Ajax_UnitTestCase {

	/**
	 * ID of an administrator user created for each test.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Creates an administrator user and sets them as the current user.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->admin_user_id = WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Logs out and removes any export directories created during the test.
	 */
	protected function tearDown(): void {
		wp_set_current_user( 0 );
		$_POST = array();

		$exports_base = WP_CONTENT_DIR . '/uploads/wp2grav-exports/';
		if ( is_dir( $exports_base ) ) {
			$dirs = glob( $exports_base . 'user-*', GLOB_ONLYDIR );
			foreach ( $dirs as $dir ) {
				wp2grav_rmdir_recursive( $dir );
			}
		}

		parent::tearDown();
	}

	// =========================================================================
	// Admin page rendering
	// =========================================================================

	/**
	 * The admin_menu action hook must be registered at priority 10.
	 */
	public function testAdminMenuHookIsRegistered(): void {
		$this->assertSame( 10, has_action( 'admin_menu', 'wp2grav_admin_menu' ) );
	}

	/**
	 * Page callback must output the heading, all six exporter buttons, and the results div.
	 */
	public function testAdminPageOutputContainsExpectedElements(): void {
		ob_start();
		wp2grav_admin_page_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'WP2Grav Exporter', $output );
		$this->assertStringContainsString( 'id="wp2grav-results"', $output );

		foreach ( array( 'posts', 'post_types', 'users', 'roles', 'site', 'all' ) as $exporter ) {
			$this->assertStringContainsString(
				'data-exporter="' . $exporter . '"',
				$output,
				"Missing export button for exporter: $exporter"
			);
		}
	}

	/**
	 * Page callback must display the current export directory path.
	 */
	public function testAdminPageShowsExportDirectory(): void {
		ob_start();
		wp2grav_admin_page_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( esc_html( get_export_dir() ), $output );
	}

	/**
	 * Previous export directories that match the user-YYYYMMDD pattern must be
	 * listed with a labelled delete button.
	 */
	public function testAdminPageListsPreviousExports(): void {
		$exports_base = WP_CONTENT_DIR . '/uploads/wp2grav-exports/';
		$fake_dir     = $exports_base . 'user-20000101';
		wp_mkdir_p( $fake_dir );

		ob_start();
		wp2grav_admin_page_callback();
		$output = ob_get_clean();

		// Remove before asserting so tearDown does not also try to clean it.
		WP_Filesystem();
		$GLOBALS['wp_filesystem']->rmdir( $fake_dir );

		$this->assertStringContainsString( 'user-20000101', $output );
		$this->assertStringContainsString( 'wp2grav-delete-btn', $output );
		$this->assertStringContainsString( 'data-folder="user-20000101"', $output );
	}

	// =========================================================================
	// AJAX: wp2grav_run_export
	// =========================================================================

	/**
	 * A missing or invalid nonce must cause wp_die( -1 ).
	 */
	public function testRunExportFailsWithInvalidNonce(): void {
		$_POST['_wpnonce'] = 'bad_nonce';
		$_POST['exporter'] = 'roles';

		try {
			$this->_handleAjax( 'wp2grav_run_export' );
			$this->fail( 'Expected WPAjaxDiedException was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}
	}

	/**
	 * A user without manage_options must receive a Permission denied error.
	 */
	public function testRunExportFailsWithoutAdminCapability(): void {
		$subscriber = WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['exporter'] = 'roles';

		try {
			$this->_handleAjax( 'wp2grav_run_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.

		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Permission denied', $response['data']['message'] );
	}

	/**
	 * An unrecognised exporter name must return a JSON error.
	 */
	public function testRunExportFailsWithUnknownExporter(): void {
		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['exporter'] = 'nonexistent_exporter';

		try {
			$this->_handleAjax( 'wp2grav_run_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.

		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Invalid exporter', $response['data']['message'] );
	}

	/**
	 * Every valid exporter name must return a success response.
	 *
	 * @dataProvider validExporterProvider
	 * @param string $exporter Grav exporter plugin name.
	 */
	public function testRunExportSucceedsForValidExporters( string $exporter ): void {
		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['exporter'] = $exporter;

		try {
			$this->_handleAjax( 'wp2grav_run_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.

		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue(
			$response['success'],
			"Exporter '$exporter' should succeed. Response: " . $this->_last_response
		);
		$this->assertStringContainsString( 'completed successfully', $response['data']['message'] );
	}

	/**
	 * Data provider for Grav exporter names.
	 *
	 * @return array<string, array<string>>
	 */
	public static function validExporterProvider(): array {
		return array(
			'roles'      => array( 'roles' ),
			'users'      => array( 'users' ),
			'post_types' => array( 'post_types' ),
			'site'       => array( 'site' ),
			'posts'      => array( 'posts' ),
		);
	}

	// =========================================================================
	// AJAX: wp2grav_delete_export
	// =========================================================================

	/**
	 * A missing or invalid nonce must cause wp_die( -1 ).
	 */
	public function testDeleteExportFailsWithInvalidNonce(): void {
		$_POST['_wpnonce'] = 'bad_nonce';
		$_POST['folder']   = 'user-20200101';

		try {
			$this->_handleAjax( 'wp2grav_delete_export' );
			$this->fail( 'Expected WPAjaxDieStopException was not thrown.' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}
	}

	/**
	 * A user without manage_options must receive a Permission denied error.
	 */
	public function testDeleteExportFailsWithoutAdminCapability(): void {
		$subscriber = WP_UnitTestCase_Base::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['folder']   = 'user-20200101';

		try {
			$this->_handleAjax( 'wp2grav_delete_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.

		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Permission denied', $response['data']['message'] );
	}

	/**
	 * Folder names that do not match ^user-\d{8}$ must be rejected.
	 *
	 * @dataProvider invalidFolderProvider
	 * @param string $folder The folder name to test a delete action upon.
	 */
	public function testDeleteExportRejectsInvalidFolderPatterns( string $folder ): void {
		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['folder']   = $folder;

		try {
			$this->_handleAjax( 'wp2grav_delete_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Invalid folder name', $response['data']['message'] );
	}

	/**
	 * Data provider for invalid folder names.
	 *
	 * @return array<string, array<string>>
	 */
	public static function invalidFolderProvider(): array {
		return array(
			'path traversal'      => array( '../../../etc/passwd' ),
			'too few date digits' => array( 'user-2024010' ),
			'wrong prefix'        => array( 'admin-20240101' ),
			'trailing slash'      => array( 'user-20240101/' ),
			'empty string'        => array( '' ),
			'double dots'         => array( '../../' ),
		);
	}

	/**
	 * A valid folder pattern that maps to a non-existent directory must return
	 * a Directory not found error.
	 */
	public function testDeleteExportFailsWhenDirectoryMissing(): void {
		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['folder']   = 'user-19990101';

		try {
			$this->_handleAjax( 'wp2grav_delete_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Directory not found', $response['data']['message'] );
	}

	/**
	 * Deleting a real export directory must return success and remove the directory.
	 */
	public function testDeleteExportSucceedsWithExistingFolder(): void {
		$exports_base = WP_CONTENT_DIR . '/uploads/wp2grav-exports/';
		$folder       = 'user-20200101';
		$target       = $exports_base . $folder;
		wp_mkdir_p( $target );
		$this->assertDirectoryExists( $target );

		$_POST['_wpnonce'] = wp_create_nonce( 'wp2grav_export_nonce' );
		$_POST['folder']   = $folder;

		try {
			$this->_handleAjax( 'wp2grav_delete_export' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected since AJAX call was cancelled by the AJAX handler.
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertStringContainsString( 'Deleted export: ' . $folder, $response['data']['message'] );
		$this->assertDirectoryDoesNotExist( $target );
	}
}
