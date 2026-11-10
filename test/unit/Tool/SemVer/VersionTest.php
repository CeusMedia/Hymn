<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_SemVer_VersionTest extends PHPUnit_Framework_TestCase
{
	public static function provideParsableVersions(): array
	{
		return [
			'full'					=> ['1.2.3', 1, 2, 3, '', '', '1.2.3'],
			'major only'			=> ['1', 1, 0, 0, '', '', '1.0.0'],
			'major and minor'		=> ['1.2', 1, 2, 0, '', '', '1.2.0'],
			'v prefix'				=> ['v2.4.1', 2, 4, 1, '', '', '2.4.1'],
			'pre-release'			=> ['1.0.0-beta.2', 1, 0, 0, 'beta.2', '', '1.0.0-beta.2'],
			'build metadata'		=> ['1.0.0+build.5', 1, 0, 0, '', 'build.5', '1.0.0+build.5'],
			'pre-release and build'	=> ['1.0.0-RC1+001', 1, 0, 0, 'RC1', '001', '1.0.0-RC1+001'],
			'surrounding spaces'	=> [' 1.2.3 ', 1, 2, 3, '', '', '1.2.3'],
		];
	}

	#[DataProvider('provideParsableVersions')]
	public function testParse( string $input, int $major, int $minor, int $patch, string $preRelease, string $build, string $rendered ): void
	{
		$version	= Hymn_Tool_SemVer_Version::fromString( $input );
		self::assertSame( $major, $version->getMajor() );
		self::assertSame( $minor, $version->getMinor() );
		self::assertSame( $patch, $version->getPatch() );
		self::assertSame( $preRelease, $version->getPreRelease() );
		self::assertSame( $build, $version->getBuild() );
		self::assertSame( $rendered, $version->render() );
		self::assertSame( $rendered, (string) new Hymn_Tool_SemVer_Version( $input ) );
	}

	public static function provideInvalidVersions(): array
	{
		return [
			'empty'			=> [''],
			'four parts'	=> ['2.4.0.1'],
			'dev branch'	=> ['dev-master'],
			'wildcard'		=> ['1.2.*'],
			'text'			=> ['latest'],
			'operator'		=> ['>=1.0'],
		];
	}

	#[DataProvider('provideInvalidVersions')]
	public function testParseInvalid( string $input ): void
	{
		self::assertFalse( Hymn_Tool_SemVer_Version_Parser::isValid( $input ) );
		$this->expectException( InvalidArgumentException::class );
		Hymn_Tool_SemVer_Version::fromString( $input );
	}

	/**
	 *	Expected result of comparing first with second version: -1, 0 or 1.
	 */
	public static function provideComparisons(): array
	{
		return [
			'equal'							=> ['1.2.3', '1.2.3', 0],
			'partial equals full'			=> ['1.2', '1.2.0', 0],
			'major'							=> ['2.0.0', '1.9.9', 1],
			'minor numeric not lexical'		=> ['1.10.0', '1.9.0', 1],
			'patch'							=> ['1.0.1', '1.0.2', -1],
			'pre-release below release'		=> ['1.0.0-beta', '1.0.0', -1],
			'pre-release alpha < beta'		=> ['1.0.0-alpha', '1.0.0-beta', -1],
			'pre-release numeric ids'		=> ['1.0.0-beta.2', '1.0.0-beta.11', -1],
			'numeric id below alpha id'		=> ['1.0.0-1', '1.0.0-alpha', -1],
			'more ids is greater'			=> ['1.0.0-alpha.1', '1.0.0-alpha', 1],
			'build metadata ignored'		=> ['1.0.0+5', '1.0.0', 0],
			'build metadata ignored 2'		=> ['1.0.0+5', '1.0.0+6', 0],
		];
	}

	#[DataProvider('provideComparisons')]
	public function testCompare( string $v1, string $v2, int $expected ): void
	{
		self::assertSame( $expected, Hymn_Tool_SemVer_Version_Comparator::compare( $v1, $v2 ) );
		self::assertSame( -$expected, Hymn_Tool_SemVer_Version_Comparator::compare( $v2, $v1 ) );

		$version1	= new Hymn_Tool_SemVer_Version( $v1 );
		$version2	= new Hymn_Tool_SemVer_Version( $v2 );
		self::assertSame( 0 === $expected, $version1->isEqualTo( $version2 ) );
		self::assertSame( 0 !== $expected, $version1->isDifferentFrom( $version2 ) );
		self::assertSame( 1 === $expected, $version1->isGreaterThan( $version2 ) );
		self::assertSame( -1 === $expected, $version1->isLowerThan( $version2 ) );
		self::assertSame( -1 !== $expected, $version1->isAtLeast( $version2 ) );
		self::assertSame( 1 !== $expected, $version1->isAtMost( $version2 ) );
	}

	public function testIncrement(): void
	{
		self::assertSame( '2.0.0', Hymn_Tool_SemVer_Version::fromString( '1.2.3-beta+7' )->incrementMajor()->render() );
		self::assertSame( '1.3.0', Hymn_Tool_SemVer_Version::fromString( '1.2.3-beta+7' )->incrementMinor()->render() );
		self::assertSame( '1.2.4', Hymn_Tool_SemVer_Version::fromString( '1.2.3-beta+7' )->incrementPatch()->render() );
	}

	public function testIsStable(): void
	{
		self::assertTrue( Hymn_Tool_SemVer_Version::fromString( '1.0.0' )->isStable() );
		self::assertFalse( Hymn_Tool_SemVer_Version::fromString( '1.0.0-RC1' )->isStable() );
		self::assertFalse( Hymn_Tool_SemVer_Version::fromString( '0.9.0' )->isStable() );
	}

	public function testSatisfies(): void
	{
		$version	= Hymn_Tool_SemVer_Version::fromString( '1.5.0' );
		self::assertTrue( $version->satisfies( '^1.2' ) );
		self::assertTrue( $version->satisfies( new Hymn_Tool_SemVer_Constraint( '~1.4.0 || ~1.5.0' ) ) );
		self::assertFalse( $version->satisfies( '^2' ) );
	}
}
