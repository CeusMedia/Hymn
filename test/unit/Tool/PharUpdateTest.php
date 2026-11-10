<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_PharUpdateTest extends PHPUnit_Framework_TestCase
{
	protected string $path;
	protected Hymn_Tool_PharUpdate $updater;

	protected function setUp(): void
	{
		$this->path		= sys_get_temp_dir().'/hymn-test-update-'.uniqid().'/';
		mkdir( $this->path.'download/1.0.5', 0775, TRUE );
		mkdir( $this->path.'download/broken', 0775, TRUE );
		mkdir( $this->path.'bin' );
		file_put_contents( $this->path.'download/1.0.5/hymn.phar', "#!/usr/bin/env php\n<?php echo 'new'; __HALT_COMPILER(); ?>\nDATA" );
		file_put_contents( $this->path.'download/broken/hymn.phar', '<html>Not Found</html>' );
		file_put_contents( $this->path.'bin/hymn', 'OLD' );
		chmod( $this->path.'bin/hymn', 0750 );
		$this->updater	= new Hymn_Tool_PharUpdate( 'file://'.$this->path.'download/<VERSION>/hymn.phar' );
	}

	protected function tearDown(): void
	{
		exec( 'chmod -R u+w '.escapeshellarg( $this->path ).' && rm -rf '.escapeshellarg( $this->path ) );
	}

	public static function provideVersions(): array
	{
		return [
			'tag'				=> ['1.0.4', TRUE],
			'tag with suffix'	=> ['1.0.5c', TRUE],
			'branch'			=> ['1.0.x', TRUE],
			'master'			=> ['master', TRUE],
			'with slash'		=> ['feature/x', FALSE],
			'parent path'		=> ['1..0', FALSE],
			'leading dot'		=> ['.hidden', FALSE],
			'empty'				=> ['', FALSE],
			'shell characters'	=> ['1.0;rm', FALSE],
		];
	}

	#[DataProvider('provideVersions')]
	public function testIsValidVersion( string $version, bool $expected ): void
	{
		self::assertSame( $expected, Hymn_Tool_PharUpdate::isValidVersion( $version ) );
	}

	public function testGetUrl(): void
	{
		$updater	= new Hymn_Tool_PharUpdate();
		self::assertSame( 'https://github.com/CeusMedia/Hymn/raw/1.0.4/hymn.phar', $updater->getUrl( '1.0.4' ) );
		$this->expectException( InvalidArgumentException::class );
		$updater->getUrl( '../x' );
	}

	public function testUpdateReplacesFileKeepingPermissions(): void
	{
		$bytes	= $this->updater->update( '1.0.5', $this->path.'bin/hymn' );
		self::assertStringEqualsFile( $this->path.'bin/hymn', (string) file_get_contents( $this->path.'download/1.0.5/hymn.phar' ) );
		self::assertSame( filesize( $this->path.'bin/hymn' ), $bytes );
		self::assertSame( 0750, fileperms( $this->path.'bin/hymn' ) & 0777 );
		self::assertSame( ['hymn'], array_values( array_diff( scandir( $this->path.'bin' ) ?: [], ['.', '..'] ) ) );	//  no temporary files left
	}

	public function testFailedDownloadKeepsInstalledFile(): void
	{
		try{
			$this->updater->update( '9.9.9', $this->path.'bin/hymn' );
			self::fail( 'Exception expected' );
		}
		catch( RuntimeException $e ){
			self::assertStringContainsString( 'Download from', $e->getMessage() );
		}
		self::assertStringEqualsFile( $this->path.'bin/hymn', 'OLD' );
		self::assertSame( ['hymn'], array_values( array_diff( scandir( $this->path.'bin' ) ?: [], ['.', '..'] ) ) );
	}

	public function testDownloadWhichIsNoPharKeepsInstalledFile(): void
	{
		try{
			$this->updater->update( 'broken', $this->path.'bin/hymn' );
			self::fail( 'Exception expected' );
		}
		catch( RuntimeException $e ){
			self::assertStringContainsString( 'is not a PHAR file', $e->getMessage() );
		}
		self::assertStringEqualsFile( $this->path.'bin/hymn', 'OLD' );
	}

	public function testReadOnlyTargetIsRefused(): void
	{
		if( 0 === posix_geteuid() )
			self::markTestSkipped( 'Permissions are not checked for root' );
		chmod( $this->path.'bin', 0555 );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Permission denied' );
		$this->updater->update( '1.0.5', $this->path.'bin/hymn' );
	}

	public function testUpdateFollowsSymlink(): void
	{
		symlink( $this->path.'bin/hymn', $this->path.'bin/hymn-link' );
		$target	= (string) realpath( $this->path.'bin/hymn-link' );
		$this->updater->update( '1.0.5', $target );
		self::assertTrue( is_link( $this->path.'bin/hymn-link' ) );								//  link is kept
		self::assertStringContainsString( '__HALT_COMPILER', (string) file_get_contents( $this->path.'bin/hymn-link' ) );
	}
}
