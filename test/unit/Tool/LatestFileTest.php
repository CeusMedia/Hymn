<?php
class Hymn_Test_Tool_LatestFileTest extends PHPUnit_Framework_TestCase
{
	protected string $path;
	protected Hymn_Tool_LatestFile $finder;

	protected function setUp(): void
	{
		$this->path		= sys_get_temp_dir().'/hymn-test-latest-'.uniqid().'/';
		mkdir( $this->path );
		$this->finder	= new Hymn_Tool_LatestFile( new Hymn_Client( ['--quiet'], FALSE ) );
		$this->finder->setFileNamePattern( '/^dump_[0-9:_-]+\.sql$/' );
	}

	protected function tearDown(): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->path ) );
	}

	public function testFindsLatestByFileName(): void
	{
		$this->createFiles( [
			'dump_2026-01-15_10:00:00.sql',
			'dump_2026-11-02_08:30:00.sql',
			'dump_2026-11-02_08:29:59.sql',
			'dump_2025-12-31_23:59:59.sql',
			'notes.txt',
		] );
		self::assertSame( $this->path.'dump_2026-11-02_08:30:00.sql', $this->finder->find( $this->path ) );
	}

	public function testIgnoresNotMatchingFiles(): void
	{
		$this->createFiles( ['backup.sql', 'dump_latest.sql', 'dump_2026-01-01_00:00:00.sql.gz'] );
		self::assertNull( $this->finder->find( $this->path ) );
	}

	public function testAcceptedFileNameIsPreferred(): void
	{
		$this->createFiles( ['dump_2026-11-02_08:30:00.sql', 'latest.sql'] );
		$this->finder->setAcceptedFileNames( ['latest.sql'] );
		self::assertSame( $this->path.'latest.sql', $this->finder->find( $this->path ) );
	}

	public function testAcceptedFileNamesByArgument(): void
	{
		$this->createFiles( ['dump_2026-11-02_08:30:00.sql', 'latest.sql'] );
		self::assertSame( $this->path.'latest.sql', $this->finder->find( $this->path, NULL, ['latest.sql'] ) );
		self::assertSame( $this->path.'dump_2026-11-02_08:30:00.sql', $this->finder->find( $this->path, NULL, [] ) );
	}

	public function testPathBySetter(): void
	{
		$this->createFiles( ['dump_2026-11-02_08:30:00.sql'] );
		$this->finder->setPath( $this->path );
		self::assertSame( $this->path.'dump_2026-11-02_08:30:00.sql', $this->finder->find() );
	}

	public function testMissingPath(): void
	{
		$this->expectException( RuntimeException::class );
		$this->finder->find();
	}

	/**
	 *	@param		list<string>	$fileNames
	 */
	protected function createFiles( array $fileNames ): void
	{
		foreach( $fileNames as $fileName )
			touch( $this->path.$fileName );
	}
}
