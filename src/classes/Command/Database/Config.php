<?php
class Hymn_Command_Database_Config extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config	= $this->client->getConfig();

		if( !isset( $config->database ) )
			$config->database	= (object) array();
		$dba	= $config->database;

		$dba->driver	= isset( $dba->driver ) ? $dba->driver : "mysql";
		$dba->host		= isset( $dba->host ) ? $dba->host : "localhost";
		$dba->port		= isset( $dba->port ) ? $dba->port : "3306";
		$dba->name		= isset( $dba->name ) ? $dba->name : NULL;
		$dba->prefix	= isset( $dba->prefix ) ? $dba->prefix : NULL;
		$dba->username	= isset( $dba->username ) ? $dba->username : NULL;
		$dba->password	= isset( $dba->password ) ? $dba->password : NULL;

		$drivers		= PDO::getAvailableDrivers();

		$questions	= array(
			(object) array(
				'key'		=> 'driver',
				'label'		=> "- PDO Driver",
				'options'	=> $drivers
			),
			(object) array(
				'key'		=> 'host',
				'label'		=> "- Server Host"
			),
			(object) array(
				'key'		=> 'port',
				'label'		=> "- Server Port"
			),
			(object) array(
				'key'		=> 'name',
				'label'		=> "- Database Name"
			),
			(object) array(
				'key'		=> 'username',
				'label'		=> "- Username"
			),
			(object) array(
				'key'		=> 'password',
				'label'		=> "- Password"
			),
			(object) array(
				'key'		=> 'prefix',
				'label'		=> "- Table Prefix"
			)
		);

		$connectable	= FALSE;
		do{																							//  do in loop
			foreach( $questions as $question ){														//  iterate questions
				$default	= $dba->{$question->key};												//  shortcut default
				$options	= isset( $question->options ) ? $question->options : array();			//  realize options
				$input		= Hymn_Client::getInput( $question->label, $default, $options, FALSE );	//  ask for value
				$dba->{$question->key}	= $input;													//  assign given value
			}
			$dsn	= $dba->driver.":host=".$dba->host.";port=".$dba->port.";dbname=".$dba->name;	//  render PDO DSN
			try{																					//  try to connect database
				if( $dbc = @new PDO( $dsn, $dba->username, $dba->password ) ){						//  connection can be established
					$result	= $dbc->query( "SHOW TABLES" );											//  query for tables
					if( is_object( $result ) && is_array( $result->fetchAll() ) )					//  query has been successful
						$connectable	= TRUE;														//  note connectability for loop break
				}
				if( !$connectable )																	//  still not connectable
					Hymn_Client::out( 'Database connection failed' );								//  show error message
			}
			catch( Exception $e ){																	//  catch all exceptions
				Hymn_Client::out( 'Database connection error: '.$e->getMessage() );
			}
		}
		while( !$connectable );																		//  repeat until connectable

		$json	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		$json->database	= $dba;
		file_put_contents( Hymn_Client::$fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
	}
}
?>
