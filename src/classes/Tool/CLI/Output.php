<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo				code documentation
 */
class Hymn_Tool_CLI_Output
{
	public static string $outputMethod		= 'print';

	/** @var object{force: bool, quiet: bool, verbose: bool, veryVerbose: bool} $flags */
	public object $flags;

	protected Hymn_Client $client;

	/** @var object{outPrefixError: string, outPrefixDeprecation: string, errorCommandUnknown: string, errorCommandClassNotImplementingInterface: string}  */
	protected object $words;

	protected bool $exit;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client		Hymn client instance
	 *	@return		void
	 */
	public function __construct( Hymn_Client $client, bool $exit = TRUE )
	{
		$this->client	= $client;
		$this->exit		= $exit;
		$this->flags	= (object) [
			'force'			=> (bool) ( $this->client->flags & Hymn_Client::FLAG_FORCE ),
			'quiet'			=> (bool) ( $this->client->flags & Hymn_Client::FLAG_QUIET ),
			'verbose'		=> (bool) ( $this->client->flags & Hymn_Client::FLAG_VERBOSE ),
			'veryVerbose'	=> (bool) ( $this->client->flags & Hymn_Client::FLAG_VERY_VERBOSE ),
		];
		/** @var object{outPrefixError: string, outPrefixDeprecation: string, errorCommandUnknown: string, errorCommandClassNotImplementingInterface: string} $words */
		$words			= $this->client->getLocale()?->loadWords( 'client' );
		$this->words	= $words;

		if( self::$outputMethod !== 'print' )
			ob_start();
	}

	/**
	 *	Prints out message of one or more lines.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		self
	 *	@throws		InvalidArgumentException			if neither array nor string nor NULL given
	 */
	public function out( string|bool|int|float|array|NULL $lines = NULL, bool $newLine = TRUE ): self
	{
		if( is_null( $lines ) )
			$lines	= [];
		if( !is_array( $lines ) ){																	//  output content is not a list
			if( is_bool( $lines ) )																	//  output is boolean
				$lines	= $lines ? 'yes' : 'no';													//  convert to string
			if( !is_string( $lines ) && !is_numeric( $lines ) ){									//  output content is neither a string nor numeric
				throw new InvalidArgumentException( sprintf(										//  quit with exception
					'Argument must be a list, string or numeric (got: %s)',							//  ... complain about invalid argument
					gettype( $lines )																//  ... display argument type
				) );
			}
			$lines	= [$lines];																		//  collect output content as list
		}
		foreach( $lines as $line ){																	//  iterate output lines
			print( $line );																			//  display each line
			if( $newLine )																			//  output should be closed by newline character
				print( PHP_EOL );																	//  print newline character
		}
		return $this;
	}

	/**
	 *	Prints out deprecation message of one or more lines.
	 *	@access		public
	 *	@param		string|array		$lines		List of message lines or one string
	 *	@throws		InvalidArgumentException		if neither array nor string given
	 *	@throws		InvalidArgumentException		if given string is empty
	 *	@return		self
	 */
	public function outDeprecation( string|array $lines = [] ): self
	{
		if( !is_array( $lines ) ){
			if( !is_string( $lines ) )
				throw new InvalidArgumentException( 'Argument must be array or string.' );			//  ...
			if( !strlen( trim( $lines ) ) )
				throw new InvalidArgumentException( 'Argument must not be empty.' );				//  ...
			$lines	= [$lines];
		}
		$lines[0]	= $this->words->outPrefixDeprecation.$lines[0];
		array_unshift( $lines, '' );
		$lines[]	= '';
		$this->out( $lines );
		return $this;
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string			$message		Error message to print
	 *	@param		integer|NULL	$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		self
	 */
	public function outError( string $message, ?int $exitCode = NULL ): self
	{
		$this->out( $this->words->outPrefixError.$message );
		if( $this->exit && is_int( $exitCode ) && $exitCode > Hymn_Client::EXIT_ON_END ){
			if( self::$outputMethod !== 'print' && ob_get_level() )
				print( ob_get_clean() );
			exit( $exitCode );
		}
		return $this;
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		self
	 */
	public function outVerbose( string|bool|int|float|array|NULL $lines, bool $newLine = TRUE ): self
	{
		if( $this->flags->verbose )																	//  verbose mode is on
			if( !$this->flags->quiet )																//  quiet mode is off
				$this->out( $lines, $newLine );
		return $this;
	}

	/**
	 *	Prints out verbose message if very verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVeryVerbose( string|bool|int|float|array|NULL $lines, bool $newLine = TRUE ): void
  {
		if( $this->flags->veryVerbose )																//  very verbose mode is on
			$this->outVerbose( $lines, $newLine );
	}
}
