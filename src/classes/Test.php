<?php
class Hymn_Test {

	static protected $shellCommands	= array(
		'graphviz'	=> array(
			'command'	=> "dot -V",
			'error'		=> 'Missing graphViz.',
		),
	);

	static public function checkShellCommand( $key ){
		if( !array_key_exists( $key, self::$shellCommands ) )
			throw new InvalidArgumentException( "No shell command test available for '".$key."'" );
		$command	= self::$shellCommands[$key]['command']." >/dev/null 2>&1";
		exec( $command, $results, $code );
		if( $code == 127 )
			throw new RuntimeException( self::$shellCommands[$key]['error'] );
	}

	static public function checkPhpClasses( $path = "src", $recursive = TRUE, $verbose = FALSE, $level = 0 ){
		$indent	= str_repeat( ". ", $level );
		if( $verbose )
			Hymn_Client::out( $indent."Folder: ".$path );
		$index	= new DirectoryIterator( $path );
		$valid	= TRUE;
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() && $recursive )
				self::checkPhpClasses( $entry->getPathname(), $verbose, $level + 1 );
			else if( $entry->isFile() ){
				if( !preg_match( "/\.php/", $entry->getFilename() ) )
					continue;
				if( $verbose )
					Hymn_Client::out( $indent.". File: ".$entry->getPathname() );

				$code		= 0;
				$results	= array();
				$command	= "php -l ".$entry->getPathname()/*." >/dev/null"*/." 2>&1";
				exec( $command, $results, $code );
				if( $code !== 0 ){
					$valid		= FALSE;
					$message	= "Invalid PHP code has been found in ".$entry->getPathname().".";
					$message	= isset( $results[2] ) ? $results[2]."." : $message;
					Hymn_Client::out( $message );
				}
			}
		}
		if( !$valid )
			throw new RuntimeException( "Invalid PHP code has been found. Please check syntax!" );
	}
}
?>
