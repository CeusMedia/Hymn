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
		if( !$moduleSource )
			throw new RuntimeException( sprintf( 'Module "%" is not available', $module->id ) );

		$hymnModuleConfig	= array();
		if( isset( $this->config->modules->{$module->id}->config ) )
			$hymnModuleConfig	= $this->config->modules->{$module->id}->config;

		$values				= array();
		foreach( $moduleSource->config as $configKey => $configData ){
			$hasModuleValue	= isset( $moduleInstalled->config[$configKey] );
			$hasConfigValue	= isset( $hymnModuleConfig->{$configKey} );
			$currentValue	= $moduleInstalled->config[$configKey]->value;
			$sourceValue	= $configData->value;
			$configValue	= $hasConfigValue ? $hymnModuleConfig->{$configKey} : NULL;
			$realValue		= $configValue ? $configValue : $sourceValue;

			if( !$hasModuleValue )																	//  config pair not set anymore
				continue;																			//  skip to next

			if( $changedOnly && $realValue === $currentValue )										//  current value not differs from source module value
				continue;																			//  skip to next since force is disabled

			$changedFromModule	= $currentValue !== $sourceValue;
			$changedFromConfig	= $hasConfigValue && $currentValue !== $configValue;

			if( $changedOnly && !( $changedFromModule || $changedFromConfig ) )
				continue;

			$values[$configKey]	= $currentValue;
			$questionAnswers	= array();
			$questionAnswers[]	= 'k';
			$this->client->out( '- Config key "'.$configKey.'":' );
			$this->client->out( '  - [k] keep current: '.$currentValue );
			if( $changedFromConfig ){
				$questionAnswers[]	= 'c';
				$this->client->out( '  - [c] config value: '.$configValue );
			}
			if( $changedFromModule ){
				$this->client->out( '  - [m] module value: '.$sourceValue );
				$questionAnswers[]	= 'm';
			}
			$this->client->out( '  - [e] enter value' );
			$questionAnswers[]	= 'e';

			$toolDecision	= new Hymn_Tool_Question(
				$this->client,
				"  = Which config value?",
				'string',
				'k',
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
						$currentValue,
						array(),
						FALSE
					);
					$values[$configKey] = $question->ask();
					break;
				case "c":
					$values[$configKey]	= $configValue;
					break;
				case "m":
					$values[$configKey]	= $sourceValue;
					break;
				case "k":
				default:
					$values[$configKey]	= $currentValue;
			}
		}

		// @todo WHY THIS BLOCK?
		if( !$this->flags->dry ){
			$installer	= new Hymn_Module_Installer( $this->client, $this->library );
			$installer->configure( $moduleSource );
		}

		if( !$values )
			return;
		$configurator	= new Hymn_Module_Config( $this->client, $this->library );
		foreach( $values as $configKey => $configValue ){
			$configurator->set( $module->id, $configKey, $configValue );
		}
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
