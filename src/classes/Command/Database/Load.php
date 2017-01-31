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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Load extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$dry			= $this->client->arguments->getOption( 'dry' );
		$verbose		= $this->client->arguments->getOption( 'verbose' );
		$pathName		= $this->client->arguments->getArgument( 0 );
		if( $pathName && file_exists( $pathName ) ){
			if( is_dir( $pathName ) )
				$fileName	= $this->getLatestDump( $pathName, TRUE );
			else{
				$fileName	= $pathName;
			}
		}
		else
			$fileName		= $this->getLatestDump( NULL, TRUE );
		if( !( $fileName && file_exists( $fileName ) ) )
			return Hymn_Client::out( "No loadable database file found." );

		try{
			if( ( $content = @file_get_contents( $fileName ) ) === FALSE )
				throw new RuntimeException( 'Missing read access to SQL script' );

			$host		= $this->client->getDatabaseConfiguration( 'host' );						//  get server host from config
			$port		= $this->client->getDatabaseConfiguration( 'port' );						//  get server port from config
			$username	= $this->client->getDatabaseConfiguration( 'username' );					//  get username from config
			$password	= $this->client->getDatabaseConfiguration( 'password' );					//  get password from config
			$name		= $this->client->getDatabaseConfiguration( 'name' );						//  get database name from config
			$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );						//  get table prefix from config
			$fileSize	= Hymn_Tool_FileSize::get( $fileName );										//  format file size

			$content	= str_replace( "<%?prefix%>", $prefix, $content );							//  replace table prefix placeholder

			//  @todo	user PHP temp file functions to avoid write access problems in app root folder
			$tempName	= $fileName.".tmp";
 			if( @file_put_contents( $tempName, $content ) === FALSE )								//  try to save manipulated script as temp file
				throw new RuntimeException( 'Missing write access to SQL scripts path' );

			$command	= call_user_func_array( "sprintf", array(									//  call sprintf with arguments list
				"mysql -h%s -P%s -u%s -p%s %s < %s",												//  command to replace within
				escapeshellarg( $host ),															//  configured host as escaped shell arg
				escapeshellarg( $port ),															//  configured port as escaped shell arg
				escapeshellarg( $username ),														//  configured username as escaped shell arg
				escapeshellarg( $password ),														//  configured pasword as escaped shell arg
				escapeshellarg( $name ),															//  configured database name as escaped shell arg
				escapeshellarg( $tempName ),														//  temp file name as escaped shell arg
			) );

			if( $verbose ){
				Hymn_Client::out( "Import file:  ".$fileName );
				Hymn_Client::out( "File size:    ".$fileSize );
				Hymn_Client::out( "DB Server:    ".$host."@".$port );
				Hymn_Client::out( "Database:     ".$name );
				Hymn_Client::out( "Table prefix: ".( $prefix ? $prefix : "- (none)" ) );
				Hymn_Client::out( "Access as:    ".$username );
			}
			if( $dry )
				return Hymn_Client::out( "Database setup okay - import itself not simulated." );

			Hymn_Client::out( "Importing ".$fileName." (".$fileSize.") ..." );
			exec( $command );
			unlink( $tempName );
//			return Hymn_Client::out( "Database loaded from ".$fileName );
		}
		catch( Exception $e ){
			Hymn_Client::out( "Importing ".$fileName." failed: ".$e->getMessage() );
		}
	}

	static public function test( $client ){
		$config		= $client->getConfig();
		$client->setupDatabaseConnection();
		$dbc		= $client->getDatabase();
		if( $dbc ){
			$result	= $dbc->query( "SHOW TABLES" );
			if( is_object( $result ) && is_array( $result->fetchAll() ) )
				return TRUE;
		}
		return FALSE;
	}

	protected function getLatestDump( $path = NULL, $verbose = FALSE ){
		$path	= $path ? $path : "config/sql/";
		if( $verbose )
			Hymn_Client::out( "Scanning folder ".$path."..." );
		if( file_exists( $path ) ){
			$list	= array();
			$index	= new DirectoryIterator( $path );
			foreach( $index as $entry ){
				if( $entry->isDir() || $entry->isDot() )
					continue;
				if( !preg_match( "/^dump_.[0-9:_-]+\.sql$/", $entry->getFilename() ) )
					continue;
				$key		= str_replace( array( '_', '-' ), '_', $entry->getFilename() );
				$list[$key]	= $entry->getFilename();
			}
			krsort( $list );
			if( $list ){
				return $path.array_shift( $list );
			}
		}
		return NULL;
	}
}
