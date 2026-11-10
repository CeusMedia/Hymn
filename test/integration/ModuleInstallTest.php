<?php

/**
 *	Integration tests of command module-install, run against built hymn.phar.
 *	Uses fixture modules in test/fixtures/source/ and a temporary app with a faked Composer installation,
 *	so no network, database or module library is needed. Database is skipped by --db=no.
 *	Another PHAR file can be tested by environment variable HYMN_PHAR.
 */
class Hymn_IntegrationTest_ModuleInstallTest extends Hymn_IntegrationTest_Case
{
	protected string $app;
	protected string $source;

	protected function setUp(): void
	{
		$this->app		= sys_get_temp_dir().'/hymn-test-module-install-'.uniqid().'/';
		$this->source	= (string) realpath( __DIR__.'/../fixtures/source' ).'/';
		if( !file_exists( __DIR__.'/../../vendor/composer/InstalledVersions.php' ) )
			self::markTestSkipped( 'Composer dependencies are not installed' );
	}

	protected function tearDown(): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->app ) );
	}

	public function testInstallPlainModuleAsLink(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertFileExists( $this->app.'config/modules/Test_Plain.xml' );
		self::assertTrue( is_link( $this->app.'classes/Test/Plain.php' ) );
		self::assertSame( $this->source.'Test/Plain/classes/Test/Plain.php', readlink( $this->app.'classes/Test/Plain.php' ) );
	}

	public function testInstallPlainModuleAsCopy(): void
	{
		$this->createApp( installType: 'copy' );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertFalse( is_link( $this->app.'classes/Test/Plain.php' ) );
		self::assertFileEquals( $this->source.'Test/Plain/classes/Test/Plain.php', $this->app.'classes/Test/Plain.php' );
	}

	public function testDryRunChangesNothing(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no --dry Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertFileDoesNotExist( $this->app.'config/modules/Test_Plain.xml' );
		self::assertFileDoesNotExist( $this->app.'classes/Test/Plain.php' );
	}

	public function testInstalledModuleIsRefused(): void
	{
		$this->createApp();
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( Hymn_Client::EXIT_ON_INPUT, $result->getCode() );
		self::assertStringContainsString( 'already installed', $this->describe( $result ) );
	}

	public function testUnknownModuleIsRefused(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no Test_Unknown' );
		self::assertSame( Hymn_Client::EXIT_ON_INPUT, $result->getCode() );
		self::assertStringContainsString( 'not found', $this->describe( $result ) );
	}

	public function testMissingPackageStopsInstallation(): void
	{
		$this->createApp( packages: [] );
		$result	= $this->runInApp( 'module-install --db=no Test_Package' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode() );
		self::assertStringContainsString( 'Package "vendor/lib" needs to be installed.', $this->describe( $result ) );
		self::assertFileDoesNotExist( $this->app.'config/modules/Test_Package.xml' );
	}

	public function testPackageInWrongVersionStopsInstallation(): void
	{
		$this->createApp( packages: ['vendor/lib' => '1.9.0'] );
		$result	= $this->runInApp( 'module-install --db=no Test_Package' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode() );
		self::assertStringContainsString( 'needs to be installed in version ^2.0 (installed: 1.9.0)', $this->describe( $result ) );
		self::assertFileDoesNotExist( $this->app.'config/modules/Test_Package.xml' );
	}

	public function testPackageInMatchingVersion(): void
	{
		$this->createApp( packages: ['vendor/lib' => '2.4.1'] );
		$result	= $this->runInApp( 'module-install --db=no Test_Package' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertFileExists( $this->app.'config/modules/Test_Package.xml' );
	}

	public function testPackageInDevelopmentVersionIsNotChecked(): void
	{
		$this->createApp( packages: ['vendor/lib' => 'dev-master'] );
		$result	= $this->runInApp( 'module-install --db=no Test_Package' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'is unstable', $this->describe( $result ) );
	}

	public function testPackageWithConstraintAppendedToName(): void
	{
		$this->createApp( packages: ['vendor/lib' => '2.0.0'] );
		$result	= $this->runInApp( 'module-install --db=no Test_PackageAppended' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );

		$this->createApp( packages: ['vendor/lib' => '3.0.0'] );
		$result	= $this->runInApp( 'module-install --db=no Test_PackageAppended' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode() );
		self::assertStringContainsString( 'Package "vendor/lib" needs to be installed in version ^2.0', $this->describe( $result ) );
	}

	public function testAllPackagesAreChecked(): void
	{
		$this->createApp( packages: ['vendor/first' => '1.0.0', 'vendor/second' => '2.0.0'] );
		$result	= $this->runInApp( 'module-install --db=no Test_TwoPackages' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode(), 'Second package must be checked, too' );
		self::assertStringContainsString( 'Package "vendor/second"', $this->describe( $result ) );
	}

	public function testComposerOfAppIsFoundByApplicationUri(): void
	{
		$this->createApp( packages: ['vendor/lib' => '1.9.0'] );
		mkdir( $this->app.'public' );
		$result	= $this->runInApp( 'module-install --db=no --file=../.hymn Test_Package', 'public/' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode(), 'Package check must work from subfolder' );
	}

	public function testComposerIsFoundInWorkingDirectoryWithoutApplicationUri(): void
	{
		$this->createApp( uri: '', packages: ['vendor/lib' => '1.9.0'] );
		$result	= $this->runInApp( 'module-install --db=no Test_Package' );
		self::assertSame( Hymn_Client::EXIT_ON_EXEC, $result->getCode(), $this->describe( $result ) );
	}

	public function testAppWithoutComposerSkipsPackageCheck(): void
	{
		$this->createApp( packages: NULL );
		$result	= $this->runInApp( 'module-install --db=no -v Test_Package' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Composer not installed in application', $this->describe( $result ) );
	}

	//  --  PROTECTED  --  //

	/**
	 *	Creates app folder with Hymn file and faked Composer installation.
	 *	@param		string|NULL					$uri			Application URI, NULL for app folder, empty for none
	 *	@param		array<string,string>|NULL	$packages		Installed packages (name => version), NULL for no Composer
	 */
	protected function createApp( ?string $uri = NULL, string $installType = 'link', ?array $packages = [] ): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->app ) );
		mkdir( $this->app.'config', 0775, TRUE );
		file_put_contents( $this->app.'.hymn', json_encode( [
			'application'	=> [
				'title'			=> 'Test App',
				'url'			=> 'http://localhost/',
				'uri'			=> $uri ?? $this->app,
				'installMode'	=> 'dev',
				'installType'	=> $installType,
			],
			'sources'		=> ['Fixtures' => ['active' => TRUE, 'type' => 'folder', 'path' => $this->source]],
			'modules'		=> new stdClass(),
		], JSON_PRETTY_PRINT ) );

		if( NULL === $packages )
			return;
		mkdir( $this->app.'vendor/composer', 0775, TRUE );
		copy( __DIR__.'/../../vendor/composer/InstalledVersions.php', $this->app.'vendor/composer/InstalledVersions.php' );
		$versions	= [];
		foreach( $packages as $name => $version )
			$versions[$name]	= ['pretty_version' => $version, 'version' => $version, 'reference' => NULL, 'type' => 'library', 'install_path' => $this->app.'vendor/'.$name, 'aliases' => [], 'dev_requirement' => FALSE];
		$installed	= [
			'root'		=> ['name' => 'test/app', 'pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'reference' => NULL, 'type' => 'project', 'install_path' => $this->app, 'aliases' => [], 'dev' => FALSE],
			'versions'	=> $versions,
		];
		file_put_contents( $this->app.'vendor/composer/installed.php', '<?php return '.var_export( $installed, TRUE ).';' );
	}

	/**
	 *	Runs hymn.phar within app folder (or a subfolder of it), with empty input for questions.
	 */
	protected function runInApp( string $arguments, string $subfolder = '' ): Hymn_IntegrationTest_HymnResponse
	{
		$phar		= getenv( 'HYMN_PHAR' ) ?: realpath( __DIR__.'/../../hymn.phar' );				//  other PHAR file by environment
		$command	= 'cd '.escapeshellarg( $this->app.$subfolder ).' && '.escapeshellarg( (string) $phar ).' '.$arguments.' < /dev/null 2>&1';
		exec( $command, $output, $code );
		return new Hymn_IntegrationTest_HymnResponse( $code, $output );
	}

	protected function describe( Hymn_IntegrationTest_HymnResponse $result ): string
	{
		return 'Exit code '.$result->getCode().', output:'.PHP_EOL.implode( PHP_EOL, $result->getLines() );
	}
}
