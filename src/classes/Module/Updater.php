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
class Hymn_Module_Updater{

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

	public function reconfigure( $module, $verbose = FALSE, $dry = FALSE ){
		$moduleInstalled	= $this->library->readInstalledModule( $this->app->uri, $module->id  );
		$moduleSource		= $this->library->getModule( $module->id, $moduleInstalled->installSource, FALSE );
		if( !$moduleSource )
			throw new RuntimeException( sprintf( 'Module "%" is not available', $module->id ) );

		$values				= array();
		foreach( $moduleSource->config as $configKey => $configData ){
			if( !isset( $moduleInstalled->config[$configKey] ) )
				continue;
			$currentValue	= $moduleInstalled->config[$configKey]->value;
			$sourceValue	= $configData->value;
			if( $sourceValue === $currentValue )
				continue;
			if( $this->quiet )
				$values[$configKey]	= $installed->value;
			else{
				Hymn_Client::out( '- Config key "'.$configKey.'" differs. Source: '.$sourceValue.' | Installed: '.$currentValue );
				$answer		= Hymn_Tool_Decision::askStatic( "Keep custom value?", NULL, NULL, FALSE );
				if( $answer === "y" )
					$values[$configKey]	= $currentValue;
			}
		}
		$target	= $this->app->uri.'config/modules/'.$module->id.'.xml';
		if( !$dry ){
			$installer	= new Hymn_Module_Installer( $this->client, $this->library, $this->quiet );
			$installer->configure( $moduleSource, $verbose, $dry );
		}
		if( !$values )
			return;
		$configurator	= new Hymn_Module_Config( $this->client, $this->library, $this->quiet );
		foreach( $values as $configKey => $configValue ){
			$configurator->set( $module->id, $configKey, $configValue, $verbose, $dry );
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

			$installer	= new Hymn_Module_Installer( $this->client, $this->library, $this->quiet );
			foreach( $module->relations->needs as $relation ){										//  iterate related modules
				if( !array_key_exists( $relation, $localModules ) ){								//  related module is not installed
					if( !array_key_exists( $relation, $availableModuleMap ) ){						//  related module is not available
						$message	= 'Module "%s" is needed but not available.';					//  create exception message
						throw new RuntimeException( sprintf( $message, $relation ) );				//  throw exception
					}
					$relatedModule	= $availableModuleMap[$relation];								//  get related module from map
					if( !$this->quiet )																//  quiet mode is off
						Hymn_Client::out( " - Installing needed module '".$relation."' ..." );		//  inform about installation of needed module
					$installer->install( $relatedModule, $installType, $verbose, $dry );			//  install related module
				}
			}
			$this->files->removeFiles( $localModule, FALSE, TRUE );									//  dry run of: remove module files
			$this->sql->runModuleUpdateSql( $localModule, $module, FALSE, TRUE );					//  dry run of: run SQL scripts
			$this->files->copyFiles( $module, $installType, FALSE, TRUE );							//  dry run of: copy module files

			$this->files->removeFiles( $localModule, $verbose, $dry );								//  remove module files
			if( !$dry ){
//				@unlink( $this->app->uri.'config/modules/'.$module->id.'.xml' );					//  remove module configuration file
				@unlink( $this->app->uri.'config/modules.cache.serial' );							//  remove modules cache file
			}
			$this->files->copyFiles( $module, $installType, $verbose, $dry );						//  copy module files
			$this->reconfigure( $module, $verbose, $dry );											//  configure module
			$this->sql->runModuleUpdateSql( $localModule, $module, $verbose, $dry );				//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Update of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}
}
