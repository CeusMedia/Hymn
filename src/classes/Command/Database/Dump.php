<?php
class Hymn_Command_Database_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$fileName	= $this->client->arguments->getArgument( 0 );
		if( !preg_match( "/[a-z0-9]/i", $fileName ) )												//  arguments has not valid value
			$fileName	= 'config/sql/';															//  set default path
		if( substr( $fileName, -1 ) == "/" )														//  given argument is a path
			$fileName	= $fileName."dump_".date( "Y-m-d_H:i:s" ).".sql";							//  generate stamped file name
		if( dirname( $fileName) )																	//  path is not existing
			exec( "mkdir -p ".dirname( $fileName ) );												//  create path

		$dbc		= $this->client->getDatabase();
		$username	= $this->client->getDatabaseConfiguration( 'username' );
		$password	= $this->client->getDatabaseConfiguration( 'password' );
		$name		= $this->client->getDatabaseConfiguration( 'name' );
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );

		$tables		= array();
		if( $prefix )
			foreach( $dbc->query( "SHOW TABLES LIKE '".$prefix."%'" ) as $table )
				$tables[]	= $table[0];
		$tables	= join( ' ', $tables );

		$command	= "mysqldump -u%s -p%s %s %s > %s";
		$command	= sprintf( $command, $username, $password, $name, $tables, $fileName );
		exec( $command );

		/*  --  REPLACE PREFIX  --  */
		$regExp		= "@(EXISTS|FROM|INTO|TABLE|TABLES|for table)( `)(".$prefix.")(.+)(`)@U";		//  build regular expression
		$callback	= array( $this, '_callbackReplacePrefix' );										//  create replace callback
		$contents	= explode( "\n", file_get_contents( $fileName ) );								//  read raw dump file
		foreach( $contents as $nr => $content )														//  iterate lines
			$contents[$nr] = preg_replace_callback( $regExp, $callback, $content );					//  replace prefix by placeholder
		file_put_contents( $fileName, implode( "\n", $contents ) );									//  save final dump file

		return Hymn_Client::out( "Database dumped to ".$fileName );
	}

	protected function _callbackReplacePrefix( $matches ){
		if( $matches[1] === 'for table' )
			return $matches[1].$matches[2].$matches[4].$matches[5];
		return $matches[1].$matches[2].'<%?prefix%>'.$matches[4].$matches[5];
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
