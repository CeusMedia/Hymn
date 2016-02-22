<?php
class Hymn_Module_SQL{

	protected $client;
//	protected $config;
	protected $quiet;

	public function __construct( $client, $quiet = FALSE ){
		$this->client	= $client;
//		$this->config	= $this->client->getConfig();
		$this->quiet	= $quiet;
	}

	protected function executeSql( $sql ){
		$dbc		= $this->client->setupDatabaseConnection();
//		$dbc		= $this->client->getDatabase();
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
	 *	@access		protected
	 *	@param 		object 		$module		Module object
	 *	@param 		boolean 	$verbose	Flag: be verbose
	 *	@return		void
	 *	@throws		RuntimeException		if target file is not readable
	 */
	protected function runModuleInstallSql( $module, $verbose ){
		if( isset( $module->sql ) && count( $module->sql ) ){										//  module has SQL scripts
			if( !$this->client->getDatabase() )														//  database connection is not established yet
				$this->client->setupDatabaseConnection( TRUE );										//  setup database connection
			$driver	= $this->client->getDatabaseConfiguration( 'driver' );							//  get database driver
			if( !$driver ){																			//  no database driver set
				$msg	= 'Cannot install SQL of module "%s": No database connection available';
				throw new RuntimeException( sprintf( $msg, $module->id ) );
			}
			$version	= 0;																		//  init reached version
			$scripts	= array();																	//  prepare empty list for collected scripts
			foreach( $module->sql as $sql ){														//  first run: install
				if( $version !== "final" && $version < $module->version ){							//  reached version is not final
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
				if( $version !== "final" && $version < $module->version ){							//  reached version is not final
					if( $sql->type === $driver || $sql->type == "*" ){								//  database driver is matching or general
						if( $sql->event == "update" && trim( $sql->sql ) ){							//  is an update script
							if( isset( $sql->version ) ){											//  script version is set
								if( version_compare( $version, $sql->version ) > 0 ){				//  script version is greater than reached version
									$version	= $sql->version;									//  set reached version to script version
									$scripts[]	= $sql;												//  append script for execution
								}
							}
						}
					}
				}
			}
			foreach( $scripts as $script ){															//  iterate collected scripts
				if( $verbose && !$this->quiet ){													//  be verbose
					$msg	= "    … apply database script on %s at version %s";
					Hymn_Client::out( sprintf( $msg, $script->event, $script->version ) );
				}
				$this->executeSql( $script->sql );													//  execute collected SQL script
			}
		}
	}

	/**
	 *	Reads module SQL scripts and executes install and update scripts.
	 *	@access		protected
	 *	@param 		object 		$module		Module object
	 *	@param 		boolean 	$verbose	Flag: be verbose
	 *	@return		void
	 */
	protected function runModuleUninstallSql( $module, $verbose ){
		if( isset( $module->sql ) && count( $module->sql ) ){										//  module has SQL scripts
			if( !$this->client->getDatabase() )														//  database connection is not established yet
				$this->client->setupDatabaseConnection( TRUE );										//  setup database connection
			$driver	= $this->client->getDatabaseConfiguration( 'driver' );							//  get database driver
			if( !$driver ){																			//  no database driver set
				$msg	= 'Cannot install SQL of module "%s": No database connection available';
				throw new RuntimeException( sprintf( $msg, $module->id ) );
			}
			foreach( $module->sql as $sql ){														//  iterate SQL scripts
				if( $sql->type === $driver || $sql->type == "*" ){									//  database driver is matching or general
					if( $sql->event == "uninstall" && trim( $sql->sql ) ){							//  is an uninstall script
						if( $verbose && !$this->quiet ){											//  be verbose
							$msg	= "    … apply database script on %s at version %s";
							Hymn_Client::out( sprintf( $msg, $script->event, $script->version ) );
						}
						$this->executeSql( $script->sql );											//  execute collected SQL script
						break;
					}
				}
			}
		}
	}
}
