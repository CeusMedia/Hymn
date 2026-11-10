<?php

/**
 *	Integration tests of command module-update, run against built hymn.phar.
 *	Fixture source is copied into the app, so modules can be changed for updates.
 *	See Hymn_IntegrationTest_AppCase for creating apps and running hymn.phar.
 */
class Hymn_IntegrationTest_ModuleUpdateTest extends Hymn_IntegrationTest_AppCase
{
	public function testUpdateOutdatedModule(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$this->changeSourceModule( 'Test_Plain', '1.1.0' );

		$result	= $this->runInApp( 'module-update --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Updating module "Test_Plain" from 1.0.0 to 1.1.0', $this->describe( $result ) );
		self::assertSame( '1.1.0', $this->getInstalledVersion( 'Test_Plain' ) );
		self::assertTrue( is_link( $this->app.'classes/Test/Plain.php' ) );
	}

	public function testDryRunChangesNothing(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$this->changeSourceModule( 'Test_Plain', '1.1.0' );

		$result	= $this->runInApp( 'module-update --db=no --dry Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Plain' ) );
	}

	public function testModuleNotOutdatedIsRefused(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$result	= $this->runInApp( 'module-update --db=no Test_Plain' );
		self::assertSame( Hymn_Client::EXIT_ON_RUN, $result->getCode() );
		self::assertStringContainsString( 'is not outdated', $this->describe( $result ) );

		$result	= $this->runInApp( 'module-update --db=no --force Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
	}

	public function testModuleNotInstalledIsRefused(): void
	{
		$this->createApp( copySource: TRUE );
		$result	= $this->runInApp( 'module-update --db=no Test_Plain' );
		self::assertSame( Hymn_Client::EXIT_ON_RUN, $result->getCode() );
		self::assertStringContainsString( 'is not installed', $this->describe( $result ) );
	}

	public function testModuleRemovedFromSourceIsReported(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Other' );
		exec( 'rm -rf '.escapeshellarg( $this->source.'Test/Other' ) );

		foreach( ['module-update --db=no Test_Other', 'module-update --db=no --force Test_Other'] as $command ){
			$result	= $this->runInApp( $command );
			self::assertSame( Hymn_Client::EXIT_ON_RUN, $result->getCode(), $this->describe( $result ) );
			self::assertStringContainsString( 'is not available in source', $this->describe( $result ) );
		}
	}

	public function testUpdateInstallsNewlyNeededModule(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Other' );
		$this->changeSourceModule( 'Test_Other', '1.1.0', ['Test_Plain'] );

		$result	= $this->runInApp( 'module-update --db=no Test_Other' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Plain' ) );
		self::assertSame( '1.1.0', $this->getInstalledVersion( 'Test_Other' ) );
	}

	public function testUpdateUninstallsModuleNotNeededAnymore(): void
	{
		$this->createApp( copySource: TRUE );
		$this->runInApp( 'module-install --db=no Test_Plain' );
		$this->runInApp( 'module-install --db=no Test_WithNeeds' );
		$this->runInApp( 'module-install --db=no Test_Other' );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Plain' ) );
		$this->changeSourceModule( 'Test_WithNeeds', '1.1.0' );								//  does not need Test_Plain anymore

		$result	= $this->runInApp( 'module-update --db=no Test_WithNeeds' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( '1.1.0', $this->getInstalledVersion( 'Test_WithNeeds' ) );
		self::assertNull( $this->getInstalledVersion( 'Test_Plain' ), 'Module not needed anymore must be uninstalled' );
		self::assertSame( '1.0.0', $this->getInstalledVersion( 'Test_Other' ), 'Other module must be kept' );
	}
}
