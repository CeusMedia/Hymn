<?php
if( version_compare( PHP_VERSION, '8.1', '<' ) )
	die( 'Needs PHP 8.1 or higher' );

require_once 'phar://hymn.phar/Client.php';										//  load main client class
require_once 'phar://hymn.phar/Loader.php';										//  load class loader
require_once 'phar://hymn.phar/Command/Interface.php';							//  preload command interface
//require_once 'phar://hymn.phar/Command/Abstract.php';							//  preload abstract command class

define( 'HAS_EXT_MBSTRING', extension_loaded( 'mbstring' ) );

$arguments	= array_slice( $argv, 1 );

if( version_compare( PHP_VERSION, '8.0', '<' ) ){
	function str_starts_with( string $haystack, string $needle ): bool
	{
		return '' !== $haystack
			&& '' !== $needle
			&& $needle === ( HAS_EXT_MBSTRING
				? mb_substr( $haystack, 0, strlen( $needle ) )
				: substr( $haystack, 0, strlen( $needle ) )
			);

/*		if( 0 === strlen( $haystack ) || 0 === strlen( $needle ) )
			return FALSE;
		if( HAS_EXT_MBSTRING )
			return mb_substr( $haystack, 0, strlen( $needle ) ) === $needle;
		/** @noinspection PhpStrFunctionsInspection */
/*		return substr( $haystack, 0, strlen( $needle ) ) === $needle;*/
	}

	function str_ends_with( string $haystack, string $needle ): bool
	{
		return '' !== $haystack
			&& '' !== $needle
			&& $needle === ( HAS_EXT_MBSTRING
				? mb_substr( $haystack, -strlen( $needle ) )
				: substr( $haystack, -strlen( $needle ) )
			);

/*		if( 0 === strlen( $haystack ) || 0 === strlen( $needle ) )
			return FALSE;
		if( HAS_EXT_MBSTRING )
			return mb_substr( $haystack, -strlen( $needle ) ) === $needle;
		/** @noinspection PhpStrFunctionsInspection */
/*		return substr( $haystack, -strlen( $needle ) ) === $needle;*/
	}

	function str_contains( string $haystack, string $needle ): bool
	{
		return '' !== $haystack
			&& '' !== $needle
			&& FALSE !== ( HAS_EXT_MBSTRING
				? mb_strpos( $haystack, $needle )
				: strpos( $haystack, $needle )
			);

/*		if( '' !== $haystack || '' !== $needle )
			return FALSE;
		if( HAS_EXT_MBSTRING )
			return FALSE !== mb_strpos( $haystack, $needle );
		/** @noinspection PhpStrFunctionsInspection */
/*		return FALSE !== strpos( $haystack, $needle );*/
	}
}

new Hymn_Loader();																//  load hymn classes
$client	= new Hymn_Client( $arguments );										//  start hymn client
$client->run();