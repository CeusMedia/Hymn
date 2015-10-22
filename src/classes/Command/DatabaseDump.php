<?php
class Hymn_Command_DatabaseDump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_DatabaseTest::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$path		= $this->client->arguments->getArgument( 0 );
		$path		= $path ? $path : 'config/sql/';
		$path		= rtrim( $path, "/" )."/";
		if( !file_exists( $path ) )
			$path	= "./";

		$username	= $this->client->getDatabaseConfiguration( 'username' );
		$password	= $this->client->getDatabaseConfiguration( 'password' );
		$name		= $this->client->getDatabaseConfiguration( 'name' );
		$fileName	= $path."dump_".date( "Y-m-d_H:i:s" ).".sql";
		$command	= "mysqldump -u%s -p%s %s > %s";
		$command	= sprintf( $command, $username, $password, $name, $fileName );
		if( $path )
			exec( "mkdir -p ".$path );
		exec( $command );
		return Hymn_Client::out( "Database dumped to ".$fileName );
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
}
