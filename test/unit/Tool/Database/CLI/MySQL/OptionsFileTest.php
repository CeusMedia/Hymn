<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_Database_CLI_MySQL_OptionsFileTest extends PHPUnit_Framework_TestCase
{
	public static function provideValues(): array
	{
		return [
			'plain'				=> ['secret'],
			'hash'				=> ['pass#word'],
			'semicolon'			=> ['pass;word'],
			'double quotes'		=> ['say "hi"'],
			'single quote'		=> ["it's"],
			'backslash'			=> ['back\\slash'],
			'spaces around'		=> [' spaced '],
			'equals'			=> ['a=b'],
		];
	}

	#[DataProvider('provideValues')]
	public function testQuotedValueIsReadByMysql( string $value ): void
	{
		$tool	= trim( (string) shell_exec( 'command -v my_print_defaults 2>/dev/null' ) );
		if( '' === $tool )
			self::markTestSkipped( 'my_print_defaults is not installed' );
		$file	= (string) tempnam( sys_get_temp_dir(), 'hymn-test-mysql-' );
		try{
			file_put_contents( $file, "[client]\npassword=".Hymn_Tool_Database_CLI_MySQL_OptionsFile::quoteValue( $value )."\n" );
			$output	= (string) shell_exec( escapeshellarg( $tool ).' --defaults-file='.escapeshellarg( $file ).' client' );	//  exec() would strip trailing spaces
		}
		finally{
			unlink( $file );
		}
		self::assertSame( '--password='.$value.PHP_EOL, $output );
	}
}
