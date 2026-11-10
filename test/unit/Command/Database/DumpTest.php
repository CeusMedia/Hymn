<?php
class Hymn_Test_Command_Database_DumpTest extends PHPUnit_Framework_TestCase
{
	protected string $path;

	protected function setUp(): void
	{
		$this->path	= sys_get_temp_dir().'/hymn-test-dump-'.uniqid().'/';
		mkdir( $this->path.'existing', 0775, TRUE );
	}

	protected function tearDown(): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->path ) );
	}

	public function testDefaultPath(): void
	{
		$fileName	= Hymn_Command_Database_Dump::resolveFileName( NULL, NULL, 'config/sql/' );
		self::assertMatchesRegularExpression( '@^config/sql/dump_\d{4}-\d{2}-\d{2}_\d{2}:\d{2}:\d{2}\.sql$@', $fileName );
	}

	public function testPathOption(): void
	{
		$fileName	= Hymn_Command_Database_Dump::resolveFileName( NULL, 'backup/', 'config/sql/' );
		self::assertStringStartsWith( 'backup/dump_', $fileName );
	}

	public function testExistingFolderWithoutSlash(): void
	{
		$fileName	= Hymn_Command_Database_Dump::resolveFileName( NULL, $this->path.'existing', 'config/sql/' );
		self::assertStringStartsWith( $this->path.'existing/dump_', $fileName );

		$fileName	= Hymn_Command_Database_Dump::resolveFileName( $this->path.'existing', NULL, 'config/sql/' );
		self::assertStringStartsWith( $this->path.'existing/dump_', $fileName );
	}

	public function testFolderArgument(): void
	{
		$fileName	= Hymn_Command_Database_Dump::resolveFileName( 'new/folder/', 'other/', 'config/sql/' );
		self::assertStringStartsWith( 'new/folder/dump_', $fileName );							//  argument wins
	}

	public function testFileArgument(): void
	{
		self::assertSame( 'backup/before-update.sql', Hymn_Command_Database_Dump::resolveFileName( 'backup/before-update.sql', NULL, 'config/sql/' ) );
	}

	public function testInvalidArgumentIsIgnored(): void
	{
		$fileName	= Hymn_Command_Database_Dump::resolveFileName( '--', NULL, 'config/sql/' );
		self::assertStringStartsWith( 'config/sql/dump_', $fileName );
	}
}
