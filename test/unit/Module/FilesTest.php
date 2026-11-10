<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Module_FilesTest extends PHPUnit_Framework_TestCase
{
	public static function provideValidPaths(): array
	{
		return [
			['index.php'], ['.htaccess'], ['Controller/Test.php'], ['background/Logo..old.png'], ['a/.hidden/b'],
		];
	}

	#[DataProvider('provideValidPaths')]
	public function testValidPath( string $path ): void
	{
		Hymn_Module_Files::checkRelativePath( $path, 'Test' );
		self::assertTrue( TRUE );
	}

	public static function provideInvalidPaths(): array
	{
		return [
			'parent'			=> ['../escaped.txt'],
			'parent inside'		=> ['a/../../escaped.txt'],
			'parent at end'		=> ['a/..'],
			'only parent'		=> ['..'],
			'absolute'			=> ['/etc/passwd'],
			'backslashes'		=> ['..\\escaped.txt'],
			'empty'				=> [''],
			'null byte'			=> ["a\0b"],
		];
	}

	#[DataProvider('provideInvalidPaths')]
	public function testInvalidPath( string $path ): void
	{
		$this->expectException( RuntimeException::class );
		Hymn_Module_Files::checkRelativePath( $path, 'Test' );
	}
}
