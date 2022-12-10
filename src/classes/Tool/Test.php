<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_Test
{
	protected Hymn_Client $client;

	protected static array $shellCommands	= array(
		'graphviz'	=> array(
			'command'	=> "dot -V",
			'error'		=> 'Missing graphViz.',
		),
	);

	public function __construct( Hymn_Client $client )
	{
		$this->client		= $client;
	}

	public function checkShellCommand( string $key )
	{
		if( !array_key_exists( $key, self::$shellCommands ) )
			throw new InvalidArgumentException( "No shell command test available for '".$key."'" );
		$command	= self::$shellCommands[$key]['command']." >/dev/null 2>&1";
		exec( $command, $results, $code );
		if( $code == 127 )
			throw new RuntimeException( self::$shellCommands[$key]['error'] );
	}

	public function checkPhpfileSyntax( string $filePath ): object
	{
		return static::staticCheckPhpfileSyntax( $filePath );
	}

	public function checkPhpClasses( ?string $path = NULL, ?bool $recursive = FALSE, bool $verbose = FALSE, int $level = 0 )
	{
		$path		= $path ?? 'src/classes';
		$path		= ltrim( rtrim( trim( $path ), '/' ), './' );
		$recursive	= $recursive || $path === 'src/classes';

		$indent	= '- ';
		$index	= new DirectoryIterator( $path );
		$valid	= TRUE;
		if( $level === 0 )
			$this->client->outVerbose( sprintf( 'Checking syntax of files in folder %s:', $path ) );
		foreach( $index as $entry ){
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() && $recursive )
				self::checkPhpClasses( $entry->getPathname(), $recursive, $verbose, $level + 1 );
			else if( $entry->isFile() ){
				if( !preg_match( '/\.php[0-9]*$/', $entry->getFilename() ) )
					continue;
				$this->client->outVerbose( $indent.$entry->getPathname() );

				$syntax	= $this->checkPhpfileSyntax( $entry->getPathname() );
				if( !$syntax->valid ){
					$this->client->out( 'Invalid PHP code found in '.$entry->getPathname() );
					if( $syntax->output )
						$this->client->out( join( PHP_EOL, $syntax->output ) );
				}
			}
		}
		if( !$valid )
			throw new RuntimeException( 'Invalid PHP code has been found. Please check syntax!' );
	}

	public static function staticCheckPhpfileSyntax( string $filePath ): object
	{
		$code		= 0;
		$output		= [];
		$command	= "php -l ".$filePath/*." >/dev/null"*/." 2>&1";
		@exec( $command, $output, $code );
		$message	= 'Syntax error in file '.$filePath;
		if( isset( $output[0] ) && strlen( trim( $output[0] ) ) )
			$message	= $output[0];
		if( isset( $output[2] ) && strlen( trim( $output[2] ) ) )
			$message	= $output[2];
		return (object) array(
			'valid'		=> $code === 0,
			'code'		=> $code,
			'message'	=> $message,
			'output'	=> $output,
		);
	}
}
