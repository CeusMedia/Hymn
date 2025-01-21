<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Database_Config extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected array $questions	= array(
		'driver'	=> [
			'key'		=> 'driver',
			'label'		=> "- PDO Driver",
			'type'		=> 'string',
			'options'	=> [],
		],
		array(
			'key'		=> 'host',
			'label'		=> "- Server Host",
			'type'		=> 'string',
		),
		array(
			'key'		=> 'port',
			'label'		=> "- Server Port",
			'type'		=> 'string',
			'default'	=> '',
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
	public function run(): void
	{
		$this->denyOnProductionMode();

		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		$config	= $this->client->getConfig();

		if( !isset( $config->database ) )
			$config->database	= new Hymn_Structure_Config_Database();
		$dba	= $config->database;

/*		$dba->driver	= $dba->driver ?: 'mysql';
		$dba->host		= '' === $dba->host ? 'localhost' : $dba->host;
		$dba->port		= '' === $dba->port ? '3306' : $dba->port;
		$dba->name		= '' === $dba->name ? NULL : '';
		$dba->prefix	= $dba->prefix ?? NULL;
		$dba->username	= $dba->username ?? NULL;
		$dba->password	= $dba->password ?? NULL;*/

		$this->questions['driver']['options']	= pdo_drivers();//PDO::getAvailableDrivers();
		$connectable	= FALSE;
		do{																							//  do in loop
			/** @var array{key: string, label: string, type: string, default: NULL, options: array} $question */
			foreach( $this->questions as $question ){												//  iterate questions
				$answer	= $this->askQuestion( $question, $dba->{$question['key']} );				//  ask question
				$dba->{$question['key']}	= $answer;												//  assign given value
			}
			try{
				$connectable	= $this->connectDatabase( $dba ) instanceof PDO;					//  try to connect database server
				if( !$connectable )
					$this->client->out( 'Database connection failed' );						//  show error message
			}
			catch( Exception $e ){																	//  catch all exceptions
				$this->client->out( 'Database connection error: '.$e->getMessage() );
			}
		}
		while( !$connectable );																		//  repeat until connectable

		$json	= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		$json->database	= $dba;
		Hymn_Tool_ConfigFile::save( $json, Hymn_Client::$fileName );
	}

	/**
	 *	@param		array{key: string, label: string, type: string, default: NULL, options: array}	$question
	 *	@param		bool|int|float|string|NULL														$default
	 *	@return		float|bool|int|string
	 */
	protected function askQuestion( array $question, bool|int|float|string|NULL $default = NULL ): float|bool|int|string
	{
		return Hymn_Tool_CLI_Question::getInstance(		//  create input for question answer
			$this->client,
			$question['label'],
			$question['type'],
			$default ?? ( $question['default'] ?? NULL ),
			$question['options'] ?? NULL,
			FALSE
		)->ask();												//  ask for value
	}

	/**
	 *	@param		Hymn_Structure_Config_Database $dba
	 *	@return		PDO|NULL
	 */
	protected function connectDatabase( Hymn_Structure_Config_Database $dba ): ?PDO
	{
		$dsn	= $dba->driver.':'.implode( ";", [									//  render PDO DSN
			"host=".$dba->host,
			"port=".$dba->port,
//			"dbname=".$this->dba->name,
		] );
		$dbc = new PDO( $dsn, $dba->username, $dba->password );

		//  connection can be established
//		$dbc->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
		if( !$dbc->query( "SHOW DATABASES LIKE '".$dba->name."'" )->fetch() )			//  given database is not existing
			$dbc->query( "CREATE DATABASE `".$dba->name."`" );							//  try to create database
		if( $dbc->query( "SHOW DATABASES LIKE '".$dba->name."'" )->fetch() ){			//  this time database is existing
			$dbc->query( "USE `".$dba->name."`" );										//  switch to database
			$result	= $dbc->query( "SHOW TABLES" );										//  try to read tables in database
			if( is_object( $result ) && is_array( $result->fetchAll() ) )				//  read attempt has been successful
				return $dbc;													//  note connection positive status
		}
		return NULL;
	}
}
