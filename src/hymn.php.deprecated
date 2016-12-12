#!/usr/bin/env php
<?php
$path			= __DIR__.'/classes/';
$directories	= array(
	'',
	'Module',
	'Command',
);
require_once $path.'Command/Interface.php';
require_once $path.'Command/Abstract.php';
foreach( $directories as $directory ){
	foreach( new DirectoryIterator( $path.$directory ) as $entry )
		if( $entry->isFile() && preg_match( "/\.php$/", $entry->getFilename() ) )
			require_once $directory.$entry->getFilename();
}
new Hymn_Client( array_slice( $argv, 1 ) );
?>
