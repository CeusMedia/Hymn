<?php
$path			= realpath( __DIR__.'/../src/classes' ).'/';
$directories	= [
	'',
	'Module',
	'Structure',
	'Structure',
	'Structure/Module',
	'Command/App',
	'Command',
	'Tool/XML',
];

require_once $path.'Command/Interface.php';
require_once $path.'Command/Abstract.php';

foreach( $directories as $directory )
	loadClassesInPath( $path.$directory );

function loadClassesInPath( $path ){
	foreach( new DirectoryIterator( $path ) as $entry ){
		if( $entry->isFile() && preg_match( "/\.php$/", $entry->getFilename() ) ){
//			print( 'Loading class '.$path.'/'.$entry->getFilename().PHP_EOL );
			require_once $path.'/'.$entry->getFilename();
		}
	}
}

if( !class_exists( 'PHPUnit_Framework_TestCase' ) ){
	class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase{
	}
}
