<?php
/**
 * Class WP2GravUsersTest
 *
 * @package wp2grav
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for `wp2grav-roles` command.
 */
class WP2GravUsersTest extends TestCase {

	/**
	 * Primary export directory for the test site's Grav artifacts.
	 *
	 * @var string
	 */
	private $export_dir;

	/**
	 * Pre-configures the test environment before any tests are run.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		// Find wp2grav_exporter plugin path.
		$test_plugin_dir = explode( '/', plugin_dir_path( __FILE__ ) );
		$needle          = array_search( 'wp2grav_exporter', $test_plugin_dir, true );
		$test_plugin_dir = array_slice( $test_plugin_dir, 0, $needle + 1 );
		$test_plugin_dir = implode( '/', $test_plugin_dir );

		include_once $test_plugin_dir . '/plugins/wp2grav-users.php';

		$new_users = self::generateNewUserData();
		self::insertNewUsers( $new_users );

		// Run user exporter.
		wp2grav_export_users();
	}

	/**
	 * Insert new users to test site.
	 *
	 * @param array $new_users Test users.
	 * @return void
	 */
	private static function insertNewUsers( array $new_users ): void {
		foreach ( $new_users as $user ) {
			$user_id = wp_insert_user(
				$user
			);
		}
	}

	/**
	 * Generates test users.
	 *
	 * @return array Predefined users.
	 */
	private static function generateNewUserData() {
		$user_data = array(
			'SubbyMcSubFace'   => array(
				'user_login'           => 'subbymcsubface',
				'user_nicename'        => 'Subby McSubFace',
				'nickname'             => 'Subby McSubFaced',
				'user_email'           => 'subbymcsubface@example.com',
				'user_pass'            => wp_generate_password(),
				'first_name'           => 'Subby',
				'last_name'            => 'McSubFace',
				'display_name'         => 'Subby S',
				'user_url'             => 'https://example.com',
				'description'          => 'A couple words about Subby here.',
				'rich_editing'         => 'true',
				'syntax_highlighting'  => 'true',
				'comment_shortcuts'    => 'false',
				'admin_color'          => 'fresh',
				'use_ssl'              => false,
				'user_registered'      => gmdate( 'Y-m-d HH:MM:SS' ),
				'show_admin_bar_front' => 'true',
				'role'                 => 'subscriber',
				'locale'               => '',
			),
			'ContribUnder'     => array(
				'user_login'           => 'contribunder',
				'user_nicename'        => 'Contrib Under',
				'nickname'             => 'Contrib Under',
				'user_email'           => 'contribunder@example.com',
				'user_pass'            => wp_generate_password(),
				'first_name'           => 'Contrib',
				'last_name'            => 'Under',
				'display_name'         => 'Contrib U',
				'user_url'             => 'https://example.com',
				'description'          => 'A couple words about Contrib here.',
				'rich_editing'         => 'true',
				'syntax_highlighting'  => 'true',
				'comment_shortcuts'    => 'false',
				'admin_color'          => 'fresh',
				'use_ssl'              => false,
				'user_registered'      => gmdate( 'Y-m-d HH:MM:SS' ),
				'show_admin_bar_front' => 'true',
				'role'                 => 'contributor',
				'locale'               => '',
			),
			'AuthorConanDoyle' => array(
				'user_login'           => 'authordoyle',
				'user_nicename'        => 'Author Conan Doyle',
				'nickname'             => 'Author C',
				'user_email'           => 'authordoyle@example.com',
				'user_pass'            => wp_generate_password(),
				'first_name'           => 'Author',
				'last_name'            => 'Doyle',
				'display_name'         => 'Author C D',
				'user_url'             => 'https://example.com',
				'description'          => 'A couple words about Author here.',
				'rich_editing'         => 'true',
				'syntax_highlighting'  => 'true',
				'comment_shortcuts'    => 'false',
				'admin_color'          => 'fresh',
				'use_ssl'              => false,
				'user_registered'      => gmdate( 'Y-m-d HH:MM:SS' ),
				'show_admin_bar_front' => 'true',
				'role'                 => 'author',
				'locale'               => '',
			),
			'MarcusEditarious' => array(
				'user_login'           => 'marcuseditarious',
				'user_nicename'        => 'Marcus Editarious',
				'nickname'             => 'Marcus E',
				'user_email'           => 'marcuseditariousn@example.com',
				'user_pass'            => wp_generate_password(),
				'first_name'           => 'Marcus',
				'last_name'            => 'Editarious',
				'display_name'         => 'Marcus E',
				'user_url'             => 'https://example.com',
				'description'          => 'A couple words about Editarious here.',
				'rich_editing'         => 'true',
				'syntax_highlighting'  => 'true',
				'comment_shortcuts'    => 'false',
				'admin_color'          => 'fresh',
				'use_ssl'              => false,
				'user_registered'      => gmdate( 'Y-m-d HH:MM:SS' ),
				'show_admin_bar_front' => 'true',
				'role'                 => 'editor',
				'locale'               => '',
			),
		);

		return $user_data;
	}

	/**
	 * Cleanup of generated account files from test site.
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void {
		global $wp_filesystem;
		$accounts_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/accounts';
		$accounts     = glob( $accounts_dir . '/*.yaml' );
		foreach ( $accounts as $account ) {
			if ( is_file( $account ) ) {
				$wp_filesystem->delete( $account );
			}
		}

		$wp_filesystem->delete( $accounts_dir );
	}

	/**
	 * Set up the test environment before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->export_dir = WP_CONTENT_DIR . '/uploads/wp2grav-exports/user-' . gmdate( 'Ymd' ) . '/';
	}

	/**
	 * Validates admin.yaml user account.
	 *
	 * @return void
	 */
	public function testValidateAdminUserYaml(): void {
		$admin_yaml    = $this->export_dir . 'accounts/admin.yaml';
		$admin_account = YAML::parseFile( $admin_yaml );

		$this->assertFileExists(
			$admin_yaml,
			'Missing account YAML'
		);

		// Name.
		$this->assertSame(
			'admin',
			$admin_account['fullname'],
			'Wrong name for account'
		);

		// Email.
		$this->assertArrayHasKey(
			'email',
			$admin_account,
			'Missing Email Account'
		);

		// Password generated.
		$this->assertArrayHasKey(
			'password',
			$admin_account,
			'Missing generated password'
		);

		// Email.
		$this->assertSame(
			'admin@example.org',
			$admin_account['email'],
			'Wrong Email for account'
		);

		// Groups membership.
		$this->assertContains( 'wp_administrator', $admin_account['groups'], 'Missing administrator group' );
		$this->assertContains( 'wp_authenticated_user', $admin_account['groups'], 'Missing authenticated group' );
	}

	/**
	 * Validates user account with author role.
	 *
	 * @return void
	 */
	public function testValidateAuthorUserYaml(): void {
		$account      = $this->export_dir . 'accounts/authordoyle.yaml';
		$account_yaml = YAML::parseFile( $account );

		$this->assertFileExists(
			$account,
			'Missing account YAML'
		);

		// Verify Grav account fullname.
		$this->assertSame(
			'Author C',
			$account_yaml['fullname'],
			'Wrong name for account'
		);

		// Email.
		$this->assertArrayHasKey(
			'email',
			$account_yaml,
			'Missing Email Account'
		);

		// Email.
		$this->assertSame(
			'authordoyle@example.com',
			$account_yaml['email'],
			'Wrong Email for account'
		);

		// Password generated.
		$this->assertArrayHasKey(
			'password',
			$account_yaml,
			'Missing generated password'
		);

		// Groups membership.
		$this->assertContains( 'wp_authenticated_user', $account_yaml['groups'], 'Missing authenticated group' );
		$this->assertContains( 'wp_author', $account_yaml['groups'], 'Missing author group' );
		$this->assertNotContains( 'wp_administrator', $account_yaml['groups'], 'Erroneously added to extra administrator group' );
	}

	/**
	 * Validates user account with contributor role.
	 *
	 * @return void
	 */
	public function testValidateContributerUserYaml(): void {
		$account      = $this->export_dir . 'accounts/contribunder.yaml';
		$account_yaml = YAML::parseFile( $account );

		$this->assertFileExists(
			$account,
			'Missing account YAML'
		);

		// Verify Grav account fullname.
		$this->assertSame(
			'Contrib Under',
			$account_yaml['fullname'],
			'Wrong name for account'
		);

		// Email.
		$this->assertArrayHasKey(
			'email',
			$account_yaml,
			'Missing Email Account'
		);

		// Email.
		$this->assertSame(
			'contribunder@example.com',
			$account_yaml['email'],
			'Wrong Email for account'
		);

		// Password generated.
		$this->assertArrayHasKey(
			'password',
			$account_yaml,
			'Missing generated password'
		);

		// Groups membership.
		$this->assertContains( 'wp_authenticated_user', $account_yaml['groups'], 'Missing authenticated group' );
		$this->assertContains( 'wp_contributor', $account_yaml['groups'], 'Missing subscriber group' );
		$this->assertNotContains( 'wp_administrator', $account_yaml['groups'], 'Erroneously added to extra administrator group' );
	}

	/**
	 * Validates user account with editor role.
	 *
	 * @return void
	 */
	public function testValidateEditorUserYaml(): void {
		$account      = $this->export_dir . 'accounts/marcuseditarious.yaml';
		$account_yaml = YAML::parseFile( $account );

		$this->assertFileExists(
			$account,
			'Missing account YAML'
		);

		// Verify Grav account fullname.
		$this->assertSame(
			'Marcus E',
			$account_yaml['fullname'],
			'Wrong name for account'
		);

		// Email.
		$this->assertArrayHasKey(
			'email',
			$account_yaml,
			'Missing Email Account'
		);

		// Email.
		$this->assertSame(
			'marcuseditariousn@example.com',
			$account_yaml['email'],
			'Wrong Email for account'
		);

		// Password generated.
		$this->assertArrayHasKey(
			'password',
			$account_yaml,
			'Missing generated password'
		);

		// Groups membership.
		$this->assertContains( 'wp_authenticated_user', $account_yaml['groups'], 'Missing authenticated group' );
		$this->assertContains( 'wp_editor', $account_yaml['groups'], 'Missing editor group' );
		$this->assertNotContains( 'wp_administrator', $account_yaml['groups'], 'Erroneously added to extra administrator group' );
	}

	/**
	 * Validates user account with subscriber role.
	 *
	 * @return void
	 */
	public function testValidateSubscriberUserYaml(): void {
		$account      = $this->export_dir . 'accounts/subbymcsubface.yaml';
		$account_yaml = YAML::parseFile( $account );

		$this->assertFileExists(
			$account,
			'Missing account YAML'
		);

		// Name.
		$this->assertSame(
			'Subby McSubFaced',
			$account_yaml['fullname'],
			'Wrong name for account'
		);

		// Email.
		$this->assertArrayHasKey(
			'email',
			$account_yaml,
			'Missing Email Account'
		);

		// Email.
		$this->assertSame(
			'subbymcsubface@example.com',
			$account_yaml['email'],
			'Wrong Email for account'
		);

		// Password generated.
		$this->assertArrayHasKey(
			'password',
			$account_yaml,
			'Missing generated password'
		);

		// Groups membership.
		$this->assertContains( 'wp_authenticated_user', $account_yaml['groups'], 'Missing authenticated group' );
		$this->assertContains( 'wp_subscriber', $account_yaml['groups'], 'Missing subscriber group' );
		$this->assertNotContains( 'wp_administrator', $account_yaml['groups'], 'Erroneously added to extra administrator group' );
	}
}
