<?php
class Hymn_Command_DatabaseTest extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( self::test( $this->client ) )
			return Hymn_Client::out( "Database can be connected." );
		return Hymn_Client::out( "Database is NOT connected." );
	}

	static public function test( $client ){
		$config		= $client->getConfig();
		if( !$client->getDatabase() )
			$client->setupDatabaseConnection( TRUE );
		$dbc		= $client->getDatabase();
		$result	= $dbc->query( "SHOW TABLES" );
		if( is_object( $result ) && is_array( $result->fetchAll() ) )
			return TRUE;
		return FALSE;
	}
}
