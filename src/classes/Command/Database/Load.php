<?php
class Hymn_Command_Database_Load extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_DatabaseTest::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$pathName		= $this->client->arguments->getArgument( 0 );
		if( $pathName && file_exists( $pathName ) ){
			$fileName	= $pathName;
			if( is_dir( $pathName ) )
				$fileName	= $this->getLatestDump( $pathName );
		}
		else
			$fileName		= $this->getLatestDump();
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

	protected function getLatestDump( $path = NULL ){
		$path	= $path ? $path : "config/sql/";
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
