<?php
class Hymn_Test_Tool_Database_CLI_MySQLTest extends PHPUnit_Framework_TestCase
{
	protected string $file;

	protected function setUp(): void
	{
		$this->file	= sys_get_temp_dir().'/hymn-test-dump-'.uniqid().'.sql';
		file_put_contents( $this->file, implode( "\n", [
			'-- Table structure for table `app_users`',
			'DROP TABLE IF EXISTS `app_users`;',
			'CREATE TABLE `app_users` (',
			'  `userId` int NOT NULL,',
			'  `name` varchar(100) NOT NULL',
			');',
			'-- Dumping data for table `app_users`',
			'LOCK TABLES `app_users` WRITE;',
			"INSERT INTO `app_users` VALUES (1,'app_admin');",
			'UNLOCK TABLES;',
			'',
		] ) );
	}

	protected function tearDown(): void
	{
		@unlink( $this->file );
	}

	public function testPrefixIsReplacedByPlaceholder(): void
	{
		$mysql	= new Hymn_Tool_Database_CLI_MySQL( new Hymn_Client( ['--quiet'], FALSE ) );
		$mysql->insertPrefixInFile( $this->file, 'app_' );
		$content	= (string) file_get_contents( $this->file );
		self::assertStringContainsString( 'DROP TABLE IF EXISTS `<%?prefix%>users`;', $content );
		self::assertStringContainsString( 'CREATE TABLE `<%?prefix%>users` (', $content );
		self::assertStringContainsString( 'LOCK TABLES `<%?prefix%>users` WRITE;', $content );
		self::assertStringContainsString( 'INSERT INTO `<%?prefix%>users` VALUES', $content );
		self::assertStringContainsString( "(1,'app_admin')", $content );							//  data is untouched
		self::assertStringContainsString( '`name` varchar(100)', $content );					//  columns are untouched
		self::assertFileDoesNotExist( $this->file.'_' );										//  no temporary file left
	}

	public function testPrefixIsReplacedByOtherPrefix(): void
	{
		$mysql	= new Hymn_Tool_Database_CLI_MySQL( new Hymn_Client( ['--quiet'], FALSE ) );
		$mysql->setPrefixPlaceholder( 'test_' );
		$mysql->insertPrefixInFile( $this->file, 'app_' );
		$content	= (string) file_get_contents( $this->file );
		self::assertStringContainsString( 'CREATE TABLE `test_users` (', $content );
		self::assertStringNotContainsString( '`app_users`', $content );
	}
}
