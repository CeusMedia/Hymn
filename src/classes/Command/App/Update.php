<?php
/**
 *	...
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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 *	@todo 			handle relations (new relations after update)
 */
class Hymn_Command_App_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";
	protected string $installMode	= "dev";

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$config				= $this->client->getConfig();											//  shortcut hymn config
		$library			= $this->getLibrary();													//  get module library instance
	//	$this->client->getDatabase()->connect();													//  setup connection to database
		$listInstalled		= $library->listInstalledModules();										//  get list of installed modules

		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		if( !$listInstalled )																		//  application has no installed modules
			$this->outError( "No installed modules found", Hymn_Client::EXIT_ON_SETUP );	//  not even one module is installed, no update

//		$start		= microtime( TRUE );

		//  apply default install type and mode if not set in application hymn file
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;
		if( isset( $config->application->installMode ) )
			$this->installMode	= $config->application->installMode;

		$outdatedModules	= [];																//  prepare empty list of updatable modules
		foreach( $listInstalled as $installedModule ){												//  iterate installed modules
			$source				= $installedModule->installSource;									//  get installed module
			$availableModule	= $library->getAvailableModule( $installedModule->id, $source, FALSE );		//  get available module
			if( $availableModule ){																	//  installed module is available at least
				$versionInstalled	= $installedModule->version;									//  shortcut installed module version
				$versionAvailable	= $availableModule->version;									//  shortcut available module version
				if( version_compare( $versionAvailable, $versionInstalled, '>' ) ){					//  installed module is outdated
					$outdatedModules[$installedModule->id]	= (object) array(						//  note updatable module
						'id'		=> $installedModule->id,
						'installed'	=> $installedModule->version,
						'available'	=> $availableModule->version,
						'source'	=> $installedModule->installSource,
					);
				}
			}
		}

		$modulesToUpdate	= $outdatedModules;														//  updatable modules are all outdated modules
		$moduleIds			= $this->client->arguments->getArguments();
		if( $moduleIds ){
			$outdatedModuleIds	= array_keys( $outdatedModules );
			$moduleIds	= $this->realizeWildcardedModuleIds( $moduleIds, $outdatedModuleIds );		//  replace wildcarded modules

			$modulesToUpdate	= [];															//  start with empty list again
			foreach( $moduleIds as $moduleId ){														//  iterate given modules
				if( !array_key_exists( $moduleId, $listInstalled ) )								//  module is not installed, no update
					$this->out( sprintf(
						"Module '%s' is not installed and cannot be updated",
						$moduleId
					) );
				else if( !array_key_exists( $moduleId, $outdatedModules ) ){						//  module is not outdated, no update
					if( $this->flags->force ){
						$installedModule	= $listInstalled[$moduleId];
						$modulesToUpdate[$moduleId]	= (object) [
							'id'		=> $installedModule->id,
							'installed'	=> $installedModule->version,
							'available'	=> $installedModule->version,
							'source'	=> $installedModule->installSource,
						];
					}
					else{
						$this->out( sprintf(
							"Module '%s' is not outdated and cannot be updated",
							$moduleId
						) );
					}
				}
				else																				//  module is updatable
					$modulesToUpdate[$moduleId]	= $outdatedModules[$moduleId];						//  note module by copy from outdated modules
			}
		}

		$updater	= new Hymn_Module_Updater( $this->client, $library );
		foreach( $modulesToUpdate as $update ){
			$module			= $library->getAvailableModule( $update->id, $update->source );
			try{
				$this->client->getFramework()->checkModuleSupport( $module );
			}
			catch( Exception $e ){
				$this->out( 'Error: '.$e->getMessage().'.' );					//  error, but continue, not exit
				continue;
			}
			$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
			$message		= vsprintf( 'Updating module "%s" from %s to %s as %s ...', [
				$module->id,
				$update->installed,
				$update->available,
				$installType
			] );
			$this->out( $message );
			$updater->update( $module, $installType );
		}
	}
}
