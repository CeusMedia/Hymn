<?php

/**
 *	Integration tests of module settings in Hymn file and of saving Hymn file, run against built hymn.phar.
 *	See Hymn_IntegrationTest_AppCase for creating apps and running hymn.phar.
 */
class Hymn_IntegrationTest_HymnFileTest extends Hymn_IntegrationTest_AppCase
{
	public function testModuleIsInstalledFromSourceSetInHymnFile(): void
	{
		$this->createAppWithTwoSources( ['Test_Plain' => ['sourceId' => 'Second']] );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( "Installing module 'Test_Plain' (from Second)", $this->describe( $result ) );
		self::assertStringStartsWith( $this->app.'second/', (string) readlink( $this->app.'classes/Test/Plain.php' ) );
	}

	public function testModuleIsInstalledFromDefaultSourceWithoutSetting(): void
	{
		$this->createAppWithTwoSources( ['Test_Plain' => new stdClass()] );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( "Installing module 'Test_Plain' (from Fixtures)", $this->describe( $result ) );
	}

	public function testFirstActiveSourceIsDefault(): void
	{
		$this->createAppWithTwoSources( ['Test_Plain' => new stdClass()] );
		$this->changeHymnFile( static function( array $data ): array {
			$data['sources']['Fixtures']['active']	= FALSE;										//  first source inactive
			return $data;
		} );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( "Installing module 'Test_Plain' (from Second)", $this->describe( $result ) );
	}

	public function testAppInstallUsesSourceSetInHymnFile(): void
	{
		$this->createAppWithTwoSources( ['Test_Plain' => ['sourceId' => 'Second']] );
		$result	= $this->runInApp( 'app-install --db=no' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( "Installing module 'Test_Plain' (from Second)", $this->describe( $result ) );
	}

	public function testOlderKeySourceIsSupported(): void
	{
		$this->createAppWithTwoSources( ['Test_Plain' => ['source' => 'Second']] );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( '(from Second)', $this->describe( $result ) );
	}

	public function testInstallTypeOfModuleInHymnFile(): void
	{
		$this->createApp( installType: 'link' );
		$this->changeHymnFile( static function( array $data ): array {
			$data['modules']['Test_Plain']	= ['installType' => 'copy'];
			return $data;
		} );
		$result	= $this->runInApp( 'module-install --db=no Test_Plain' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'as copy', $this->describe( $result ) );
		self::assertFalse( is_link( $this->app.'classes/Test/Plain.php' ) );
	}

	public function testSavingKeepsWholeHymnFile(): void
	{
		$this->createApp();
		$this->changeHymnFile( static function( array $data ): array {
			$data['paths']		= ['images' => 'img/', 'custom' => 'custom/'];
			$data['modules']	= ['Test_Plain' => ['sourceId' => 'Fixtures', 'installType' => 'copy', 'config' => ['a' => '1']], '@note' => new stdClass()];
			$data['system']		= ['user' => 'www-data', 'group' => 'www-data'];
			$data['database']	= ['driver' => 'mysql', 'host' => 'localhost', 'name' => 'app', 'username' => 'user', 'password' => 'secret', 'prefix' => 'app_'];
			$data['custom']		= ['keep' => 'me'];
			return $data;
		} );
		$before	= json_decode( (string) file_get_contents( $this->app.'.hymn' ), TRUE );

		$result	= $this->runInApp( 'config-set application.title Changed' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringNotContainsString( 'Deprecated', $this->describe( $result ) );

		$after	= json_decode( (string) file_get_contents( $this->app.'.hymn' ), TRUE );
		self::assertSame( 'Changed', $after['application']['title'] );
		$before['application']['title']	= 'Changed';
		self::assertEquals( $before, $after );
	}

	/**
	 *	Creates app with sources "Fixtures" (default) and "Second", both having all fixture modules.
	 *	@param		array<string,mixed>		$modules		Module settings for Hymn file
	 */
	protected function createAppWithTwoSources( array $modules ): void
	{
		$this->createApp();
		exec( 'cp -R '.escapeshellarg( $this->source ).' '.escapeshellarg( $this->app.'second' ) );
		$second	= $this->app.'second/';
		$this->changeHymnFile( static function( array $data ) use ( $modules, $second ): array {
			$data['sources']['Second']	= ['active' => TRUE, 'type' => 'folder', 'path' => $second];
			$data['modules']			= $modules;
			return $data;
		} );
	}
}
