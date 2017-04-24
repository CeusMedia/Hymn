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
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
	protected $dbc;
	protected $quiet;
	protected $files;
	protected $sql;
	protected $isLiveCopy	= FALSE;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->quiet	= $quiet;
		$this->dbc		= $client->setupDatabaseConnection();
		$this->files	= new Hymn_Module_Files( $client, $quiet );
		$this->sql		= new Hymn_Module_SQL( $client, $quiet );
		$this->app		= $this->config->application;												//  shortcut to application config

/*		if( isset( $this->app->installMode ) )
			Hymn_Client::out( "Install Mode: ".$this->app->installMode );
		if( isset( $this->app->installType ) )
			Hymn_Client::out( "Install Type: ".$this->app->installType );*/

		if( isset( $this->app->installType ) && $this->app->installType === "copy" )				//  installation is a copy
			if( isset( $this->app->installMode ) && $this->app->installMode === "live" )			//  installation has been for live environment
				$this->isLiveCopy	= TRUE;
		if( $this->isLiveCopy ){
			Hymn_Client::out( "" );
			Hymn_Client::out( "ATTENTION: This build is a live installation in copy mode." );
			Hymn_Client::out( "There is not uplink to commit file changes to source repository." );
			Hymn_Client::out( "" );
		}
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
	 *	@param		boolean		$dry			Flag: dry run move - simulation only
	 *	@return		void
	 */
	public function configure( $module, $verbose = FALSE, $dry = FALSE ){
		$source	= $module->path.'module.xml';
		$target	= $this->app->uri.'config/modules/'.$module->id.'.xml';
		if( !$dry ){																				//  if not in dry mode
			Hymn_Module_Files::createPath( dirname( $target ) );									//  create folder for module configurations in app
			@copy( $source, $target );																//  copy module configuration into this folder
		}
		else {
			$target	= $source;
		}

		$xml	= file_get_contents( $target );
		$xml	= new Hymn_Tool_XmlElement( $xml );
		$type	= isset( $this->app->type ) ? $this->app->type : 1;
		$xml->version->setAttribute( 'install-type', $type );
		$xml->version->setAttribute( 'install-source', $module->sourceId );
		$xml->version->setAttribute( 'install-date', date( "c" ) );

		$config	= (object) array();																	//  prepare empty hymn module config
		if( isset( $this->config->modules->{$module->id}->config ) )								//  module config is set in hymn file
			$config	= $this->config->modules->{$module->id}->config;								//  get module config from hymn file

		// @todo apply configuration of maybe before installed module version

		foreach( $xml->config as $nr => $node ){													//  iterate original module config pairs
			$key	= (string) $node['name'];														//  shortcut config pair key
			if( $module->config[$key]->mandatory == "yes" ){										//  config pair is mandatory
				if( $module->config[$key]->type !== "boolean" ){									//  ... and not of type boolean
					if( !strlen( trim( $module->config[$key]->value ) ) ){							//  ... and has no value
						if( !isset( $config->{$key} ) ){											//  ... and is not set in hymn file
							$config->{$key}	= Hymn_Client::getInput(								//  get new value from console
								"  … configure '".$key."'",											//  render console input label
								$module->config[$key]->type,
								NULL,
								$module->config[$key]->values,										//  get suggested values if set
								FALSE																//  no break = inline question
							);
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
		if( !$dry ){
			$xml->saveXml( $target );																//  save changed DOM to module file
			@unlink( $this->app->uri.'config/modules.cache.serial' );							 	//  remove modules cache file
		}
	}

	public function install( $module, $installType = "link", $verbose = FALSE, $dry = FALSE ){
		try{
			$this->files->copyFiles( $module, $installType, $verbose, $dry );						//  copy module files
			$this->configure( $module, $verbose, $dry );											//  configure module
			$this->sql->runModuleInstallSql( $module, $verbose, $dry || $this->isLiveCopy );		//  run SQL scripts, not for live copy builds
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Installation of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}

	public function uninstall( $module, $verbose = FALSE, $dry = FALSE ){
		try{
			$appUri				= $this->app->uri;
			$localModule		= $this->library->readInstalledModule( $appUri, $module->id );
			$localModule->path	= $appUri;
			$this->files->removeFiles( $localModule, $verbose, $dry );								//  remove module files
			if( !$dry ){																			//  not a dry run
				@unlink( $this->app->uri.'config/modules/'.$module->id.'.xml' );					//  remove module configuration file
				@unlink( $this->app->uri.'config/modules.cache.serial' );							//  remove modules cache file
			}
			$this->sql->runModuleUninstallSql( $localModule, $verbose, $dry );						//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$message	= "Uninstallation of module '%s' failed.\ņ%s";
			$message	= sprintf( $message, $localModule->id, $e->getMessage() );
			throw new RuntimeException( $message, 0, $e );
		}
	}

	public function update( $module, $installType, $verbose = FALSE, $dry = FALSE ){
		try{
			$appUri				= $this->app->uri;
			$localModules		= $this->library->listInstalledModules( $appUri );
			$localModule		= $this->library->readInstalledModule( $appUri, $module->id );
			$localModule->path	= $appUri;

			$availableModules	= $this->library->getModules();										//  get list of all available modules
			$availableModuleMap	= array();															//  prepare map of available modules
			foreach( $availableModules as $availableModule )										//  iterate module list
				$availableModuleMap[$availableModule->id]	= $availableModule;						//  add module to map

			foreach( $module->relations->needs as $relation ){										//  iterate related modules
				if( !array_key_exists( $relation, $localModules ) ){								//  related module is not installed
					if( !array_key_exists( $relation, $availableModuleMap ) ){						//  related module is not available
						$message	= 'Module "%s" is needed but not available.';					//  create exception message
						throw new RuntimeException( sprintf( $message, $relation ) );				//  throw exception
					}
					$relatedModule	= $availableModuleMap[$relation];								//  get related module from map
					if( !$this->quiet )																//  quiet mode is off
						Hymn_Client::out( " - Installing needed module '".$relation."' ..." );		//  inform about installation of needed module
					$this->install( $relatedModule, $installType, $verbose, $dry );					//  install related module
				}
			}
			$this->files->removeFiles( $localModule, FALSE, TRUE );									//  dry run of: remove module files
			$this->sql->runModuleUpdateSql( $localModule, $module, FALSE, TRUE );					//  dry run of: run SQL scripts
			$this->files->copyFiles( $module, $installType, FALSE, TRUE );							//  dry run of: copy module files

			$this->files->removeFiles( $localModule, $verbose, $dry );								//  remove module files
			if( !$dry ){
				@unlink( $this->app->uri.'config/modules/'.$module->id.'.xml' );					//  remove module configuration file
				@unlink( $this->app->uri.'config/modules.cache.serial' );							//  remove modules cache file
			}
			$this->files->copyFiles( $module, $installType, $verbose, $dry );						//  copy module files
			$this->configure( $module, $verbose, $dry );											//  configure module
			$this->sql->runModuleUpdateSql( $localModule, $module, $verbose, $dry );				//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Update of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}
}
