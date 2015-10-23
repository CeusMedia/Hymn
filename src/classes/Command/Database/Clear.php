<?php
class Hymn_Command_Database_Clear extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_DatabaseTest::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$force		= $this->client->arguments->getOption( 'force' );
		$verbose	= $this->client->arguments->getOption( 'verbose' );
		$quiet		= $this->client->arguments->getOption( 'quiet' );

		$dbc	= $this->client->getDatabase();
		$result	= $dbc->query( "SHOW TABLES" );
		$tables	= $result->fetchAll();
		if( !$tables ){
			if( !$quiet )
				Hymn_Client::out( "Database is empty" );
			return;
		}

		if( !$force ){
			if( $quiet )
				return Hymn_Client::out( "Quiet mode needs force mode (-f|--force)" );
			Hymn_Client::out( "Database tables:" );
			foreach( $tables as $table )
				Hymn_Client::out( "- ".$table[0] );
			$answer	= Hymn_Client::getInput( "Do you really want to drop these tables?", NULL, array("y", "n" ) );
			if( $answer !== "y" )
				return;
		}

		foreach( $tables as $table ){
			if( !$quiet && $verbose )
				Hymn_Client::out( "- Drop table '".$table[0]."'" );
			$dbc->query( "DROP TABLE ".$table[0] );
		}
		if( !$quiet )
			Hymn_Client::out( "Database cleared" );
	}

	static public function getTables( $client ){
		return array();
	}
}
