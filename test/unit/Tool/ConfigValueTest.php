<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_ConfigValueTest extends PHPUnit_Framework_TestCase
{
	public static function provideValues(): array
	{
		return [
			'string'				=> ['abc', NULL, 'abc'],
			'string trimmed'		=> ['  abc ', 'string', 'abc'],
			'integer'				=> ['42', 'int', 42],
			'integer by alias'		=> ['42', 'integer', 42],
			'float'					=> ['1.5', 'float', 1.5],
			'bool yes'				=> ['yes', 'bool', TRUE],
			'bool 1'				=> ['1', 'boolean', TRUE],
			'bool no'				=> ['no', 'bool', FALSE],
			'bool false'			=> ['FALSE', 'bool', FALSE],
			'bool 0'				=> ['0', 'bool', FALSE],
			'bool empty'			=> ['', 'bool', FALSE],
			'bool from bool'		=> [TRUE, 'bool', TRUE],
		];
	}

	#[DataProvider('provideValues')]
	public function testSetValue( bool|string $input, ?string $type, bool|int|float|string $expected ): void
	{
		$value	= new Hymn_Tool_ConfigValue( $input, $type );
		self::assertSame( $expected, $value->getValue() );
	}

	public function testGetValueAsTrimmedString(): void
	{
		self::assertSame( 'yes', ( new Hymn_Tool_ConfigValue( 'true', 'bool' ) )->getValue( TRUE ) );
		self::assertSame( 'no', ( new Hymn_Tool_ConfigValue( 'no', 'bool' ) )->getValue( TRUE ) );
		self::assertSame( '42', ( new Hymn_Tool_ConfigValue( '42', 'int' ) )->getValue( TRUE ) );
		self::assertSame( 'abc', ( new Hymn_Tool_ConfigValue( ' abc ' ) )->getValue( TRUE ) );
	}

	public function testInvalidType(): void
	{
		$this->expectException( DomainException::class );
		new Hymn_Tool_ConfigValue( 'abc', 'array' );
	}

	public function testIsAndHasValue(): void
	{
		$unset	= new Hymn_Tool_ConfigValue();
		self::assertFalse( $unset->is() );
		self::assertFalse( $unset->hasValue() );

		$empty	= new Hymn_Tool_ConfigValue( '' );
		self::assertTrue( $empty->is() );
		self::assertFalse( $empty->hasValue() );

		$set	= new Hymn_Tool_ConfigValue( 'abc' );
		self::assertTrue( $set->is() );
		self::assertTrue( $set->hasValue() );
	}

	public function testCompareTo(): void
	{
		$unset	= new Hymn_Tool_ConfigValue();
		$empty	= new Hymn_Tool_ConfigValue( '' );
		$a		= new Hymn_Tool_ConfigValue( 'a' );
		$b		= new Hymn_Tool_ConfigValue( 'b' );
		$int1	= new Hymn_Tool_ConfigValue( '1', 'int' );

		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_UNSET_BOTH, $unset->compareTo( new Hymn_Tool_ConfigValue() ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_UNSET_SELF, $unset->compareTo( $a ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_UNSET_OTHER, $a->compareTo( $unset ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_EMPTY_BOTH, $empty->compareTo( new Hymn_Tool_ConfigValue( ' ' ) ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_EMPTY_SELF, $empty->compareTo( $a ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_EMPTY_OTHER, $a->compareTo( $empty ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_EQUAL, $a->compareTo( new Hymn_Tool_ConfigValue( 'a' ) ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_UNEQUAL, $a->compareTo( $b ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_UNEQUAL, $int1->compareTo( new Hymn_Tool_ConfigValue( '1' ) ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_MISMATCH_TYPE, $int1->compareTo( new Hymn_Tool_ConfigValue( '1' ), FALSE ) );
		self::assertSame( Hymn_Tool_ConfigValue::COMPARED_MISMATCH_LENGTH, $a->compareTo( new Hymn_Tool_ConfigValue( 'aa' ), FALSE ) );
	}

	public function testDiffersFromIfBothSet(): void
	{
		$unset	= new Hymn_Tool_ConfigValue();
		$a		= new Hymn_Tool_ConfigValue( 'a' );
		self::assertFalse( $a->differsFromIfBothSet( $unset ) );
		self::assertFalse( $unset->differsFromIfBothSet( $a ) );
		self::assertFalse( $a->differsFromIfBothSet( new Hymn_Tool_ConfigValue( 'a' ) ) );
		self::assertTrue( $a->differsFromIfBothSet( new Hymn_Tool_ConfigValue( 'b' ) ) );
		self::assertTrue( $a->equalsToIfBothSet( new Hymn_Tool_ConfigValue( 'a' ) ) );

		$int	= new Hymn_Tool_ConfigValue( '1', 'int' );
		self::assertTrue( $int->differsFromIfBothSet( new Hymn_Tool_ConfigValue( '1' ) ) );			//  type safe: 1 !== '1'
		self::assertFalse( $int->differsFromIfBothSet( new Hymn_Tool_ConfigValue( '1' ), FALSE ) );	//  compared as trimmed strings
	}

	public function testApplyIfSet(): void
	{
		$value	= new Hymn_Tool_ConfigValue( 'a' );
		$value->applyIfSet( new Hymn_Tool_ConfigValue() );
		self::assertSame( 'a', $value->getValue() );
		$value->applyIfSet( new Hymn_Tool_ConfigValue( 'b' ) );
		self::assertSame( 'b', $value->getValue() );
	}
}
