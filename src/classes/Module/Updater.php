<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Updater{

	protected $client;
	protected $config;
	protected $library;
	protected $dbc;
	protected $files;
	protected $sql;
	protected $isLiveCopy	= FALSE;
	protected $flags;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->dbc		= $client->setupDatabaseConnection();
		$this->files	= new Hymn_Module_Files( $client );
		$this->sql		= new Hymn_Module_SQL( $client );
		$this->app		= $this->config->application;												//  shortcut to application config
		$this->flags	= (object) array(
			'dry'		=> $client->flags & Hymn_Client::FLAG_DRY,
			'quiet'		=> $client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $client->flags & Hymn_Client::FLAG_VERBOSE,
		);

/*		if( isset( $this->app->installMode ) )
			$this->client->out( "Install Mode: ".$this->app->installMode );
		if( isset( $this->app->installType ) )
			$this->client->out( "Install Type: ".$this->app->installType );*/

		if( isset( $this->app->installType ) && $this->app->installType === "copy" )				//  installation is a copy
			if( isset( $this->app->installMode ) && $this->app->installMode === "live" )			//  installation has been for live environment
				$this->isLiveCopy	= TRUE;
		if( $this->isLiveCopy ){
			$this->client->out( "" );
			$this->client->out( "ATTENTION: This build is a live installation in copy mode." );
			$this->client->out( "There is not uplink to commit file changes to source repository." );
			$this->client->out( "" );
		}
	}

	public function reconfigure( $module, $changedOnly = FALSE ){
		$moduleInstalled	= $this->library->readInstalledModule( $module->id );
		$moduleSource		= $this->library->getModule( $module->id, $moduleInstalled->installSource, FALSE );
		if( !$moduleSource ){
			$message	= vsprintf( 'Module "%" is not available in source "%"', array(
				$module->id,
				$moduleInstalled->installSource
			) );
			throw new RuntimeException( $message );
		}

		$hymnModuleConfig	= array();
		if( isset( $this->config->modules->{$module->id}->config ) )
			$hymnModuleConfig	= $this->config->modules->{$module->id}->config;

		$inputValues		= array();																//  prepare list of values to change
		foreach( $moduleSource->config as $configKey => $configData ){
			$valueConfig			= new Hymn_Tool_ConfigValue();//@type
			$valueCurrent			= new Hymn_Tool_ConfigValue();
			$valueCurrentDefault	= new Hymn_Tool_ConfigValue();
			$valueCurrentOriginal	= new Hymn_Tool_ConfigValue();
			if( isset( $moduleInstalled->config[$configKey] ) ){
				$valueCurrent->set(
					$moduleInstalled->config[$configKey]->value,
					$moduleInstalled->config[$configKey]->type
				);
				$valueCurrentDefault->set(
					$moduleInstalled->config[$configKey]->default,
					$moduleInstalled->config[$configKey]->type
				);
				$valueCurrentOriginal->set(
					$moduleInstalled->config[$configKey]->original,
					$moduleInstalled->config[$configKey]->type
				);
			}
			if( isset( $hymnModuleConfig->{$configKey} ) )
				$valueConfig->set( $hymnModuleConfig->{$configKey} );
			$valueUpdateModule		= new Hymn_Tool_ConfigValue(
				$configData->value,
				$configData->type
			);

			$valueAssumed	= $valueUpdateModule;													//  assume current module default as valid
			$valueAssumed	= $valueConfig->is() ? $valueConfig : $valueAssumed;					//  if hymn config is set, take this as default

			$valueSuggest	= $valueUpdateModule;													//  assume ...
			$valueSuggest	= $valueConfig->is() ? $valueConfig : $valueAssumed;					//  if hymn config is set, take this as default
			if( $valueCurrentOriginal->differsFromIfBothSet( $valueUpdateModule ) )					//  if module default has changed since installation
				$valueSuggest	= $valueUpdateModule;

			if( !$valueUpdateModule->is() )															//  config pair not used anymore
				continue;																			//  skip to next

			if( $changedOnly && !$valueCurrent->differsFromIfBothSet( $valueSuggest ) )				//  module value is not newer than config
				continue;																			//  skip to next

			$questionAnswers	= array();
			$this->client->out( '- Config key "'.$configKey.'":' );
			$questionDefault	= 'd';
			$questionAnswers[]	= 'd';
			$this->client->out( '  - [d] default of module : '.$valueUpdateModule->get( TRUE ) );
			if( $valueCurrent->is() ){
				$questionDefault	= 'k';
				$questionAnswers[]	= 'k';
				$this->client->out( '  - [k] keep current: '.$valueCurrent->get( TRUE ) );
			}
			if( $valueCurrent->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 's';
				$this->client->out( '  - [s] suggested: '.$valueSuggest->get( TRUE ) );
			}
			if( $valueConfig->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 'c';
				$this->client->out( '  - [c] config value: '.$valueConfig->get( TRUE ) );
			}
			if( $valueUpdateModule->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 'm';
				$this->client->out( '  - [m] module value: '.$valueUpdateModule->get( TRUE ) );
			}
			$questionAnswers[]	= 'e';
			$this->client->out( '  - [e] enter value' );

			$toolDecision	= new Hymn_Tool_Question(
				$this->client,
				"  = Which config value?",
				'string',
				$questionDefault,
				$questionAnswers,
				FALSE
			);
			$answer			= $toolDecision->ask();
			switch( $answer ){
				case "e":
					$question	= new Hymn_Tool_Question(
						$this->client,
						"  > Enter new value:",
						'string',
						$valueCurrent->get(),
						array(),
						FALSE
					);
					$inputValues[$configKey] = $question->ask();
					break;
				case "c":
					$inputValues[$configKey]	= $valueConfig->get( TRUE );
					break;
				case "m":
					$inputValues[$configKey]	= $valueUpdateModule->get( TRUE );
					break;
				case "s":
					$inputValues[$configKey]	= $valueSuggest->get( TRUE );
					break;
				case "k":
				default:
					$inputValues[$configKey]	= $valueCurrent->get( TRUE );
			}
		}

		// @todo WHY THIS BLOCK?
		if( !$this->flags->dry ){
			$installer	= new Hymn_Module_Installer( $this->client, $this->library );
			$installer->configure( $moduleSource );
		}

		if( !$inputValues )
			return;
		$configurator	= new Hymn_Module_Config( $this->client, $this->library );
		foreach( $inputValues as $configKey => $inputConfigValue )
			$configurator->set( $module->id, $configKey, $inputConfigValue );
	}

	public function update( $module, $installType ){
		try{
			$appUri				= $this->app->uri;
			$localModules		= $this->library->listInstalledModules();
			$localModule		= $this->library->readInstalledModule( $module->id );
			$localModule->path	= $appUri;

			$availableModules	= $this->library->getModules();										//  get list of all available modules
			$availableModuleMap	= array();															//  prepare map of available modules
			foreach( $availableModules as $availableModule )										//  iterate module list
				$availableModuleMap[$availableModule->id]	= $availableModule;						//  add module to map

			$installer	= new Hymn_Module_Installer( $this->client, $this->library );
			foreach( $module->relations->needs as $relation ){										//  iterate related modules
				if( !array_key_exists( $relation, $localModules ) ){								//  related module is not installed
					if( !array_key_exists( $relation, $availableModuleMap ) ){						//  related module is not available
						$message	= 'Module "%s" is needed but not available.';					//  create exception message
						throw new RuntimeException( sprintf( $message, $relation ) );				//  throw exception
					}
					$relatedModule	= $availableModuleMap[$relation];								//  get related module from map
					if( !$this->flags->quiet )														//  quiet mode is off
						$this->client->out( " - Installing needed module '".$relation."' ..." );	//  inform about installation of needed module
					$installer->install( $relatedModule, $installType );							//  install related module
				}
			}
			$this->files->removeFiles( $localModule, FALSE, TRUE );									//  dry run of: remove module files
			$this->sql->runModuleUpdateSql( $localModule, $module, FALSE, TRUE );					//  dry run of: run SQL scripts
			$this->files->copyFiles( $module, $installType, FALSE, TRUE );							//  dry run of: copy module files

			$this->files->removeFiles( $localModule );												//  remove module files
			if( !$this->flags->dry ){
//				$pathConfig	= $this->client->getConfigPath();
//				@unlink( $pathConfig.'modules/'.$module->id.'.xml' );								//  remove module configuration file
				Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );						//  remove modules cache file
			}
			$this->files->copyFiles( $module, $installType );										//  copy module files
			$this->reconfigure( $module, TRUE );													//  configure module skipping unchanged values
			if( !( $this->client->flags & Hymn_Client::FLAG_NO_DB ) )
				$this->sql->runModuleUpdateSql( $localModule, $module );							//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Update of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}
}
