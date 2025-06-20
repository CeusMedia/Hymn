<?php
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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Updater
{
	protected Hymn_Client $client;
	protected Hymn_Structure_Config $config;
	protected Hymn_Module_Library $library;
	protected bool $isLiveCopy	= FALSE;

	/** @var object{dry: bool, quiet: bool, verbose: bool} $flags */
	protected object $flags;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library )
	{
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->flags	= (object) [
			'dry'		=> (bool) ( $client->flags & Hymn_Client::FLAG_DRY ),
			'quiet'		=> (bool) ( $client->flags & Hymn_Client::FLAG_QUIET ),
			'verbose'	=> (bool) ( $client->flags & Hymn_Client::FLAG_VERBOSE ),
		];

		$app		= $this->config->application;												//  shortcut to application config
/*		if( isset( $app->installMode ) )
			$this->client->out( "Install Mode: ".$app->installMode );
		if( isset( $app->installType ) )
			$this->client->out( "Install Type: ".$app->installType );*/

		if( isset( $app->installType ) && $app->installType === "copy" )				//  installation is a copy
			if( isset( $app->installMode ) && $app->installMode === "live" )			//  installation has been for live environment
				$this->isLiveCopy	= TRUE;
		if( $this->isLiveCopy )
			$this->client->out( [
				'',
				'ATTENTION: This build is a live installation in copy mode.',
				'There is not uplink to commit file changes to source repository.',
				'',
			] );
	}

	/**
	 *	Return list of outdated modules within current application.
	 *	@access		public
	 *	@param		string|NULL		$sourceId		ID of source to reduce to
	 *	@return		array		List of outdated modules
	 */
	public function getUpdatableModules( string $sourceId = NULL ): array
	{
		$outdated		= [];																		//  prepare list of outdated modules
		foreach( $this->library->listInstalledModules( $sourceId ) as $installed ){					//  iterate installed modules
			$source		= $installed->install?->source ?? 'unknown';								//  get source of installed module
			$available	= $this->library->getAvailableModule( $installed->id, $source, FALSE );		//  get available module within source of installed module
			if( !$available )																		//  module is not existing in source anymore
				continue;																			//  skip this module @todo remove gone modules ?!?
			if( version_compare( $installed->version->current, $available->version->current, '>=' ) )					//  installed module is up-to-date
				continue;																			//  skip this module

			$logs	= [];
			foreach( $available->version->log as $log )
				if( version_compare( $log->version, $installed->version->current, '>' ) )
					$logs[]	= $log;

			$outdated[$installed->id]	= (object) [												//	note outdated module and note:
				'id'		=> $installed->id,														//  - module ID
				'source'	=> $installed->install->source,											//  - source of current module installation
				'installed'	=> $installed->version->current,										//  - currently installed module version
				'available'	=> $available->version->current,										//  - available module version
				'log'		=> $logs
			];
		}
		return $outdated;																			//  return list of outdated modules
	}

	public function reconfigure( Hymn_Structure_Module $module, bool $changedOnly = FALSE ): void
	{
		$moduleInstalled	= $this->library->readInstalledModule( $module->id );
		$moduleSource		= $this->library->getAvailableModule( $module->id, $moduleInstalled->install->source, FALSE );
		if( !$moduleSource ){
			$message	= vsprintf( 'Module "%" is not available in source "%"', [
				$module->id,
				$moduleInstalled->install->source
			] );
			throw new RuntimeException( $message );
		}

		$hymnModuleConfig	= [];
		if( isset( $this->config->modules[$module->id]->config ) )
			$hymnModuleConfig	= $this->config->modules[$module->id]->config;

		$inputValues		= [];																//  prepare list of values to change
		foreach( $moduleSource->config as $configKey => $configData ){
			$valueConfig			= new Hymn_Tool_ConfigValue();//@type
			$valueCurrent			= new Hymn_Tool_ConfigValue();
			$valueCurrentDefault	= new Hymn_Tool_ConfigValue();
			$valueCurrentOriginal	= new Hymn_Tool_ConfigValue();
			$valueUpdateModule		= new Hymn_Tool_ConfigValue(
				$configData->value,
				$configData->type
			);
			if( isset( $moduleInstalled->config[$configKey] ) ){
				$pair	= $moduleInstalled->config[$configKey];
				$valueCurrent->setType( $pair->type )->setValue( $pair->value );
				$valueCurrentDefault->setType( $pair->type )->setValue( $pair->default );
				$valueCurrentOriginal->setType( $pair->type )->setValue( $pair->original );
			}
			if( isset( $hymnModuleConfig->{$configKey} ) ){
				$valueConfig->setType( $configData->type );
				$valueConfig->setValue( $hymnModuleConfig->{$configKey} );
			}
			$valueSuggest	= $valueCurrentOriginal;
			if( $valueUpdateModule->hasValue() )
				if( $valueUpdateModule->getValue() !== $valueCurrentDefault->getValue() )
					$valueSuggest	= $valueUpdateModule;

			if( $changedOnly && !$valueCurrent->differsFromIfBothSet( $valueSuggest ) )				//  module value is not newer than config
				continue;																			//  skip to next

			$questionAnswers	= [];
			$this->client->out( '- Config key "'.$configKey.'":' );
			$questionDefault	= 'd';
			$questionAnswers[]	= 'd';
			$this->client->out( '  - [d] default of module : '.$valueUpdateModule->getValue( TRUE ) );
			if( $valueCurrent->is() ){
				$questionDefault	= 'k';
				$questionAnswers[]	= 'k';
				$this->client->out( '  - [k] keep current: '.$valueCurrent->getValue( TRUE ) );
			}
			if( $valueCurrent->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 's';
				$this->client->out( '  - [s] suggested: '.$valueSuggest->getValue( TRUE ) );
			}
			if( $valueConfig->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 'c';
				$this->client->out( '  - [c] config value: '.$valueConfig->getValue( TRUE ) );
			}
			if( $valueUpdateModule->differsFromIfBothSet( $valueSuggest ) ){
				$questionAnswers[]	= 'm';
				$this->client->out( '  - [m] module value: '.$valueUpdateModule->getValue( TRUE ) );
			}
			$questionAnswers[]	= 'e';
			$this->client->out( '  - [e] enter value' );

			$answer			= Hymn_Tool_CLI_Question::getInstance(
				$this->client,
				"  = Which config value?",
				'string',
				$questionDefault,
				$questionAnswers,
				FALSE
			)->ask();
			switch( $answer ){
				case "e":
					$inputValues[$configKey]	= Hymn_Tool_CLI_Question::getInstance(
						$this->client,
						"  > Enter new value:",
						'string',
						(string) $valueCurrent->getValue(),
						[],
						FALSE
					)->ask();
					break;
				case "c":
					$inputValues[$configKey]	= $valueConfig->getValue( TRUE );
					break;
				case "m":
					$inputValues[$configKey]	= $valueUpdateModule->getValue( TRUE );
					break;
				case "s":
					$inputValues[$configKey]	= $valueSuggest->getValue( TRUE );
					break;
				case "k":
					$inputValues[$configKey]	= $valueCurrent->getValue( TRUE );
					break;
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

	public function update( Hymn_Structure_Module $module, string $installType ): bool
	{
		$this->client->getFramework()->checkModuleSupport( $module );
		$files	= new Hymn_Module_Files( $this->client );
		$sql	= new Hymn_Module_SQL( $this->client );
		try{
			$appUri				= $this->config->application->uri;
			$localModules		= $this->library->listInstalledModules();
			$localModule		= $this->library->readInstalledModule( $module->id );
			$localModule->path	= $appUri;

			$availableModules	= $this->library->getAvailableModules();							//  get list of all available modules
			$availableModuleMap	= [];															//  prepare map of available modules
			foreach( $availableModules as $availableModule )										//  iterate module list
				$availableModuleMap[$availableModule->id]	= $availableModule;						//  add module to map

			$installer	= new Hymn_Module_Installer( $this->client, $this->library );

			//  --  MODULES TO UNINSTALL
			$installedModules	= [];
			$neededModules		= [];
			foreach( $localModule->relations->needs as $moduleId => $relation )
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $relation->type )				//  only if relation is a module
					$installedModules[$moduleId]	= $relation;
			foreach( $module->relations->needs as $moduleId => $relation )
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $relation->type )				//  only if relation is a module
					$neededModules[$moduleId]	= $relation;
			$moduleIdsToUninstall	= array_diff(													//  calculate modules not needed anymore ...
				array_keys( $installedModules ),													//  ... by intersecting old list ...
				array_keys( $neededModules )														//  ... with new list of needed modules
			);
			foreach( $moduleIdsToUninstall as $moduleIdToUninstall ){								//  iterate modules to uninstall
				if( !array_key_exists( $moduleIdToUninstall, $localModules ) )						//  module to uninstall is not installed
					continue;																		//  skip this module
				foreach( $localModules as $localModule ){											//  iterate installed modules
					if( $localModule->id === $module->id )											//  module to check is module to update
						continue;																	//  skip this module
					if( array_key_exists( $moduleIdToUninstall, $localModule->relations->needs ) )	//  module is needed by another module
						continue 2;																	//  to not uninstall module
				}
				$this->client->out( " - Uninstalling module '".$moduleIdToUninstall."' ..." );		//  inform about installation of needed module
				$installer->uninstall( $localModule );												//  uninstall module
			}

			//  --  MODULES TO INSTALL
			foreach( $module->relations->needs as $neededModuleId => $relation ){					//  iterate related modules
				if( Hymn_Structure_Module_Relation::TYPE_MODULE !== $relation->type )				//  relation is not a module
					continue;
				if( array_key_exists( $neededModuleId, $localModules ) )							//  related module is installed
					continue;
				if( !array_key_exists( $neededModuleId, $availableModuleMap ) ){					//  related module is not available
					$message	= 'Module "%s" is needed but not available.';						//  create exception message
					throw new RuntimeException( sprintf( $message, $neededModuleId ) );				//  throw exception
				}
				$relatedModule	= $availableModuleMap[$neededModuleId];								//  get related module from map
				if( !$this->flags->quiet )															//  quiet mode is off
					$this->client->out( " - Installing needed module '".$neededModuleId."' ..." );	//  inform about installation of needed module
				$installer->install( $relatedModule, $installType );								//  install related module
			}

			//  --  TRY TO HANDLE FILE AND DATABASE CHANGES
			$files->removeFiles( $localModule, TRUE );												//  try run of: remove module files
			$sql->runModuleUpdateSql( $localModule, $module, TRUE );								//  try run of: run SQL scripts
			$files->copyFiles( $module, $installType, TRUE );										//  try run of: copy module files

			$files->removeFiles( $localModule );													//  remove module files, this time for real
			if( !$this->flags->dry ){
//				$pathConfig	= $this->client->getConfigPath();
//				@unlink( $pathConfig.'modules/'.$module->id.'.xml' );								//  remove module configuration file
				Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );						//  remove modules cache file
			}
			$files->copyFiles( $module, $installType );												//  copy module files, this time for real
			$this->reconfigure( $module, TRUE );													//  configure module skipping unchanged values
			$sql->runModuleUpdateSql( $localModule, $module );										//  run SQL scripts, this time for real
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Update of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}
}
