<?php
class Hymn_Test_Tool_BaseConfigEditorTest extends PHPUnit_Framework_TestCase
{
	protected string $file;

	protected function setUp(): void
	{
		$this->file	= sys_get_temp_dir().'/hymn-test-config-'.uniqid().'.ini';
		file_put_contents( $this->file, implode( PHP_EOL, [
			'app.name		= "My Project"',
			'app.version		= "0.1"',
			';app.base.url		= "http://example.com/"',
			'',
			'system.cache.modules	= yes',
			'system.debug		= no',
			'system.option		= null',
			'path.locales		= "locales/"	; where locales are',
		] ) );
	}

	protected function tearDown(): void
	{
		@unlink( $this->file );
	}

	public function testRead(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		self::assertSame( 'My Project', $editor->getProperty( 'app.name' ) );
		self::assertSame( '0.1', $editor->getProperty( 'app.version' ) );
		self::assertTrue( $editor->getProperties()['system.cache.modules'] );
		self::assertFalse( $editor->getProperties()['system.debug'] );
		self::assertNull( $editor->getProperties()['system.option'] );
		self::assertSame( 'locales/', $editor->getProperty( 'path.locales' ) );
		self::assertSame( 'where locales are', $editor->getComment( 'path.locales' ) );
	}

	public function testDisabledProperties(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		self::assertTrue( $editor->hasProperty( 'app.base.url', FALSE ) );
		self::assertFalse( $editor->isActiveProperty( 'app.base.url' ) );
		self::assertTrue( $editor->isActiveProperty( 'app.name' ) );
		self::assertArrayNotHasKey( 'app.base.url', $editor->getProperties() );
		self::assertArrayHasKey( 'app.base.url', $editor->getProperties( FALSE ) );
	}

	public function testActivateAndDeactivate(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		$editor->activateProperty( 'app.base.url' );
		self::assertMatchesRegularExpression( '/^app\.base\.url\s+= "http:\/\/example\.com\/"$/m', (string) file_get_contents( $this->file ) );

		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		self::assertTrue( $editor->isActiveProperty( 'app.base.url' ) );
		$editor->deactivateProperty( 'app.name' );
		self::assertMatchesRegularExpression( '/^;app\.name\s+= "My Project"$/m', (string) file_get_contents( $this->file ) );
		self::assertFalse( ( new Hymn_Tool_BaseConfigEditor( $this->file ) )->isActiveProperty( 'app.name' ) );
	}

	public function testActivateActiveProperty(): void
	{
		$this->expectException( LogicException::class );
		( new Hymn_Tool_BaseConfigEditor( $this->file ) )->activateProperty( 'app.name' );
	}

	public function testActivateUnknownProperty(): void
	{
		$this->expectException( InvalidArgumentException::class );
		( new Hymn_Tool_BaseConfigEditor( $this->file ) )->activateProperty( 'not.existing' );
	}

	public function testSetProperty(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		$editor->setProperty( 'app.version', '0.2' );
		$editor->setProperty( 'system.debug', TRUE );
		$editor->setProperty( 'app.new', 'value with "quotes"' );

		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		self::assertSame( '0.2', $editor->getProperty( 'app.version' ) );
		self::assertTrue( $editor->getProperties()['system.debug'] );
		self::assertSame( 'value with "quotes"', $editor->getProperty( 'app.new' ) );
		self::assertSame( 'My Project', $editor->getProperty( 'app.name' ) );					//  others untouched
		self::assertSame( 'where locales are', $editor->getComment( 'path.locales' ) );		//  comments kept
		self::assertNull( $editor->getProperties()['system.option'] );						//  null kept
	}

	public function testAddDisabledProperty(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		$editor->addProperty( 'feature.x', 'on', 'not ready', FALSE );
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file );
		self::assertTrue( $editor->hasProperty( 'feature.x', FALSE ) );
		self::assertFalse( $editor->isActiveProperty( 'feature.x' ) );
		self::assertSame( 'not ready', $editor->getComment( 'feature.x' ) );
	}

	public function testReservedWordsDisabled(): void
	{
		$editor	= new Hymn_Tool_BaseConfigEditor( $this->file, FALSE );
		self::assertSame( 'yes', $editor->getProperties()['system.cache.modules'] );
		self::assertSame( 'null', $editor->getProperties()['system.option'] );
	}

	public function testCreatesMissingFile(): void
	{
		$file	= sys_get_temp_dir().'/hymn-test-config-'.uniqid().'.ini';
		$editor	= new Hymn_Tool_BaseConfigEditor( $file );
		self::assertFileExists( $file );
		self::assertSame( [], $editor->getProperties() );
		unlink( $file );
	}
}
