<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";

	/** @var	array<string>							$activeSourceIds  */
	protected array $activeSourceIds;

	/**	@var	array<string,Hymn_Structure_Source>		$activeSourceList */
	protected array $activeSourceList;

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no, dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$moduleIds	= $this->client->arguments->getArguments();

		if( $moduleIds )
			$relation	= $this->getGraphOfRequestedModuleIds( $moduleIds );
		else
			$relation	= $this->getGraphOfAllUninstalledButConfiguredModules();

		foreach( $relation->getModulesOrderedByDependency() as $module ){
			$isInstalled	= array_key_exists( $module->id, $this->getLibrary()->listInstalledModules() );
			$isCalledModule	= in_array( $module->id, $moduleIds );
			$isForced		= $this->flags->force && ( $isCalledModule || !$moduleIds );
			if( $isInstalled && !$isForced ){
				$this->client->outVerbose( "Module '".$module->id."' is already installed" );
				continue;
			}
			$this->installModule( $module );
		}

/*		//  todo: custom install mode: define SQL to import in hymn file
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );							//  broken on this point since extraction to Hymn_Module_SQL
			}
		}*/
	}


	//  --  PROTECTED  --  //


	protected function __onInit(): void
	{
		$library	= $this->getLibrary();
		$this->activeSourceList	= $library->getActiveSources();
		$this->activeSourceIds	= array_keys( $this->activeSourceList );
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
/*		if( $library->isInstalledModule( $moduleId ) ){
		}*/
		if( $defaultId ){
			if( $library->isAvailableModuleInSource( $moduleId, $defaultId ) )
				return $defaultId;
		}
		$moduleSourceIds	= array_keys( $library->getAvailableModuleSources( $moduleId ) );
		if( $moduleSourceIds )
			return $moduleSourceIds[0];
		return NULL;
	}

	protected function getGraphOfRequestedModuleIds( array $moduleIds ): Hymn_Module_Graph
	{
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );
		foreach( $moduleIds as $moduleId ){
			$sourceId	= $this->detectModuleSource( $moduleId );
			$sourceId	= $this->client->getModuleInstallSource( $moduleId, $this->activeSourceIds, $sourceId );
			$module		= $library->getAvailableModule( $moduleId, $sourceId );
			if( $module->isActive )
				$relation->addModule( $module );
		}
		return $relation;
	}

	protected function getGraphOfAllUninstalledButConfiguredModules(): Hymn_Module_Graph
	{
		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$this->out( 'Mode: Install ALL ('.count( $config->modules ).')' );
		foreach( $config->modules as $moduleId => $moduleConfig ){
			if( '' === $moduleId || str_starts_with( $moduleId, '@' ) )
				continue;
			$sourceId	= $this->detectModuleSource( $moduleId );
			$sourceId	= $this->client->getModuleInstallSource( $moduleId, $this->activeSourceIds, $sourceId );
			$module		= $library->getAvailableModule( $moduleId, $sourceId );
			if( $module->isActive ){
				$relation->addModule( $module );
				$this->client->outVerbose( '- implies module '.$moduleId.' (from '.$sourceId.')' );
			}
		}
		return $relation;
	}

	protected function installModule( Hymn_Structure_Module $module ): bool
	{
		try{
			$this->client->getFramework()->checkModuleSupport( $module );
		}
		catch( Exception $e ){
			$this->outError( 'Error: '.$e->getMessage().'.' );				//  error, but continue, not exit
			return FALSE;
		}

		$library	= $this->getLibrary();
		$installer	= new Hymn_Module_Installer( $this->client, $library );
		$installType	= $this->client->getModuleInstallType( $module->id );
//			$installMode	= $this->client->getModuleInstallMode( $module->id );
		$sourceId	= $this->detectModuleSource( $module->id );
		$sourceId	= $this->client->getModuleInstallSource( $module->id, $this->activeSourceIds, $sourceId );
		/** @var Hymn_Structure_Module $module */
		$module		= $library->getUncachedAvailableModuleFromSource( $module->id, $sourceId );

		if( empty( $module->sourceId ) ){
			$this->outError( "Module '".$module->id."' is not assigned to a source - skipped" );
			return FALSE;
		}
		$installType	= $this->client->getModuleInstallType( $module->id, $installType );
		$this->out( vsprintf( "%sInstalling module '%s' (from %s) version %s as %s ...", [
			$this->flags->dry ? 'Dry: ' : '',
			$module->id,
			$module->sourceId,
			$module->version->current,
			$installType
		] ) );
		return $installer->install( $module, $installType );
	}
}
