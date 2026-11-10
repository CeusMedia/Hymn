<?php

/**
 *	Integration tests for all commands, run against built hymn.phar in a temporary app:
 *	no command may crash or wait for input forever, if arguments or input are missing.
 */
class Hymn_IntegrationTest_CommandsTest extends Hymn_IntegrationTest_AppCase
{
	/** @var list<string> Commands not to run: would replace installed hymn binary */
	protected const SKIPPED_COMMANDS	= ['self-update'];


	public function testNoCommandCrashesOrHangsWithoutArguments(): void
	{
		$failures	= [];
		foreach( $this->getCommands() as $command ){
			$this->createApp();
			$result	= $this->runInApp( $command, timeout: 15 );
			$output	= implode( PHP_EOL, $result->getLines() );
			if( 124 === $result->getCode() )
				$failures[]	= $command.': waits forever';
			else if( 255 === $result->getCode() || 1 === preg_match( '/Fatal error|TypeError|ArgumentCountError/', $output ) )
				$failures[]	= $command.': crashed with exit code '.$result->getCode().PHP_EOL.'  '.$result->getFirstLine();
		}
		self::assertSame( [], $failures, implode( PHP_EOL, $failures ) );
	}

	public function testInitWithPipedAnswers(): void
	{
		$this->createApp();
		unlink( $this->app.'.hymn' );
		$answers	= array_merge(
			array_fill( 0, 10, '' ),								//  application settings: take defaults
			[
				'no',												//  configure database?
				'n',												//  configure PHPUnit?
				'nein',												//  configure composer?
				'',													//  create base config file? default: yes
				'1.2',												//  - version
				'en',												//  - language
				'N',												//  create make file?
				'yes',												//  create Git ignore file?
			]
		);
		$result	= $this->runInApp( 'init', input: implode( PHP_EOL, $answers ).PHP_EOL );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );

		$hymn	= json_decode( (string) file_get_contents( $this->app.'.hymn' ) );
		self::assertSame( 'My Project', $hymn->application->title );
		self::assertSame( 'link', $hymn->application->installType );
		self::assertObjectNotHasProperty( 'database', $hymn );
		self::assertFileExists( $this->app.'config/config.ini' );
		self::assertStringContainsString( '1.2', (string) file_get_contents( $this->app.'config/config.ini' ) );
		self::assertFileExists( $this->app.'.gitignore' );
		self::assertFileDoesNotExist( $this->app.'Makefile' );
		self::assertFileDoesNotExist( $this->app.'phpunit.xml' );
		self::assertFileDoesNotExist( $this->app.'composer.json' );
	}

	public function testMissingInputEndsWithError(): void
	{
		$this->createAppWithIncompleteDatabase();
		$result	= $this->runInApp( 'database-test', timeout: 15 );
		self::assertNotSame( 124, $result->getCode(), 'Command must not wait for input forever' );
		self::assertNotSame( 0, $result->getCode() );
		self::assertStringContainsString( 'end of input', $this->describe( $result ) );
	}

	public function testNonInteractiveModeDoesNotAsk(): void
	{
		$this->createAppWithIncompleteDatabase();
		$result	= $this->runInApp( 'database-test --interactive=no', input: 'mydb'.PHP_EOL, timeout: 15 );
		self::assertNotSame( 0, $result->getCode() );
		self::assertStringContainsString( 'interaction is disabled', $this->describe( $result ) );
	}

	public function testDatabaseNotConfigured(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'database-test', timeout: 15 );
		self::assertSame( Hymn_Client::EXIT_ON_SETUP, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Database access needed but not configured', $this->describe( $result ) );
	}

	public function testUnreachableDatabaseIsReported(): void
	{
		$this->createApp();
		$this->changeHymnFile( static function( array $data ): array {
			$data['database']	= ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '1', 'name' => 'test', 'username' => 'test', 'password' => 'test', 'prefix' => ''];
			return $data;
		} );
		foreach( ['database-test', 'database-dump'] as $command ){
			$result	= $this->runInApp( $command, timeout: 15 );
			self::assertSame( Hymn_Client::EXIT_ON_SETUP, $result->getCode(), $command.': '.$this->describe( $result ) );
			self::assertStringContainsString( 'Database can NOT be connected: SQLSTATE', $this->describe( $result ) );
		}
	}

	public function testInvalidOptionIsReported(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'app-info --db=maybe' );
		self::assertSame( Hymn_Client::EXIT_ON_SETUP, $result->getCode() );
		self::assertStringContainsString( 'Invalid value "maybe" for option "db"', $this->describe( $result ) );
	}

	public function testSubCommandsAreRun(): void
	{
		$this->createApp();
		$result	= $this->runInApp( 'app-info -v' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Test_Plain', $this->describe( $result ), 'Output of sub command modules-available expected' );
	}

	/**
	 *	@return		list<string>
	 */
	protected function getCommands(): array
	{
		$helpText	= (string) file_get_contents( __DIR__.'/../../src/locales/en/command/help.txt' );
		preg_match_all( '/^- ([a-z][a-z-]*)/m', $helpText, $matches );
		return array_values( array_diff( array_unique( $matches[1] ), self::SKIPPED_COMMANDS ) );
	}

	/**
	 *	Creates app with database driver, but without database name, which needs to be asked for.
	 */
	protected function createAppWithIncompleteDatabase(): void
	{
		$this->createApp();
		$this->changeHymnFile( static function( array $data ): array {
			$data['database']	= ['driver' => 'mysql'];
			return $data;
		} );
	}
}
