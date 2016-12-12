<?php
$path			= realpath( __DIR__.'/../src/classes' ).'/';
$directories	= array(
	'',
	'Module',
	'Command',
);

require_once $path.'Command/Interface.php';
require_once $path.'Command/Abstract.php';

foreach( $directories as $directory )
	loadClassesInPath( $path.$directory );

function loadClassesInPath( $path ){
	foreach( new DirectoryIterator( $path ) as $entry )
		if( $entry->isFile() && preg_match( "/\.php$/", $entry->getFilename() ) )
			require_once $path.'/'.$entry->getFilename();
}
