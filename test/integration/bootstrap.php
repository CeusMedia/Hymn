<?php
require_once __DIR__ . '/../vendor/autoload.php';

$pathClasses			= realpath( __DIR__.'/../src/classes' ).'/';
$directories	= [
	'',
	'Module',
	'Module/Library',
	'Structure',
	'Structure',
	'Structure/Config',
	'Structure/Graph',
	'Structure/Module',
	'Command/App',
	'Command',
	'Tool',
	'Tool/CLI',
	'Tool/XML',
];

require_once $pathClasses.'Command/Interface.php';
require_once $pathClasses.'Command/Abstract.php';

foreach( $directories as $directory )
	loadClassesInPath( $pathClasses.$directory );

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

