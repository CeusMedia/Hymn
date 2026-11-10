<?php

/**
 *	Integration tests of command module-install, run against built hymn.phar.
 *	Uses fixture modules in test/fixtures/source/ and a temporary app with a faked Composer installation,
 *	so no network, database or module library is needed. Database is skipped by --db=no.
 *	See Hymn_IntegrationTest_AppCase for creating apps and running hymn.phar.
 */
class Hymn_IntegrationTest_ModuleInstallTest extends Hymn_IntegrationTest_AppCase
{

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

	public function testCombinedShortFlags(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install -dv --db=no Test_Plain' );					//  dry and verbose
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Checking related packages', $this->describe( $result ) );	//  verbose output
		self::assertFileDoesNotExist( $this->app.'config/modules/Test_Plain.xml' );			//  dry: nothing installed
	}

	public function testStylesAndImagesGoToThemes(): void
	{
		$this->createApp();
		file_put_contents( $this->app.'config/config.ini', 'layout.theme = "custom"'.PHP_EOL );
		$result	= $this->runInApp( 'module-install --db=no Test_Styled' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertFileExists( $this->app.'themes/common/css/test.css' );						//  style of source common
		self::assertFileExists( $this->app.'themes/custom/img/test.png' );					//  image of source theme: theme of app
	}

	public function testDryRunCreatesNoFolders(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no --dry Test_Styled' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertDirectoryDoesNotExist( $this->app.'themes' );

		$result	= $this->runInApp( 'module-install --db=no --dry Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertDirectoryDoesNotExist( $this->app.'classes' );
	}

	public function testFilePathOutsideOfAppIsRefused(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no Test_BadPath' );
		self::assertNotSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Invalid file path "../../escaped.txt" in module Test_BadPath', $this->describe( $result ) );
		self::assertFileDoesNotExist( dirname( $this->app, 2 ).'/escaped.txt' );
		self::assertNull( $this->getInstalledVersion( 'Test_BadPath' ) );
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

	public function testNeededModulesAreInstalledFirst(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no Test_Deep' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Plain' ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_WithNeeds' ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Deep' ) );

		$output	= $this->describe( $result );													//  order: deepest first
		$posPlain		= strpos( $output, "Installing module 'Test_Plain'" );
		$posWithNeeds	= strpos( $output, "Installing module 'Test_WithNeeds'" );
		$posDeep		= strpos( $output, "Installing module 'Test_Deep'" );
		self::assertNotFalse( $posPlain );
		self::assertTrue( $posPlain < $posWithNeeds && $posWithNeeds < $posDeep, $output );
	}

	public function testInstalledNeededModulesAreSkipped(): void
	{
		$this->createApp();
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$result	= $this->runInApp( 'module-install --db=no Test_WithNeeds' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringNotContainsString( "Installing module 'Test_Plain'", $this->describe( $result ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_WithNeeds' ) );
	}

	public function testMissingNeededModuleStopsInstallation(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'module-install --db=no Test_NeedsMissing' );
		self::assertNotSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Test_Missing', $this->describe( $result ) );
		self::assertNull( $this->getInstalledVersion( 'Test_NeedsMissing' ) );
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
}
