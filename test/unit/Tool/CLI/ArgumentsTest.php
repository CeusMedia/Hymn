<?php
class Hymn_Test_Tool_CLI_ArgumentsTest extends PHPUnit_Framework_TestCase
{
	protected array $options;

	protected function setUp(): void
	{
		$json			= (string) file_get_contents( __DIR__.'/../../../../src/baseArgumentOptions.json' );
		$this->options	= json_decode( $json, TRUE, 512, JSON_THROW_ON_ERROR );
	}

	public function testDefaults(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-info'], $this->options );
		self::assertSame( '.hymn', $arguments->getOption( 'file' ) );
		self::assertSame( 'yes', $arguments->getOption( 'db' ) );
		self::assertNull( $arguments->getOption( 'dry' ) );
		self::assertSame( ['app-info'], $arguments->getArguments() );
	}

	public function testFlagsAndArgumentsAreSeparated(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-install', '-d', 'UI_Bootstrap', '--verbose', 'Info_Pages', '-f'], $this->options );
		self::assertTrue( $arguments->getOption( 'dry' ) );
		self::assertTrue( $arguments->getOption( 'verbose' ) );
		self::assertTrue( $arguments->getOption( 'force' ) );
		self::assertNull( $arguments->getOption( 'quiet' ) );
		self::assertSame( ['app-install', 'UI_Bootstrap', 'Info_Pages'], $arguments->getArguments() );
		self::assertSame( 'UI_Bootstrap', $arguments->getArgument( 1 ) );
		self::assertNull( $arguments->getArgument( 5 ) );
	}

	public function testLongFlags(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-install', '--dry', '--force', '--quiet'], $this->options );
		self::assertTrue( $arguments->getOption( 'dry' ) );
		self::assertTrue( $arguments->getOption( 'force' ) );
		self::assertTrue( $arguments->getOption( 'quiet' ) );
	}

	public function testOptionsWithValues(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-install', '--file=other.hymn', '--db=only'], $this->options );
		self::assertSame( 'other.hymn', $arguments->getOption( 'file' ) );
		self::assertSame( 'only', $arguments->getOption( 'db' ) );
		self::assertTrue( $arguments->hasOption( 'file', TRUE ) );
		self::assertSame( ['app-install'], $arguments->getArguments() );
	}

	public function testInvalidOptionValue(): void
	{
		$this->expectException( RangeException::class );
		$this->expectExceptionMessage( 'Invalid value "maybe" for option "db"' );
		new Hymn_Tool_CLI_Arguments( ['app-install', '--db=maybe'], $this->options );
	}

	public function testVeryVerbose(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-info', '-vv'], $this->options );
		self::assertTrue( $arguments->getOption( 'very-verbose' ) );
	}

	public function testCommandSpecificOptions(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-clear', 'locks', '--age=60'], $this->options );
		$arguments->registerOption( 'age', '/^--age=(\S+)$/', '\\1', '-1' );
		$arguments->parse();
		self::assertSame( '60', $arguments->getOption( 'age' ) );
		self::assertSame( ['app-clear', 'locks'], $arguments->getArguments() );
	}

	public function testRegisterOptionsNeedsPatternAndResolve(): void
	{
		$this->expectException( RangeException::class );
		new Hymn_Tool_CLI_Arguments( [], ['broken' => ['default' => NULL]] );
	}

	public function testManipulateArguments(): void
	{
		$arguments	= new Hymn_Tool_CLI_Arguments( ['app-install', 'A', 'B'], $this->options );
		$arguments->removeArgument( 0 );
		self::assertSame( ['A', 'B'], array_values( $arguments->getArguments() ) );
		$arguments->setArgument( 1, 'C' );
		self::assertSame( 'C', $arguments->getArgument( 1 ) );
	}
}
