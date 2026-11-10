<?php
class Hymn_Test_Tool_Database_KeepRulesTest extends PHPUnit_Framework_TestCase
{
	public function testDailyKeepsNewestDumps(): void
	{
		$files	= $this->createDumpNames( '2026-11-01', 10 );								//  10 days back from Nov 1st
		$rules	= new Hymn_Tool_Database_KeepRules( 3 );
		$remove	= $rules->getFilesToRemove( $files );
		self::assertCount( 7, $remove );
		self::assertNotContains( 'dump_2026-11-01_02:00:00.sql', $remove );
		self::assertNotContains( 'dump_2026-10-30_02:00:00.sql', $remove );
		self::assertContains( 'dump_2026-10-29_02:00:00.sql', $remove );
		self::assertSame( 'dump_2026-10-29_02:00:00.sql', $remove[0] );						//  newest first
	}

	public function testDailyCountsDumpsNotDays(): void
	{
		$files	= ['dump_2026-11-02_08:00:00.sql', 'dump_2026-11-02_20:00:00.sql', 'dump_2026-11-01_20:00:00.sql'];
		$remove	= ( new Hymn_Tool_Database_KeepRules( 2 ) )->getFilesToRemove( $files );
		self::assertSame( ['dump_2026-11-01_20:00:00.sql'], $remove );
	}

	public function testWeeklyKeepsSundays(): void
	{
		$files	= $this->createDumpNames( '2026-11-10', 30 );								//  Sundays: Nov 8, 1, Oct 25, 18
		$remove	= ( new Hymn_Tool_Database_KeepRules( 1, 2 ) )->getFilesToRemove( $files );
		self::assertNotContains( 'dump_2026-11-10_02:00:00.sql', $remove );					//  daily
		self::assertNotContains( 'dump_2026-11-08_02:00:00.sql', $remove );					//  weekly 1
		self::assertNotContains( 'dump_2026-11-01_02:00:00.sql', $remove );					//  weekly 2
		self::assertContains( 'dump_2026-10-25_02:00:00.sql', $remove );						//  weekly 3: too old
		self::assertCount( 27, $remove );
	}

	public function testMonthlyAndYearly(): void
	{
		$files	= [
			'dump_2026-03-01_02:00:00.sql',
			'dump_2026-02-15_02:00:00.sql',
			'dump_2026-02-01_02:00:00.sql',
			'dump_2026-01-01_02:00:00.sql',
			'dump_2025-12-01_02:00:00.sql',
			'dump_2025-01-01_02:00:00.sql',
			'dump_2024-01-01_02:00:00.sql',
		];
		$remove	= ( new Hymn_Tool_Database_KeepRules( 1, 0, 2, 2 ) )->getFilesToRemove( $files );
		self::assertSame( [
			'dump_2026-02-15_02:00:00.sql',													//  not 1st of month
			'dump_2025-12-01_02:00:00.sql',													//  monthly 4: too old (Jan 1st is kept as yearly)
			'dump_2024-01-01_02:00:00.sql',													//  yearly 3
		], $remove );
	}

	public function testOtherFilesAreIgnored(): void
	{
		$files	= ['latest.sql', 'dump_latest.sql', 'notes.txt', 'dump_2026-11-01_02:00:00.sql.gz', 'dump_2026-10-01_02:00:00.sql'];
		$remove	= ( new Hymn_Tool_Database_KeepRules( 1 ) )->getFilesToRemove( $files );
		self::assertSame( ['dump_2026-10-01_02:00:00.sql'], $remove );						//  compressed dump is newest
	}

	public function testNoRulesRemoveAll(): void
	{
		$files	= $this->createDumpNames( '2026-11-01', 3 );
		self::assertCount( 3, ( new Hymn_Tool_Database_KeepRules() )->getFilesToRemove( $files ) );
	}

	public function testNegativeRule(): void
	{
		$this->expectException( InvalidArgumentException::class );
		new Hymn_Tool_Database_KeepRules( -1 );
	}

	/**
	 *	@return		list<string>		Dump file names of a number of days, ending at given date, in random order
	 */
	protected function createDumpNames( string $lastDate, int $days ): array
	{
		$names	= [];
		$date	= new DateTimeImmutable( $lastDate );
		for( $i = 0; $i < $days; $i++ )
			$names[]	= 'dump_'.$date->modify( '-'.$i.' days' )->format( 'Y-m-d' ).'_02:00:00.sql';
		shuffle( $names );
		return $names;
	}
}
