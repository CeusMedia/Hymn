<?php /** @noinspection PhpUnused */
declare(strict_types=1);

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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
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

		//  apply default install type and mode if not set in application hymn file
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;
		if( isset( $config->application->installMode ) )
			$this->installMode	= $config->application->installMode;

		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$this->updateOutdatedModules();
		$this->installNewlyNeededModules();
	}

	protected function detectModuleSource( string $moduleId ): ?string
	{
		if( '' === trim( $moduleId ) )
			throw new InvalidArgumentException( __METHOD__.' > Module ID cannot by empty' );

		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$defaultId	= $library->getDefaultSource();
		if( !empty( $config->modules[$moduleId]->source ) ){
			$sourceByHymn	= trim( $config->modules[$moduleId]->source );
			if( $library->isAvailableModuleInSource( $moduleId, $sourceByHymn ) )
				return $sourceByHymn;
		}
		if( $defaultId )
			if( $library->isAvailableModuleInSource( $moduleId, $defaultId ) )
				return $defaultId;
		$moduleSourceIds	= array_keys( $library->getAvailableModuleSources( $moduleId ) );
		if( $moduleSourceIds )
			return $moduleSourceIds[0];
		return NULL;
	}

	/**
	 *	Installs modules, which are needed after update of modules list in hymn file.
	 *	@return		void
	 */
	protected function installNewlyNeededModules(): void
	{
		$library	= $this->getLibrary();
		$config		= $this->client->getConfig();

		$activeSourceList	= $library->getActiveSources();
		$activeSourceIds	= array_keys( $activeSourceList );
		$listInstalled		= $library->listInstalledModules();

		$relation	= new Hymn_Module_Graph( $this->client, $library );
		foreach( $config->modules as $moduleId => $moduleConfig ){
			if( '' === $moduleId || str_starts_with( $moduleId, '@' ) )
				continue;
			$sourceId	= $this->detectModuleSource( $moduleId );
			$sourceId	= $this->client->getModuleInstallSource( $moduleId, $activeSourceIds, $sourceId );
			$module		= $library->getAvailableModule( $moduleId, $sourceId );
			if( !$module->isActive )
				continue;
			$relation->addModule( $module );
		}

		foreach( $relation->getOrder() as $module ){
			try{
				if( array_key_exists( $module->id, $listInstalled ) )
					continue;
				$this->client->getFramework()->checkModuleSupport( $module );
				$installType	= $this->client->getModuleInstallType( $module->id );
				if( !$this->flags->quiet )
					$this->out( vsprintf( "%sInstalling module '%s' (from %s) version %s as %s ...", [
						$this->flags->dry ? 'Dry: ' : '',
						$module->id,
						$module->sourceId,
						$module->version->current,
						$installType
					] ) );
				$installer	= new Hymn_Module_Installer( $this->client, $library );
				$installer->install( $module, $installType );
			}
			catch( Exception $e ){
				$this->out( 'Error: '.$e->getMessage().'.' );				//  error, but continue, not exit
			}
		}
	}

	protected function updateOutdatedModules(): void
	{
		$config				= $this->client->getConfig();											//  shortcut hymn config
		$library			= $this->getLibrary();													//  get module library instance
		//	$this->client->getDatabase()->connect();													//  setup connection to database
		$listInstalled		= $library->listInstalledModules();										//  get list of installed modules

		//  apply default install type and mode if not set in application hymn file
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;
		if( isset( $config->application->installMode ) )
			$this->installMode	= $config->application->installMode;

		$outdatedModules	= [];																	//  prepare empty list of updatable modules
		foreach( $listInstalled as $installedModule ){												//  iterate installed modules
			$source				= $installedModule->install->source;								//  get installed module
			$availableModule	= $library->getAvailableModule( $installedModule->id, $source, FALSE );		//  get available module
			if( $availableModule ){																	//  installed module is available at least
				$versionInstalled	= $installedModule->version->current;							//  shortcut installed module version
				$versionAvailable	= $availableModule->version->current;							//  shortcut available module version
				if( version_compare( $versionAvailable, $versionInstalled, '>' ) ){			//  installed module is outdated
					$outdatedModules[$installedModule->id]	= (object) [							//  note updatable module
						'id'		=> $installedModule->id,
						'installed'	=> $versionInstalled,
						'available'	=> $versionAvailable,
						'source'	=> $installedModule->install->source,
					];
				}
			}
		}

		$modulesToUpdate	= $outdatedModules;														//  updatable modules are all outdated modules
		$moduleIds			= $this->client->arguments->getArguments();
		if( $moduleIds ){
			$outdatedModuleIds	= array_keys( $outdatedModules );
			$moduleIds	= $this->realizeWildcardedModuleIds( $moduleIds, $outdatedModuleIds );		//  replace wildcarded modules

			$modulesToUpdate	= [];																//  start with empty list again
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
							'installed'	=> $installedModule->version->current,
							'available'	=> $installedModule->version->current,
							'source'	=> $installedModule->install->source,
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
