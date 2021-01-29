<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Config extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected 		$questions	= array(
		'driver'	=> array(
			'key'		=> 'driver',
			'label'		=> "- PDO Driver",
			'type'		=> 'string',
			'options'	=> array(),
		),
		array(
			'key'		=> 'host',
			'label'		=> "- Server Host",
			'type'		=> 'string',
		),
		array(
			'key'		=> 'port',
			'label'		=> "- Server Port",
			'type'		=> 'integer',
		),
		array(
			'key'		=> 'name',
			'label'		=> "- Database Name",
			'type'		=> 'string',
		),
		array(
			'key'		=> 'username',
			'label'		=> "- Username",
			'type'		=> 'string',
		),
		array(
			'key'		=> 'password',
			'label'		=> "- Password",
			'type'		=> 'string',
		),
		array(
			'key'		=> 'prefix',
			'label'		=> "- Table Prefix",
			'type'		=> 'string',
		)
	);

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
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

		$this->questions['driver']['options']	= pdo_drivers();//PDO::getAvailableDrivers();
		$connectable	= FALSE;
		do{																							//  do in loop
			foreach( $this->questions as $question ){														//  iterate questions
				$default	= $dba->{$question['key']};												//  shortcut default
				$options	= isset( $question['options'] ) ? $question['options'] : array();		//  realize options
				$input		= new Hymn_Tool_CLI_Question(												//  ask for value
					$this->client,
					$question['label'],
					$question['type'],
					$default,
					$options,
					FALSE
				);
				$dba->{$question['key']}	= $input->ask();											//  assign given value
			}
			$dsn			= $dba->driver.':'.implode( ";", array(									//  render PDO DSN
				"host=".$dba->host,
				"port=".$dba->port,
	//			"dbname=".$this->dba->name,
			) );
			try{																					//  try to connect database server
				if( $dbc = new PDO( $dsn, $dba->username, $dba->password ) ){						//  connection can be established
//					$dbc->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
					if( !$dbc->query( "SHOW DATABASES LIKE '".$dba->name."'" )->fetch() )			//  given database is not existing
						$dbc->query( "CREATE DATABASE `".$dba->name."`" );							//  try to create database
					if( $dbc->query( "SHOW DATABASES LIKE '".$dba->name."'" )->fetch() ){			//  this time database is existing
						$dbc->query( "USE `".$dba->name."`" );										//  switch to database
						$result	= $dbc->query( "SHOW TABLES" );										//  try to read tables in database
						if( is_object( $result ) && is_array( $result->fetchAll() ) )				//  read attempt has been successful
							$connectable	= TRUE;													//  note connectability
					}
				}
				if( !$connectable )																	//  still not connectable
					$this->client->out( 'Database connection failed' );								//  show error message
			}
			catch( Exception $e ){																	//  catch all exceptions
				$this->client->out( 'Database connection error: '.$e->getMessage() );
			}
		}
		while( !$connectable );																		//  repeat until connectable

		$json	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		$json->database	= $dba;
		file_put_contents( Hymn_Client::$fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
	}
}
?>
