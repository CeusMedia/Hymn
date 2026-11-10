<?php

/**
 *	Integration tests of command app-clear, run against built hymn.phar.
 *	See Hymn_IntegrationTest_AppCase for creating apps and running hymn.phar.
 */
class Hymn_IntegrationTest_AppClearTest extends Hymn_IntegrationTest_AppCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->createApp();
		mkdir( $this->app.'logs' );
		mkdir( $this->app.'config/locks' );
		foreach( ['logs/app.log', 'logs/app.log.1', 'logs/app.log.2.gz', 'logs/.htaccess', 'logs/notes.txt', 'config/locks/job.lock', 'config/locks/readme'] as $file )
			touch( $this->app.$file );
		touch( $this->app.'logs/app.log.1', time() - 3 * 3600 );
		touch( $this->app.'config/locks/job.lock', time() - 3 * 3600 );
	}

	public function testClearLogs(): void
	{
		$result	= $this->runInApp( 'app-clear logs' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( ['.htaccess', 'notes.txt'], $this->listFiles( 'logs' ) );
		self::assertSame( ['job.lock', 'readme'], $this->listFiles( 'config/locks' ) );			//  locks untouched
	}

	public function testClearLocks(): void
	{
		$result	= $this->runInApp( 'app-clear locks' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( ['readme'], $this->listFiles( 'config/locks' ) );
		self::assertCount( 5, $this->listFiles( 'logs' ) );										//  logs untouched
	}

	public function testClearAll(): void
	{
		$result	= $this->runInApp( 'app-clear' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( ['.htaccess', 'notes.txt'], $this->listFiles( 'logs' ) );
		self::assertSame( ['readme'], $this->listFiles( 'config/locks' ) );
	}

	public function testAgeKeepsNewerFiles(): void
	{
		$result	= $this->runInApp( 'app-clear logs --age=60' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( ['.htaccess', 'app.log', 'app.log.2.gz', 'notes.txt'], $this->listFiles( 'logs' ) );
	}

	public function testDryRunRemovesNothing(): void
	{
		$result	= $this->runInApp( 'app-clear -dv' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertStringContainsString( 'Would remove: app.log', $this->describe( $result ) );
		self::assertCount( 5, $this->listFiles( 'logs' ) );
		self::assertCount( 2, $this->listFiles( 'config/locks' ) );
	}

	public function testUnknownTypeIsRefused(): void
	{
		$result	= $this->runInApp( 'app-clear logs foo' );
		self::assertSame( Hymn_Client::EXIT_ON_INPUT, $result->getCode() );
		self::assertCount( 5, $this->listFiles( 'logs' ) );
	}

	public function testRunInSubfolder(): void
	{
		mkdir( $this->app.'public' );
		$result	= $this->runInApp( 'app-clear logs --file=../.hymn', 'public/' );
		self::assertSame( 0, $result->getCode(), $this->describe( $result ) );
		self::assertSame( ['.htaccess', 'notes.txt'], $this->listFiles( 'logs' ) );
	}

	/**
	 *	@return		list<string>
	 */
	protected function listFiles( string $folder ): array
	{
		$files	= array_values( array_diff( scandir( $this->app.$folder ) ?: [], ['.', '..'] ) );
		sort( $files );
		return $files;
	}
}
