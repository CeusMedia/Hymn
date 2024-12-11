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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Database_Load extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $defaultPath;

	/**
	 *	Execute this command.
	 *	Implements flags: database-no
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$this->denyOnProductionMode();

		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;

		if( !Hymn_Command_Database_Test::test( $this->client ) )
			$this->outError( 'Database can NOT be connected.', Hymn_Client::EXIT_ON_SETUP );

		$fileName	= NULL;
		$pathName	= $this->client->arguments->getArgument();
		if( $pathName && file_exists( $pathName ) ){
			if( is_dir( $pathName ) )
				$fileName	= $this->getLatestDump( $pathName );
			else{
				$fileName	= $pathName;
			}
		}
		else if( file_exists( $this->defaultPath ) )
			$fileName		= $this->getLatestDump();
		else
			$this->outError( 'No loadable database file or folder found.', Hymn_Client::EXIT_ON_RUN );

//		$this->client->outVerbose( 'File: '.$fileName );
		if( !( $fileName && file_exists( $fileName ) ) )
			$this->outError( 'No loadable database file found.', Hymn_Client::EXIT_ON_INPUT );

		if( !is_readable( $fileName ) )
			$this->outError( 'Missing read access to SQL script: '.$fileName, Hymn_Client::EXIT_ON_EXEC );
		try{
			$dbc		= $this->client->getDatabase();
			$prefix		= $dbc->getConfigValue( 'prefix' );											//  get table prefix from config
			$mysql		= new Hymn_Tool_Database_CLI_MySQL( $this->client );						//  get CLI handler for MySQL
			$fileSize	= Hymn_Tool_FileSize::get( $fileName );										//  format file size
			if( $this->flags->verbose ){
				$dba	= $dbc->getConfig();
				$this->out( [
					'Import file:  '.$fileName,														//  show import file name
					'File size:    '.$fileSize,														//  show import file size
					'DB Server:    '.$dba->host.'@'.$dba->port,										//  show server host and port from config
					'Database:     '.$dba->name,													//  show database name from config
					'Table prefix: '.( $prefix ?: '(none)' ),										//  show table prefix from config
					'Access as:    '.$dba->username,												//  show username from config
				] );
				$this->out( 'Loading import file ...' );
			}
			else
				$this->out( 'Loading import file '.$fileName.' ('.$fileSize.') ...' );
			if( $this->flags->dry )
				$this->out( '-> Dry Mode: Import itself not executed.' );
			else
				$mysql->importFileWithPrefix( $fileName, $prefix );
		}
		catch( Exception $e ){
			$this->out( 'Loading import file '.$fileName.' failed: '.$e->getMessage() );
		}
	}

	protected function __onInit(): void
	{
		$this->defaultPath		= $this->client->getConfigPath().'sql/';
	}

	protected function getLatestDump( ?string $path = NULL ): ?string
	{
		$pathConfig	= $this->client->getConfigPath();
		$path		= $path ? rtrim( $path, '/' ).'/' : $pathConfig.'sql/';
		if( !file_exists( $path ) )
			throw new RuntimeException( 'Path is not existing: '.$path );
		$finder		= new Hymn_Tool_LatestFile( $this->client );
		$finder->setAcceptedFileNames( ['latest.sql'] );
		$finder->setFileNamePattern( '/^dump_[0-9:_-]+\.sql$/' );
		$this->client->outVerbose( 'Scanning folder '.$path.'...' );
		return $finder->find( $path );
	}
}
