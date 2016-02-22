<?php
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
	protected $dbc;
	protected $quiet;
	protected $files;
	protected $sql;

	public function __construct( $client, $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->quiet	= $quiet;
		$this->dbc		= $client->setupDatabaseConnection();
		$this->files	= new Hymn_Module_Files( $client, $quiet );
		$this->sql		= new Hymn_Module_SQL( $client, $quiet );
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

	public function install( $module, $installType = "link", $verbose = FALSE ){
		try{
			if( !$this->quiet )
				Hymn_Client::out( "- Installing module ".$module->id );
			$this->files->copyFiles( $module, $installType, $verbose );								//  copy module files
			$this->configure( $module, $verbose );													//  configure module
			$this->sql->runModuleInstallSql( $module, $verbose );									//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= 'Installation of module "%s" failed: %s';
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}

	public function uninstall( $module, $verbose = FALSE ){
		try{
			if( !$this->quiet )
				Hymn_Client::out( "- Uninstalling module ".$module->id );
			$this->files->removeFiles( $module, $verbose );											//  remove module files
			@unlink( $this->config->application->uri.'config/modules/'.$module->id.'.xml' );		//  remove module configuration file
			@unlink( $this->config->application->uri.'config/modules.cache.serial' );				//  remove modules cache file
			$this->sql->runModuleUninstallSql( $module, $verbose );									//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			throw new RuntimeException( 'Uninstallation of module "'.$module->id.'" failed: '.$e->getMessage(), 0, $e );
		}
	}

	public function update( $module, $verbose = FALSE ){
		try{
			if( !$this->quiet )
				Hymn_Client::out( "- Updating module ".$module->id );
			$this->files->removeFiles( $module, $verbose );											//  remove module files
			@unlink( $this->config->application->uri.'config/modules/'.$module->id.'.xml' );		//  remove module configuration file
			@unlink( $this->config->application->uri.'config/modules.cache.serial' );				//  remove modules cache file
			$this->files->copyFiles( $module, $installType, $verbose );								//  copy module files
			$this->configure( $module, $verbose );													//  configure module
			$this->sql->runModuleUpdateSql( $module, $verbose );									//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= 'Update of module "%s" failed: %s';
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}
}
