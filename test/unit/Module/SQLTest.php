<?php
class Hymn_Test_Module_SQLTest extends PHPUnit_Framework_TestCase
{
	protected object $sql;

	protected function setUp(): void
	{
		$this->sql	= self::createSql();
	}

	public function testInstallUsesFinalScript(): void
	{
		$module	= $this->createModule( '0.3', [
			['install', '0.1', 'mysql', 'CREATE TABLE a;'],
			['update', '0.2', 'mysql', 'ALTER TABLE a;'],
			['install', 'final', 'mysql', 'CREATE TABLE a_final;'],
		] );
		self::assertSame( ['CREATE TABLE a_final;'], $this->extractSql( $this->sql->getModuleInstallSql( $module ) ) );
	}

	public function testInstallUsesUnversionedScript(): void
	{
		$module	= $this->createModule( '0.3', [
			['install', '', '*', 'CREATE TABLE a;'],
			['update', '0.2', 'mysql', 'ALTER TABLE a;'],
		] );
		self::assertSame( ['CREATE TABLE a;'], $this->extractSql( $this->sql->getModuleInstallSql( $module ) ) );
	}

	public function testInstallCollectsVersionedInstallAndUpdates(): void
	{
		$module	= $this->createModule( '0.4', [
			['install', '0.1', 'mysql', 'CREATE TABLE a;'],
			['update', '0.2', 'mysql', 'ALTER TABLE a 0.2;'],
			['update', '0.3', 'pgsql', 'ALTER TABLE a 0.3 for other driver;'],
			['update', '0.4', '*', 'ALTER TABLE a 0.4;'],
			['update', '0.5', 'mysql', 'ALTER TABLE a 0.5, newer than module;'],
		] );
		self::assertSame(
			['CREATE TABLE a;', 'ALTER TABLE a 0.2;', 'ALTER TABLE a 0.4;'],
			$this->extractSql( $this->sql->getModuleInstallSql( $module ) )
		);
	}

	public function testInstallSkipsEmptyScriptsAndOtherDrivers(): void
	{
		$module	= $this->createModule( '0.1', [
			['install', '', 'mysql', '   '],
			['install', '', 'pgsql', 'CREATE TABLE b;'],
		] );
		self::assertSame( [], $this->sql->getModuleInstallSql( $module ) );
	}

	public function testUpdateSelectsNewerScriptsSorted(): void
	{
		$installed	= $this->createModule( '0.2', [] );
		$module		= $this->createModule( '0.10', [
			['update', '0.10', 'mysql', 'ALTER 0.10;'],
			['update', '0.2', 'mysql', 'ALTER 0.2;'],
			['update', '0.9', 'mysql', 'ALTER 0.9;'],
			['update', '0.3', '*', 'ALTER 0.3;'],
			['update', '0.4', 'pgsql', 'ALTER 0.4 for other driver;'],
			['install', '0.1', 'mysql', 'CREATE;'],
		] );
		$scripts	= $this->sql->getModuleUpdateSql( $installed, $module );
		self::assertSame( ['0.3', '0.9', '0.10'], array_keys( $scripts ) );					//  numeric order, not lexical
		self::assertSame( ['ALTER 0.3;', 'ALTER 0.9;', 'ALTER 0.10;'], $this->extractSql( $scripts ) );
	}

	public function testUninstallUsesFinalScript(): void
	{
		$module	= $this->createModule( '0.3', [
			['uninstall', '0.1', 'mysql', 'DROP TABLE old;'],
			['uninstall', 'final', 'mysql', 'DROP TABLE a;'],
		] );
		self::assertSame( ['DROP TABLE a;'], $this->extractSql( $this->sql->getModuleUninstallSql( $module ) ) );
	}

	public function testNoScriptsWithoutDatabase(): void
	{
		$sql	= self::createSql( Hymn_Client::FLAG_NO_DB );
		$module	= $this->createModule( '0.1', [['install', '', 'mysql', 'CREATE TABLE a;']] );
		self::assertSame( [], $sql->getModuleInstallSql( $module ) );
		self::assertSame( [], $sql->getModuleUninstallSql( $module ) );
		self::assertSame( [], $sql->getModuleUpdateSql( $module, $module ) );
	}

	public function testNoScripts(): void
	{
		$module	= $this->createModule( '0.1', [] );
		self::assertSame( [], $this->sql->getModuleInstallSql( $module ) );
		self::assertSame( [], $this->sql->getModuleUpdateSql( $module, $module ) );
	}

	/**
	 *	Creates SQL handler without database connection: driver is always mysql.
	 *	@param		int		$flags		Client flags, eg. Hymn_Client::FLAG_NO_DB
	 */
	protected static function createSql( int $flags = 0 ): object
	{
		$client	= new Hymn_Client( ['--quiet'], FALSE );
		$client->flags	= $flags;																//  options are turned into flags on run only
		return new class( $client ) extends Hymn_Module_SQL {
			protected function checkDriver(): string
			{
				return 'mysql';
			}
		};
	}

	/**
	 *	@param		list<array{0: string, 1: string, 2: string, 3: string}>		$scripts	List of event, version, driver, SQL
	 */
	protected function createModule( string $version, array $scripts ): Hymn_Structure_Module
	{
		$module	= new Hymn_Structure_Module( 'Test', $version, '' );
		foreach( $scripts as [$event, $scriptVersion, $type, $sql] )
			$module->sql[]	= new Hymn_Structure_Module_SQL( $event, $scriptVersion, $type, $sql );
		return $module;
	}

	/**
	 *	@param		array<Hymn_Structure_Module_SQL>	$scripts
	 *	@return		list<string>
	 */
	protected function extractSql( array $scripts ): array
	{
		return array_values( array_map( static fn( $script ) => $script->sql, $scripts ) );
	}
}
