<?php
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
	protected $dbc;
	protected $quiet;

	public function __construct( $client, $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->quiet	= $quiet;
		$this->dbc		= $client->setupDatabaseConnection();
	}

	/**
	 *	Configures an installed module by several steps:
	 *	1. set version attribtes: install type, source and date
	 *	2. look for mandatory but empty config pairs in original module
	 *	3. get value for these missing pairs from console if also not set in hymn file
	 *	4. combine values from hymn file and console input and apply to module file
	 *	@access		public
	 *	@param		object		$module			Data object of module to install
	 *	@param		boolean		$verbose		Flag: be verbose
	 *	@return		void
	 */
	public function configure( $module, $verbose = FALSE ){
		$source	= $module->path.'module.xml';
		$target	= $this->config->application->uri.'config/modules/'.$module->id.'.xml';
		@mkdir( dirname( $target ), 0770, TRUE );
		@copy( $source, $target );

		$xml	= file_get_contents( $target );
		$xml	= new SimpleXMLElement( $xml );
		$xml->version->addAttribute( 'install-type',  1 );
		$xml->version->addAttribute( 'install-source', $module->sourceId );
		$xml->version->addAttribute( 'install-date', date( "c" ) );

		$config	= (object) array();																	//  prepare empty hymn module config
		if( isset( $this->config->modules->{$module->id}->config ) )								//  module config is set in hymn file
			$config	= $this->config->modules->{$module->id}->config;								//  get module config from hymn file

		foreach( $xml->config as $nr => $node ){													//  iterate original module config pairs
			$key	= (string) $node['name'];														//  shortcut config pair key
			if( $module->config[$key]->mandatory == "yes" ){										//  config pair is mandatory
				if( $module->config[$key]->type !== "boolean" ){									//  ... and not of type boolean
					if( !strlen( trim( $module->config[$key]->value ) ) ){							//  ... and has no value
						if( !isset( $config->{$key} ) ){											//  ... and is not set in hymn file
							$message	= "  … configure '".$key."'";								//  render console input label
							$values		= $module->config[$key]->values;							//  get suggested values if set

							$value		= Hymn_Client::getInput( $message, NULL, $values, FALSE );	//  get new value from console
							$config->{$key}	= $value;
						}
					}
				}
			}
			if( isset( $config->{$key} ) ){															//  a config value has been set
				$dom = dom_import_simplexml( $node );												//  import DOM node of module file
				$dom->nodeValue = $config->{$key};													//  set new value on DOM node
				if( $verbose && !$this->quiet )														//  verbose mode is on
					Hymn_Client::out( "  … configured ".$key );										//  inform about configures config pair
			}
		}
		$xml->saveXml( $target );																	//  save changed DOM to module file
		@unlink( $this->config->application->uri.'config/modules.cache.serial' );					//  remove modules cache file
	}

	public function copyFiles( $module, $installType = "link", $verbose = FALSE ){
		$fileMap	= $this->prepareModuleFileMap( $module );
		foreach( $fileMap as $source => $target ){
			@mkdir( dirname( $target ), 0770, TRUE );
			$pathNameIn	= realpath( $source );
			$pathOut	= dirname( $target );
			if( $installType === "link" ){
				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_executable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not executable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( file_exists( $target ) ){
					//	if( !$force )
					//		throw new Exception( 'Target file '.$target.' is already existing' );
						@unlink( $target );
					}
					if( !@symlink( $source, $target ) )
						throw new Exception( 'Link of source file '.$source.' is not creatable.' );
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … linked file '.$source );
				}
				catch( Exception $e ){
					Hymn_Client::out( 'Link Error: '.$e->getMessage().'.' );
				}
			}
			else{
				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( !@copy( $source, $target ) )
						throw new Exception( 'Source file '.$source.' could not been copied' );
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … copied file '.$source );
				}
				catch( Exception $e ){
					Hymn_Client::out( 'Copy Error: '.$e->getMessage().'.' );
				}
			}
		}
	}

	protected function executeSql( $sql ){
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

	public function install( $module, $installType = "link", $verbose = FALSE ){
		try{
			if( !$this->quiet )
				Hymn_Client::out( "- Installing module ".$module->id );
			$this->copyFiles( $module, $installType, $verbose );
			$this->configure( $module, $verbose );
			$this->runModuleInstallSql( $module, $verbose );
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= 'Installation of module "%s" failed: %s';
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}

	/**
	 *	Enlist all module files onto a map of source and target files.
	 *	@access		protected
	 *	@param 		object 		$module		Module object
	 *	@return		array
	 */
	protected function prepareModuleFileMap( $module ){
		$pathSource		= $module->path;
		$pathTarget		= $this->config->application->uri;
		$theme			= isset( $this->config->layoutTheme ) ? $this->config->layoutTheme : 'custom';
		$map			= array();
		$skipSources	= array( 'lib', 'styles-lib', 'scripts-lib', 'url' );
		foreach( $module->files as $fileType => $files ){
			foreach( $files as $file ){
				switch( $fileType ){
					case 'files':
						$path	= $file->file;
						$map[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'classes':
					case 'templates':
						$path	= $fileType.'/'.$file->file;
						$map[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'locales':
						$path	= $this->config->paths->locales;
						$source	= $pathSource.'locales/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
					case 'scripts':
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue;
						$path	= $this->config->paths->scripts;
						$source	= $pathSource.'js/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
					case 'styles':
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue;
						$path	= $this->config->paths->themes;
						$source	= $pathSource.'css/'.$file->file;
						$target	= $pathTarget.$path.$theme.'/css/'.$file->file;
						$map[$source]	= $target;
						break;
					case 'images':
						$path	= $this->config->paths->images;
						if( !empty( $file->source) && $file->source === "theme" ){
							$path	= $this->config->paths->themes;
							$path	= $path.$theme."/img/";
						}
						$source	= $pathSource.'img/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
				}
			}
		}
		return $map;
	}

	/**
	 *	Removed installed files of module.
	 *	@access		protected
	 *	@param 		object 		$module		Module object
	 *	@param 		boolean 	$verbose	Flag: be verbose
	 *	@return		void
	 *	@throws		RuntimeException		if target file is not readable
	 *	@throws		RuntimeException		if target file is not writable
	 */
	protected function removeFiles( $module, $verbose = FALSE ){
		$fileMap	= $this->prepareModuleFileMap( $module );										//  get list of installed module files
		foreach( $fileMap as $source => $target ){													//  iterate file list
			if( !is_readable( $target ) )															//  if installed file is not readable
				throw new RuntimeException( 'Target file '.$target.' is not readable' );			//  throw exception
			if( !is_writable( $target ) )															//  if installed file is not writable
				throw new RuntimeException( 'Target file '.$target.' is not removable' );			//  throw exception
			@unlink( $target );																		//  remove installed file
			if( $verbose && !$this->quiet )															//  be verbose
				Hymn_Client::out( '  … removed file '.$target );									//  print note about removed file
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

	public function uninstall( $module, $verbose = FALSE ){
		try{
			if( !$this->quiet )
				Hymn_Client::out( "- Uninstalling module ".$module->id );
			$this->removeFiles( $module, $verbose );												//  remove module files
			@unlink( $this->config->application->uri.'config/modules/'.$module->id );				//  remove module configuration file
			@unlink( $this->config->application->uri.'config/modules.cache.serial' );				//  remove modules cache file
			$this->runModuleUninstallSql( $module, $verbose );
			return TRUE;
		}
		catch( Exception $e ){
			throw new RuntimeException( 'Uninstallation of module "'.$module->id.'" failed: '.$e->getMessage(), 0, $e );
		}
	}
}
