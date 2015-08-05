<?php
class Hymn_Command_DatabaseDump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		Hymn_Client::out();
		if( !Hymn_Command_DatabaseTest::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$username	= $this->client->getConfig()->database->username;
		$password	= $this->client->getConfig()->database->password;
		$name		= $this->client->getConfig()->database->name;
		$path		= !empty( $arguments[1] ) ? $arguments[1] : '.';
		$path		= rtrim( $path, "/" )."/";
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
