<?php

/**
 *	Tests of questions without interaction (--interactive=no).
 *	Answering by input is covered by integration tests (piped input).
 */
class Hymn_Test_Tool_CLI_QuestionTest extends PHPUnit_Framework_TestCase
{
	protected Hymn_Client $client;

	protected function setUp(): void
	{
		$this->client	= new Hymn_Client( ['--quiet'], FALSE );
		$this->client->flags	= Hymn_Client::FLAG_NO_INTERACTION;								//  options are turned into flags on run only
	}

	public function testDefaultIsTaken(): void
	{
		self::assertSame( 'abc', $this->ask( 'string', 'abc' )->ask() );
		self::assertSame( 42, $this->ask( 'int', '42' )->ask() );
		self::assertSame( 1.5, $this->ask( 'float', '1.5' )->ask() );
		self::assertSame( 'b', $this->ask( 'string', 'b', ['a', 'b'] )->ask() );
	}

	public function testBooleanDefaults(): void
	{
		self::assertTrue( $this->ask( 'boolean', 'yes' )->ask() );
		self::assertTrue( $this->ask( 'bool', 'y' )->ask() );
		self::assertFalse( $this->ask( 'boolean', 'no' )->ask() );
		self::assertFalse( $this->ask( 'boolean' )->ask() );									//  no default: no
	}

	public function testEmptyAnswerWithoutDefault(): void
	{
		self::assertSame( '', $this->ask( 'string' )->ask() );
	}

	public function testRequiredAnswerWithoutDefault(): void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'interaction is disabled' );
		$this->ask( 'string' )->setRequired()->ask();
	}

	public function testDefaultNotInOptions(): void
	{
		$this->expectException( RuntimeException::class );
		$this->ask( 'string', 'c', ['a', 'b'] )->ask();
	}

	protected function ask( string $type, ?string $default = NULL, array $options = [] ): Hymn_Tool_CLI_Question
	{
		return Hymn_Tool_CLI_Question::getInstance( $this->client, 'Question?', $type, $default, $options );
	}
}
