<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_SemVer_ConstraintTest extends PHPUnit_Framework_TestCase
{
	/**
	 *	Constraint, version, expected result. Semantics as in Composer.
	 */
	public static function provideChecks(): array
	{
		return [
			//  exact
			['1.2.3', '1.2.3', TRUE],
			['1.2.3', '1.2.4', FALSE],
			['=1.2.3', '1.2.3', TRUE],
			['==1.2.3', '1.2.4', FALSE],
			['=1.2.0', '5.0.0', FALSE],
			['1.0.0', '1.0.0+5', TRUE],

			//  comparison operators
			['>=1.2', '1.2.0', TRUE],
			['>=1.2', '1.1.9', FALSE],
			['>1.2', '1.2.0', FALSE],
			['>1.2', '1.2.1', TRUE],
			['<=2.0', '2.0.0', TRUE],
			['<=2.0', '2.0.1', FALSE],
			['<2.0', '1.9.9', TRUE],
			['<2.0', '2.0.0', FALSE],
			['<2.0', '2.0.0-beta', FALSE],
			['<2.0.0-beta', '2.0.0-alpha', TRUE],
			['!=1.0', '1.0.0', FALSE],
			['!=1.0', '1.0.1', TRUE],
			['<>1.0', '1.0.0', FALSE],
			['>= 1.2', '1.3.0', TRUE],

			//  caret
			['^1.2', '1.5.0', TRUE],
			['^1.2', '1.2.0', TRUE],
			['^1.2', '1.1.9', FALSE],
			['^1.2', '2.0.0', FALSE],
			['^1.2', '2.0.0-beta', FALSE],
			['^1.2.3', '1.2.9', TRUE],
			['^1.2.3', '1.3.0', TRUE],
			['^1.2.3', '1.2.2', FALSE],
			['^1', '1.9.0', TRUE],
			['^1', '2.0.0', FALSE],
			['^0.3', '0.3.5', TRUE],
			['^0.3', '0.4.0', FALSE],
			['^0.0.3', '0.0.3', TRUE],
			['^0.0.3', '0.0.4', FALSE],
			['^0', '0.9.0', TRUE],
			['^0', '1.0.0', FALSE],
			['^8.1', '8.3.0', TRUE],
			['^1.0', 'v1.2.0', TRUE],

			//  tilde
			['~1.2', '1.9.0', TRUE],
			['~1.2', '2.0.0', FALSE],
			['~1.2', '9.0.0', FALSE],
			['~1.2.3', '1.2.9', TRUE],
			['~1.2.3', '1.3.0', FALSE],
			['~1', '1.5.0', TRUE],

			//  wildcards
			['*', '0.0.1', TRUE],
			['*', '99.0.0', TRUE],
			['', '1.0.0', TRUE],
			['1.2.*', '1.2.5', TRUE],
			['1.2.*', '1.3.0', FALSE],
			['1.2.x', '1.2.0', TRUE],
			['1.*', '1.9.0', TRUE],
			['1.*', '2.0.0', FALSE],

			//  hyphen ranges
			['1.0 - 2.0', '1.5.0', TRUE],
			['1.0 - 2.0', '2.0.5', TRUE],
			['1.0 - 2.0', '2.1.0', FALSE],
			['1.0 - 2.0', '0.9.0', FALSE],
			['1.0.0 - 2.1.0', '2.1.0', TRUE],
			['1.0.0 - 2.1.0', '2.1.1', FALSE],
			['1.0-2.0', '1.5.0', TRUE],

			//  AND
			['>=1.0 <2.0', '1.5.0', TRUE],
			['>=1.0 <2.0', '2.0.0', FALSE],
			['>=1.0,<2.0', '1.5.0', TRUE],
			['>=1.0, <2.0', '2.5.0', FALSE],
			['>=1.0 && <2.0', '1.5.0', TRUE],
			['>=1.0 <1.5 !=1.2.0', '1.2.0', FALSE],

			//  OR
			['^8.1|^8.2', '8.3.0', TRUE],
			['^1.0 || ^3.0', '2.0.0', FALSE],
			['^1.0 || ^3.0', '3.1.0', TRUE],
			['^1.0||^3.0', '1.1.0', TRUE],
			['>=1.0 <1.5 || ^2.0', '1.7.0', FALSE],
			['>=1.0 <1.5 || ^2.0', '2.3.0', TRUE],
			['1.0 - 1.5 || ^3', '1.5.9', TRUE],
		];
	}

	#[DataProvider('provideChecks')]
	public function testCheckVersion( string $constraint, string $version, bool $expected ): void
	{
		$object	= new Hymn_Tool_SemVer_Constraint( $constraint );
		self::assertSame( $expected, $object->checkVersion( $version ), $constraint.' with '.$version );
		self::assertSame( $expected, $object->checkVersion( Hymn_Tool_SemVer_Version::fromString( $version ) ) );
	}

	public static function provideInvalidConstraints(): array
	{
		return [
			'unknown operator'		=> ['=>1.0'],
			'not a version'			=> ['latest'],
			'four parts'			=> ['^2.4.0.1'],
			'operator only'			=> ['>='],
			'wildcard in operator'	=> ['^1.*'],
			'empty or part'			=> ['^1.0 ||'],
			'empty and part'		=> ['>=1.0,,<2.0'],
			'invalid in or'			=> ['^1.0 || foo'],
			'invalid in and'		=> ['>=1.0 bar'],
		];
	}

	#[DataProvider('provideInvalidConstraints')]
	public function testInvalidConstraint( string $constraint ): void
	{
		$this->expectException( InvalidArgumentException::class );
		new Hymn_Tool_SemVer_Constraint( $constraint );
	}

	public function testStructure(): void
	{
		$constraint	= new Hymn_Tool_SemVer_Constraint( '^1.0 || >=2.0 <3.0' );
		self::assertCount( 2, $constraint->getOrs() );
		self::assertCount( 0, $constraint->getAnds() );
		self::assertSame( '^1.0', $constraint->getOrs()[0]->getConstraint() );
		self::assertCount( 2, $constraint->getOrs()[1]->getAnds() );

		$constraint	= new Hymn_Tool_SemVer_Constraint( '>=2.0 <3.0' );
		self::assertCount( 0, $constraint->getOrs() );
		self::assertCount( 2, $constraint->getAnds() );
	}

	/**
	 *	Constraint and expected rendering of parsed range.
	 */
	public static function provideRangeRenderings(): array
	{
		return [
			['1.2.3', '1.2.3'],
			['*', '*'],
			['>1.0', '>1.0.0'],
			['<2.0', '<2.0.0'],
			['!=1.0', '!=1.0.0'],
			['^1.2', '>=1.2.0 <2.0.0'],
			['^0.3', '>=0.3.0 <0.4.0'],
			['~1.2.3', '>=1.2.3 <1.3.0'],
			['1.2.*', '>=1.2.0 <1.3.0'],
			['1.0 - 2.0', '>=1.0.0 <2.1.0'],
			['1.0.0 - 2.1.0', '>=1.0.0 <=2.1.0'],
		];
	}

	#[DataProvider('provideRangeRenderings')]
	public function testRangeRender( string $constraint, string $expected ): void
	{
		$range	= new Hymn_Tool_SemVer_Constraint_Range( $constraint );
		self::assertSame( $expected, $range->render() );

		$reparsed	= new Hymn_Tool_SemVer_Constraint( $expected );						//  rendering is parsable and equivalent
		foreach( ['0.0.1', '0.3.9', '1.0.0', '1.2.3', '1.2.9', '1.9.0', '2.0.0-beta', '2.0.5', '2.1.0', '3.0.0'] as $version )
			self::assertSame( $range->checkVersion( $version ), $reparsed->checkVersion( $version ), $constraint.' vs. '.$expected.' with '.$version );
	}
}
