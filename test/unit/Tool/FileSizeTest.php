<?php

use PHPUnit\Framework\Attributes\DataProvider;

class Hymn_Test_Tool_FileSizeTest extends PHPUnit_Framework_TestCase
{
	public static function provideBytes(): array
	{
		return [
			[0, '0 B'],
			[100, '100 B'],
			[511, '511 B'],
			[512, '0.5 KB'],
			[1024, '1 KB'],
			[1536, '1.5 KB'],
			[1048576, '1 MB'],
			[1073741824, '1 GB'],
		];
	}

	#[DataProvider('provideBytes')]
	public function testFormatBytes( int $bytes, string $expected ): void
	{
		self::assertSame( $expected, Hymn_Tool_FileSize::formatBytes( $bytes ) );
	}

	public function testFormatBytesWithOptions(): void
	{
		self::assertSame( '1.46KB', Hymn_Tool_FileSize::formatBytes( 1500, 2, '' ) );
		self::assertSame( '1000 B', Hymn_Tool_FileSize::formatBytes( 1000, 1, ' ', 1 ) );		//  edge: switch unit at 1024 only
	}

	public function testGet(): void
	{
		$file	= tempnam( sys_get_temp_dir(), 'hymn-test-' );
		file_put_contents( $file, str_repeat( 'x', 2048 ) );
		self::assertSame( '2 KB', Hymn_Tool_FileSize::get( $file ) );
		unlink( $file );
	}

	public function testGetMissingFile(): void
	{
		$this->expectException( RuntimeException::class );
		Hymn_Tool_FileSize::get( '/not/existing/file' );
	}
}
