<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
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
				self::checkPhpClasses( $entry->getPathname(), $recursive, $verbose, $level + 1 );
			else if( $entry->isFile() ){
				if( !preg_match( "/\.php/", $entry->getFilename() ) )
					continue;
				if( $verbose )
					Hymn_Client::out( $indent.". File: ".$entry->getPathname() );

				$code		= 0;
				$results	= array();
				$command	= "php -l ".$entry->getPathname()/*." >/dev/null"*/." 2>&1";
				@exec( $command, $results, $code );
				if( $code !== 0 ){
					$valid		= FALSE;
					$message	= "Invalid PHP code has been found in ".$entry->getPathname().".";
					$message	= isset( $results[0] ) ? $results[0]."." : $message;
					Hymn_Client::out( $message );
				}
			}
		}
		if( !$valid )
			throw new RuntimeException( "Invalid PHP code has been found. Please check syntax!" );
	}
}
?>
