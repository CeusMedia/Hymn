<?php
/**
 *	Manager for module SQL scripts.
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Manager for module SQL scripts.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_SQL
{
	protected Hymn_Client $client;
	protected object $flags;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
		$this->flags	= (object) [
			'quiet'			=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'dry'			=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'verbose'		=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
			'noDatabase'	=> $this->client->flags & Hymn_Client::FLAG_NO_DB,
		];
	}

	/**
	 *	Return list of SQL statements to execute on module update.
	 *	Returns empty list if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module		$module				Object of library module to install
	 *	@return		array<Hymn_Structure_Module_SQL>		List of SQL statements to execute on module installation
	 */
	public function getModuleInstallSql( Hymn_Structure_Module $module ): array
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return [];																				//  quit here and return empty list
		if( !isset( $module->sql ) || !count( $module->sql ) )										//  module has no SQL scripts
			return [];																				//  quit here and return empty list
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= 0;																			//  init reached version
		$scripts	= [];																			//  prepare empty list for collected scripts

		foreach( $module->sql as $sql )																//  first run: install
			if( $sql->event == "install" && trim( $sql->sql ) )										//  is an install script
				if( $sql->version == "final" || !$sql->version )									//  is final install script
					if( $sql->type === $driver || $sql->type == "*" )								//  database driver is matching or general
						$scripts[]	= $sql;															//  append script for execution

		if( !$scripts ){
			foreach( $module->sql as $sql ){														//  first run: install
				if( version_compare( $version, $module->version->current, "<" ) ){			//  reached version is not final
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
				if( version_compare( $version, $module->version->current, "<" ) ){			//  reached version is not final
					if( $sql->type === $driver || $sql->type == "*" ){								//  database driver is matching or general
						if( $sql->event == "update" && trim( $sql->sql ) ){							//  is an update script
							if( isset( $sql->version ) ){											//  script version is set
								if( version_compare( $version, $sql->version, "<" ) ){		//  script version is greater than reached version
									$version	= $sql->version;									//  set reached version to script version
									$scripts[]	= $sql;												//  append script for execution
								}
							}
						}
					}
				}
			}
		}
		return $scripts;
	}

	/**
	 *	Reads module SQL scripts and returns list of uninstall scripts.
	 *	Returns empty list if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		object{sql: object, version: ?string}		$installedModule	Object of locally installed module
	 *	@return		array		List of SQL statements to execute on module uninstallation
	 */
	public function getModuleUninstallSql( object $installedModule ): array
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return [];																			//  quit here and return empty list
		if( !isset( $installedModule->sql ) || !count( $installedModule->sql ) )					//  module has no SQL scripts
			return [];																			//  quit here and return empty list
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= 0;																			//  init reached version
		$scripts	= [];																		//  prepare empty list for collected scripts

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
		return $scripts;
	}

	/**
	 *	Return list of SQL statements to execute on module update.
	 *	Returns empty list if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module		$installedModule	Object of locally installed module
	 *	@param		Hymn_Structure_Module		$module				Object of library module to update to
	 *	@return		array<string,Hymn_Structure_Module_SQL>		List of SQL statements to execute on module update
	 */
	public function getModuleUpdateSql( Hymn_Structure_Module $installedModule, Hymn_Structure_Module $module ): array
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return [];																			//  quit here and return empty list
		if( !isset( $module->sql ) || !count( $module->sql ) )										//  module has no SQL scripts
			return [];																			//  quit here and return empty list
		$driver		= $this->checkDriver();															//  check database connection and get PDO driver
		$version	= $installedModule->version->current;											//  start by version of currently installed module
		$scripts	= [];																		//  prepare empty list for collected scripts

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
		return $scripts;
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	Does nothing if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module		$module			Object of module to install
	 *	@throws		RuntimeException			if target file is not readable
	 */
	public function runModuleInstallSql( Hymn_Structure_Module $module ): void
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return;
		$scripts	= $this->getModuleInstallSql( $module );
		foreach( $scripts as $script ){																//  iterate collected scripts
			if( $this->flags->verbose && !$this->flags->quiet ){									//  be verbose
				$msg	= "    … apply database script on %s at version %s";
				$this->client->out( sprintf( $msg, $script->event, $script->version ) );
			}
			if( !$this->flags->dry )																//  this is a dry run
				$this->executeSql( $script->sql );													//  execute collected SQL script
		}
	}

	/**
	 *	Reads module SQL scripts and executes uninstall scripts.
	 *	Does nothing if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		object		$installedModule	Object of locally installed module
	 *	@return		void
	 */
	public function runModuleUninstallSql( object $installedModule ): void
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return;
		$scripts	= $this->getModuleUninstallSql( $installedModule );

		foreach( $scripts as $script ){
			if( $this->flags->verbose && !$this->flags->quiet ){									//  be verbose
				$msg	= "    … apply database script on %s at version %s";
				$this->client->out( sprintf( $msg, $script->event, $script->version ) );
			}
			if( !$this->flags->dry )																//  not a dry run
				$this->executeSql( $script->sql );													//  execute collected SQL script
		}
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	Does nothing if flag 'db' is set to 'no'.
	 *	@access		public
	 *	@param		object		$installedModule	Object of locally installed module
	 *	@param		object		$module				Object of library module to update to
	 *	@param		boolean		$tryMode			Flag: force no changes, only try (default: no)
	 *	@return		void
	 *	@todo		implement SQL checks or in try mode nothing is done
	 *	@todo		also maybe add transactions
	 */
	public function runModuleUpdateSql( object $installedModule, object $module, bool $tryMode = FALSE ): void
	{
		if( $this->flags->noDatabase )																//  flag to skip database operations is set
			return;
		$scripts	= $this->getModuleUpdateSql( $installedModule, $module );
		foreach( $scripts as $script ){																//  iterate found ordered update scripts
			if( $this->flags->verbose && !$this->flags->quiet && !$tryMode ){						//  be verbose
				$msg	= "  … apply database script on %s at version %s";							//  ...
				$this->client->out( sprintf( $msg, $script->event, $script->version ) );			//  ...
			}
			if( !( $this->flags->dry || $tryMode ) ){												//  not a dry or try run
				try{
					$this->executeSql( $script->sql );												//  execute collected SQL script
				}
				catch( Exception $e ){
					$this->client->out( 'Problem occured: '.$e->getMessage() );						//  ...
				}
			}
		}
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Returns PDO driver used or to be used for database connection.
	 *	@access		protected
	 *	@return		string							PDO driver used by database connection
	 *	@throws		\RuntimeException				if no database connection driver is set
	 */
	protected function checkDriver(): string
	{
		$dbc	= $this->client->getDatabase();														//  shortcut database resource of client
		$driver	= $dbc->getConfig( 'driver' );														//  get configured database driver
		if( !$driver )																				//  no database driver set
			throw new \RuntimeException( 'No database connection driver set' );						//  quit with exception
		return $driver;																				//  otherwise return configured driver
	}

	/**
	 *	Executes single SQL script against database connection.
	 *	@access		protected
	 *	@param		string 		$sql				SQL script to execute
	 *	@return		void
	 *	@throws		RuntimeException				if execution fails
	 */
	protected function executeSql( string $sql )
	{
		$dbc		= $this->client->getDatabase();
		$dbc->connect( TRUE );
		$prefix		= $dbc->getConfig( 'prefix' );
		$lines		= explode( "\n", trim( $sql ) );
		$statements = [];
		$buffer		= [];
		while( count( $lines ) ){
			$line = array_shift( $lines );
			if( !trim( $line ) )
				continue;
			$buffer[]	= $dbc->applyTablePrefixToSql( trim( $line ), $prefix );
			if( str_ends_with( trim( $line ), ';' ) ){
				$statements[]	= join( "\n", $buffer );
				$buffer			= [];
			}
			if( !count( $lines ) && $buffer )
				$statements[]	= join( "\n", $buffer ).';';
		}
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
}
