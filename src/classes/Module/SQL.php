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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_SQL{

	protected $client;
//	protected $config;
	protected $quiet;

	public function __construct( Hymn_Client $client, $quiet = FALSE ){
		$this->client	= $client;
//		$this->config	= $this->client->getConfig();
		$this->quiet	= $quiet;
	}

	/**
	 *	Checks for database connection and returns used PDO driver.
	 *	@access		protected
	 *	@return		string							PDO driver used by database connection
	 *	@throws		RuntimeException				if no database connection is available
	 */
	protected function checkDriver(){
		if( !$this->client->getDatabase() )															//  database connection is not established yet
			$this->client->setupDatabaseConnection( TRUE );											//  setup database connection
		$driver	= $this->client->getDatabaseConfiguration( 'driver' );								//  get database driver
		if( !$driver )																				//  no database driver set
			throw new RuntimeException( 'No database connection available' );
		return $driver;
	}

	/**
	 *	Executes single SQL script against database connection.
	 *	@access		protected
	 *	@param 		string 		$sql				SQL script to execute
	 *	@return		void
	 *	@throws		RuntimeException				if execution fails
	 */
	protected function executeSql( $sql ){
//		$dbc		= $this->client->setupDatabaseConnection();
		$dbc		= $this->client->getDatabase();
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );
		$lines		= explode( "\n", trim( $sql ) );
		$statements = array();
		$buffer		= array();
		while( count( $lines ) ){
			$line = array_shift( $lines );
			if( !trim( $line ) )
				continue;
			$buffer[]	= str_replace( "<%?prefix%>", $prefix, trim( $line ) );
			if( preg_match( '/;$/', trim( $line ) ) ){
				$statements[]	= join( "\n", $buffer );
				$buffer			= array();
			}
			if( !count( $lines ) && $buffer )
				$statements[]	= join( "\n", $buffer ).';';
		}
		$errors	= 0;
		foreach( $statements as $statement ){
			try{
				$result	= $dbc->exec( $statement );
				if( $result	=== FALSE )
					throw new RuntimeException( 'SQL execution failed for: '.$statement );
			}
			catch( Exception $e ){
				error_log( date( "Y-m-d H:i:s" ).' '.$e->getMessage()."\n", 3, 'hymn.db.error.log' );
				throw new RuntimeException( 'SQL error - see hymn.db.error.log' );
			}
		}
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	@access		public
	 *	@param 		object 		$module				Object of nodule to install
	 *	@param 		boolean 	$verbose			Flag: be verbose
	 *	@param		boolean		$dry				Flag: dry run mode - simulation only
	 *	@return		void
	 *	@throws		RuntimeException		if target file is not readable
	 */
	public function runModuleInstallSql( $module, $verbose, $dry = FALSE ){
		if( !isset( $module->sql ) || !count( $module->sql ) )										//  module has no SQL scripts
			return;																					//  quit here
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= 0;																			//  init reached version
		$scripts	= array();																		//  prepare empty list for collected scripts

		foreach( $module->sql as $sql )																//  first run: install
			if( $sql->event == "install" && trim( $sql->sql ) )										//  is an install script
				if( $sql->version == "final" || !$sql->version )									//  is final install script
					if( $sql->type === $driver || $sql->type == "*" )								//  database driver is matching or general
						$scripts[]	= $sql;															//  append script for execution

		if( !$scripts ){
			foreach( $module->sql as $sql ){														//  first run: install
				if( version_compare( $version, $module->version, "<" ) ){							//  reached version is not final
					if( $sql->type === $driver || $sql->type == "*" ){								//  database driver is matching or general
						if( $sql->event == "install" && trim( $sql->sql ) ){						//  is an install script
							if( isset( $sql->version ) )											//  script version is set
								$version	= $sql->version;										//  set reached version to script version
							$scripts[]	= $sql;														//  append script for execution
						}
					}
				}
			}
			foreach( $module->sql as $sql ){														//  second run: update
				if( version_compare( $version, $module->version, "<" ) ){							//  reached version is not final
					if( $sql->type === $driver || $sql->type == "*" ){								//  database driver is matching or general
						if( $sql->event == "update" && trim( $sql->sql ) ){							//  is an update script
							if( isset( $sql->version ) ){											//  script version is set
								if( version_compare( $version, $sql->version, "<" ) ){				//  script version is greater than reached version
									$version	= $sql->version;									//  set reached version to script version
									$scripts[]	= $sql;												//  append script for execution
								}
							}
						}
					}
				}
			}
		}
		if( $dry )																					//  this is a dry run
			return;																					//  do not execute anything
		foreach( $scripts as $script ){																//  iterate collected scripts
			if( $verbose && !$this->quiet ){														//  be verbose
				$msg	= "    … apply database script on %s at version %s";
				Hymn_Client::out( sprintf( $msg, $script->event, $script->version ) );
			}
			$this->executeSql( $script->sql );														//  execute collected SQL script
		}
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	@access		public
	 *	@param 		object 		$installedModule	Object of locally installed module
	 *	@param 		boolean 	$verbose			Flag: be verbose
	 *	@param		boolean		$dry				Flag: dry run mode - simulation only
	 *	@return		void
	 */
	public function runModuleUninstallSql( $installedModule, $verbose, $dry = FALSE ){
		if( !isset( $installedModule->sql ) || !count( $installedModule->sql ) )					//  module has no SQL scripts
			return;																					//  quit here
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= 0;																			//  init reached version
		$scripts	= array();																		//  prepare empty list for collected scripts

		foreach( $installedModule->sql as $sql )													//  first run: uninstall
			if( $sql->event == "uninstall" && trim( $sql->sql ) )									//  is an uninstall script
				if( $sql->version == "final" )														//  is final uninstall script
					if( $sql->type === $driver || $sql->type == "*" )								//  database driver is matching or general
						$scripts[]	= $sql;															//  append script for execution

		if( !$scripts ){
			foreach( $installedModule->sql as $sql ){												//  iterate SQL scripts
				if( version_compare( $version, $installedModule->version, "<" ) ){					//  reached version is not final
					if( $sql->type === $driver || $sql->type == "*" ){								//  database driver is matching or general
						if( $sql->event == "uninstall" && trim( $sql->sql ) ){						//  is an uninstall script
							$scripts[]	= $sql;														//  append script for execution
							if( $sql->version == "final" )											//  is final uninstall script
								$version	= $installedModule->version;							//  set version to module version to skip others
						}
					}
				}
			}
		}

		foreach( $scripts as $script ){
			if( $verbose && !$this->quiet ){														//  be verbose
				$msg	= "    … apply database script on %s at version %s";
				Hymn_Client::out( sprintf( $msg, $script->event, $script->version ) );
			}
			if( !$dry )																				//  not a dry run
				$this->executeSql( $script->sql );													//  execute collected SQL script
		}
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	@access		public
	 *	@param 		object 		$installedModule	Object of locally installed module
	 *	@param 		object 		$module				Object of library module to update to
	 *	@param 		boolean 	$verbose			Flag: be verbose
	 *	@param		boolean		$dry				Flag: dry run mode - simulation only
	 *	@return		void
	 */
	public function runModuleUpdateSql( $installedModule, $module, $verbose, $dry = FALSE ){
		if( !isset( $module->sql ) || !count( $module->sql ) )										//  module has no SQL scripts
			return;																					//  quit here
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= $installedModule->version;													//  start by version of currently installed module
		$scripts	= array();																		//  prepare empty list for collected scripts

		foreach( $module->sql as $sql ){															//  iterate SQL scripts
			if( $sql->event == "update" && trim( $sql->sql ) ){										//  is an update script
				if( $sql->type === $driver || $sql->type == "*" ){									//  database driver is matching or general
					if( version_compare( $sql->version, $version,">" ) ){							//  script version is prior to currently installed version
						$scripts[$sql->version]	= $sql;												//  append script for execution
					}
				}
			}
		}
		uksort( $scripts, 'version_compare' );														//  sort update scripts by version

		foreach( $scripts as $script ){																//  iterate found ordered update scripts
			if( $verbose && !$this->quiet ){														//  be verbose
				$msg	= "    … apply database script on %s at version %s";						//  ...
				Hymn_Client::out( sprintf( $msg, $script->event, $script->version ) );				//  ...
			}
			if( !$dry )																				//  not a dry run
				$this->executeSql( $script->sql );													//  execute collected SQL script
		}
	}
}
