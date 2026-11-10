<?php
class Hymn_Test_Tool_Database_PDOTest extends PHPUnit_Framework_TestCase
{
	public function testFilterTablesByPrefixIsLiteral(): void
	{
		$tables	= ['app_users', 'app_roles', 'app2_users', 'appXusers', 'other_app_users', 'app'];
		self::assertSame( ['app_users', 'app_roles'], Hymn_Tool_Database_PDO::filterTablesByPrefix( $tables, 'app_' ) );
	}

	public function testFilterTablesWithoutPrefix(): void
	{
		$tables	= ['b', 'a'];
		self::assertSame( ['b', 'a'], Hymn_Tool_Database_PDO::filterTablesByPrefix( $tables, NULL ) );
		self::assertSame( ['b', 'a'], Hymn_Tool_Database_PDO::filterTablesByPrefix( $tables, '' ) );
	}

	public function testFilterTablesWithPercentInPrefix(): void
	{
		self::assertSame( ['a%b_1'], Hymn_Tool_Database_PDO::filterTablesByPrefix( ['a%b_1', 'axb_1'], 'a%b' ) );
	}

	public function testEscapeLike(): void
	{
		self::assertSame( 'my\\_app', Hymn_Tool_Database_PDO::escapeLike( 'my_app' ) );
		self::assertSame( '100\\%', Hymn_Tool_Database_PDO::escapeLike( '100%' ) );
		self::assertSame( 'a\\\\b', Hymn_Tool_Database_PDO::escapeLike( 'a\\b' ) );
		self::assertSame( 'plain', Hymn_Tool_Database_PDO::escapeLike( 'plain' ) );
	}
}
