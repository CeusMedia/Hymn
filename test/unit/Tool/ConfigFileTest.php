<?php
class Hymn_Test_Tool_ConfigFileTest extends PHPUnit_Framework_TestCase
{
	protected string $file;

	protected function setUp(): void
	{
		$this->file	= sys_get_temp_dir().'/hymn-test-'.uniqid().'.hymn';
	}

	protected function tearDown(): void
	{
		@unlink( $this->file );
	}

	public function testRead(): void
	{
		$config	= $this->readJson( [
			'application'	=> ['title' => 'My Project', 'url' => 'https://example.com/', 'uri' => '/var/www/app/', 'installMode' => 'dev', 'installType' => 'link'],
			'sources'		=> ['Public' => ['type' => 'folder', 'path' => '/src/', 'title' => 'Public modules']],
			'modules'		=> [
				'App_Site'			=> new stdClass(),
				'Resource_Database'	=> ['sourceId' => 'Public', 'config' => ['access.username' => 'user']],
			],
			'system'		=> ['user' => 'www-data'],
			'database'		=> ['driver' => 'mysql', 'host' => 'localhost', 'port' => '3306', 'name' => 'app', 'prefix' => 'app_'],
		] );

		self::assertSame( 'My Project', $config->application->title );
		self::assertSame( '/var/www/app/', $config->application->uri );
		self::assertSame( 'link', $config->application->installType );

		self::assertSame( ['Public'], array_keys( $config->sources ) );
		self::assertTrue( $config->sources['Public']->active );									//  default
		self::assertTrue( $config->sources['Public']->isDefault );								//  only source
		self::assertSame( '/src/', $config->sources['Public']->path );

		self::assertSame( ['App_Site', 'Resource_Database'], array_keys( $config->modules ) );
		self::assertNull( $config->modules['App_Site']->sourceId );
		self::assertSame( [], $config->modules['App_Site']->config );
		self::assertSame( 'Public', $config->modules['Resource_Database']->sourceId );
		self::assertSame( ['access.username' => 'user'], $config->modules['Resource_Database']->config );

		self::assertSame( 'www-data', $config->system->user );
		self::assertNull( $config->system->group );
		self::assertSame( 'mysql', $config->database->driver );
		self::assertSame( 'app_', $config->database->prefix );
		self::assertSame( '', $config->database->password );
	}

	public function testReadMinimal(): void
	{
		$config	= $this->readJson( ['application' => ['title' => 'Minimal']] );
		self::assertSame( '', $config->application->uri );
		self::assertSame( [], $config->sources );
		self::assertSame( [], $config->modules );
		self::assertSame( '', $config->database->driver );
	}

	public function testReadNumbersAndBooleansAsStrings(): void
	{
		$config	= $this->readJson( [
			'application'	=> ['title' => 2026],
			'sources'		=> ['A' => ['path' => '/a/', 'active' => 0], 'B' => ['path' => '/b/']],
			'database'		=> ['driver' => 'mysql', 'port' => 3306],
		] );
		self::assertSame( '2026', $config->application->title );
		self::assertSame( '3306', $config->database->port );
		self::assertFalse( $config->sources['A']->active );
		self::assertFalse( $config->sources['A']->isDefault );									//  no default flag with several sources
	}

	public function testReadMissingFile(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Please use command "init"' );
		Hymn_Tool_ConfigFile::read( $this->file );
	}

	public function testReadInvalidJson(): void
	{
		file_put_contents( $this->file, '{"application": ' );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'is not valid JSON' );
		Hymn_Tool_ConfigFile::read( $this->file );
	}

	public function testSaveAndReadAgain(): void
	{
		$config	= $this->readJson( [
			'application'	=> ['title' => 'My Project', 'uri' => '/var/www/app/', 'installType' => 'copy'],
			'sources'		=> ['Public' => ['type' => 'folder', 'path' => '/src/', 'active' => FALSE]],
			'modules'		=> ['App_Site' => new stdClass(), 'UI_Bootstrap' => ['config' => ['version' => '5']]],
			'database'		=> ['driver' => 'mysql', 'name' => 'app'],
		] );
		Hymn_Tool_ConfigFile::save( $config, $this->file );

		$json	= json_decode( (string) file_get_contents( $this->file ) );
		self::assertSame( 'My Project', $json->application->title );
		self::assertObjectNotHasProperty( 'url', $json->application );							//  empty values are not written
		self::assertEquals( new stdClass(), $json->modules->App_Site );						//  module without settings: {}
		self::assertObjectNotHasProperty( 'system', $json );									//  no user and group

		$again	= Hymn_Tool_ConfigFile::read( $this->file );
		self::assertSame( 'copy', $again->application->installType );
		self::assertFalse( $again->sources['Public']->active );
		self::assertSame( ['version' => '5'], $again->modules['UI_Bootstrap']->config );
		self::assertSame( 'app', $again->database->name );
	}

	protected function readJson( array $data ): Hymn_Structure_Config
	{
		file_put_contents( $this->file, json_encode( $data ) );
		return Hymn_Tool_ConfigFile::read( $this->file );
	}
}
