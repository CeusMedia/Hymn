<?php
class Hymn_Test_Module_Library_AvailableTest extends PHPUnit_Framework_TestCase
{
	protected Hymn_Module_Library_Available $library;
	protected string $source;

	protected function setUp(): void
	{
		$this->library	= new Hymn_Module_Library_Available( new Hymn_Client( ['--quiet'], FALSE ) );
		$this->source	= (string) realpath( __DIR__.'/../../../fixtures/source' ).'/';
	}

	public function testFirstSourceIsDefault(): void
	{
		$this->library->addSource( 'A', $this->source, 'folder' );
		$this->library->addSource( 'B', $this->source, 'folder' );
		self::assertSame( 'A', $this->library->getDefaultSource() );
		self::assertFalse( $this->library->getSource( 'B' )->isDefault );
	}

	public function testFirstActiveSourceIsDefault(): void
	{
		$this->library->addSource( 'Inactive', $this->source, 'folder', FALSE );
		$this->library->addSource( 'A', $this->source, 'folder' );
		$this->library->addSource( 'B', $this->source, 'folder' );
		self::assertSame( 'A', $this->library->getDefaultSource() );
		self::assertFalse( $this->library->getSource( 'B' )->isDefault );
	}

	public function testGetSourceKeepsSourceUntouched(): void
	{
		$this->library->addSource( 'A', $this->source, 'folder' );
		$this->library->getSource( 'A' );															//  without modules
		$source	= $this->library->getSource( 'A', TRUE );											//  with modules, afterwards
		self::assertArrayHasKey( 'Test_Plain', $source->modules );
		self::assertSame( [], $this->library->getSource( 'A' )->modules );
	}

	public function testGetSourcesWithModules(): void
	{
		$this->library->addSource( 'A', $this->source, 'folder' );
		$this->library->addSource( 'Inactive', $this->source, 'folder', FALSE );
		$sources	= $this->library->getActiveSources( TRUE );
		self::assertSame( ['A'], array_keys( $sources ) );
		self::assertArrayHasKey( 'Test_WithNeeds', $sources['A']->modules );
	}

	public function testAddSourceTwice(): void
	{
		$this->library->addSource( 'A', $this->source, 'folder' );
		$this->expectException( InvalidArgumentException::class );
		$this->library->addSource( 'A', $this->source, 'folder' );
	}
}
