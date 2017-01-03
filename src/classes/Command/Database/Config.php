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
class Hymn_Command_Database_Config extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
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
				$input		= Hymn_Client::getInput( $question->label, 'string', $default, $options, FALSE );	//  ask for value
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
