<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool.Database.CLI.MySQL
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
*	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Database.CLI.MySQL
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_Database_CLI_MySQL_OptionsFile
{
	protected string $defaultFileName		= '.mysqlOptions.cfg';
	protected string $actualFileName;
	protected Hymn_Client $client;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function create( ?string $fileName = NULL, bool $strict = TRUE ): self
	{
		$fileName	= is_null( $fileName ) ? $this->defaultFileName : $fileName;
		if( file_exists( $fileName ) && $strict ){
			$message	= 'MySQL options file "'.$fileName.'" is already existing';
			$this->client->outError( $message, Hymn_Client::EXIT_ON_RUN );
		}
		$dbc		= $this->client->getDatabase();
		$lines		= array('[client]');
		$map		= [
			'host'		=> 'host',
			'port'		=> 'port',
//			'database'	=> 'name',
			'user'		=> 'username',
			'password'	=> 'password',
		];
		$optionList		= [];
		foreach( $map as $optionsKey => $dbaKey ){
			$trimmedValue 	= trim( $dbc->getConfig( $dbaKey ) );
			if( strlen( $trimmedValue ) )
				$optionList[$optionsKey]	= $trimmedValue;
		}
		foreach( $optionList as $key => $value )
			$lines[]	= $key.'='.$value;
		file_put_contents( $fileName, join( PHP_EOL, $lines ) );
		$this->actualFileName	 = $fileName;
		return $this;
	}

	public function getDefaultFileName(): string
	{
		return $this->defaultFileName;
	}

	public function has( ?string $fileName = NULL ): bool
	{
		$fileName	= is_null( $fileName ) ? $this->defaultFileName : $fileName;
		return file_exists( $fileName );
	}

	public function remove( ?string $fileName = NULL ): self
	{
		$fileName	= is_null( $fileName ) ? $this->defaultFileName : $fileName;
		if( $this->has( $fileName ) )
			@unlink( $fileName );
		return $this;
	}

	public function setDefaultFileName( string$fileName ): self
	{
		$this->defaultFileName	= $fileName;
		return $this;
	}
}
