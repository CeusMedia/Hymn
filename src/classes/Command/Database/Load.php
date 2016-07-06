<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Load extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

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

		$username	= $this->client->getDatabaseConfiguration( 'username' );
		$password	= $this->client->getDatabaseConfiguration( 'password' );
		$name		= $this->client->getDatabaseConfiguration( 'name' );
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );

		$tempName	= $fileName.".tmp";
		try{
			if( ( $content = @file_get_contents( $fileName ) ) === FALSE )
				throw new RuntimeException( 'Missing read access to SQL script' );
			$content	= str_replace( "<%?prefix%>", $prefix, $content );
			if( @file_put_contents( $tempName, $content ) === FALSE )
				throw new RuntimeException( 'Missing write access to SQL scripts path' );

			$command	= "mysql -u%s -p%s %s < %s";
			$command	= sprintf( $command, $username, $password, $name, $tempName );

			$fileSize	= Hymn_Tool_FileSize::get( $fileName );
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
