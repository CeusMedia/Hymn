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
 *	@package		CeusMedia.Hymn.Command.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Module_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";
//	protected string $installMode	= "dev";

	/** @var	array<string>							$activeSourceIds  */
	protected array $activeSourceIds;

	/**	@var	array<string,Hymn_Structure_Source>		$activeSourceList */
	protected array $activeSourceList;

	public function run(): void
	{
		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$library	= $this->getLibrary();													//  get availableModule library instance
		$this->activeSourceList	= $library->getActiveSources();
		$this->activeSourceIds	= array_keys( $this->activeSourceList );

		$moduleId	= $this->evaluateGivenModuleId();
		$sourceId	= $this->evaluateGivenSourceIdWithModuleId( $moduleId );

		//  apply default install type and mode if not set in application hymn file
		$config		= $this->client->getConfig();											//  shortcut hymn config
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;

		if( !$library->isAvailableModuleInSource( $moduleId, $sourceId ) )
			$this->outError( 'Module "'.$moduleId.'" not found in source "'.$sourceId.'"', Hymn_Client::EXIT_ON_RUN );

		$availableModule	= $library->getUncachedAvailableModuleFromSource( $moduleId, $sourceId );
		foreach( $this->getNeededModulesToInstall( $availableModule ) as $neededModule ){		//  install needed modules first
			$this->client->outVerbose( "Module '".$neededModule->id."' is needed by '".$availableModule->id."'" );
			$this->installModule( $neededModule );
		}
		$this->installModule( $availableModule );
	}


	//  --  PROTECTED  --  //


	protected function evaluateGivenModuleId(): string
	{
		$moduleId	= $this->client->arguments->getArgument();
		if( '' === trim( $moduleId ?? '' ) )
			$this->outError( 'No module ID given.', Hymn_Client::EXIT_ON_INPUT );

		if( $this->getLibrary()->isInstalledModule( $moduleId ) )
			$this->outError( "Module '".$moduleId."' is already installed", Hymn_Client::EXIT_ON_INPUT );

		if( NULL === $this->getLibrary()->getAvailableModule( $moduleId, NULL, FALSE ) )
			$this->outError( "Module '".$moduleId."' not found", Hymn_Client::EXIT_ON_INPUT );

		return $moduleId;
	}

	protected function evaluateGivenSourceIdWithModuleId( string $moduleId ): string
	{
		$sourceId	= trim( $this->client->arguments->getArgument( 1 ) ?? '' );
		if( '' !== $sourceId )
			$this->evaluateSourceId( $sourceId );
		else{
			$sourceId	= $this->detectModuleSource( $moduleId );
			if( NULL === $sourceId )
				$this->outError( 'Source of module "'.$moduleId.'" could not been detected', Hymn_Client::EXIT_ON_INPUT );
		}
		return $this->client->getModuleInstallSource( $moduleId, $this->activeSourceIds, $sourceId );
	}

	/**
	 *	Returns modules needed by module, but not installed yet, in order of installation.
	 *	Their sources are detected like on app-install.
	 *	@param		Hymn_Structure_Module	$module
	 *	@return		list<Hymn_Structure_Module>
	 */
	protected function getNeededModulesToInstall( Hymn_Structure_Module $module ): array
	{
		$library	= $this->getLibrary();
		$graph		= new Hymn_Module_Graph( $this->client, $library );
		$graph->addModule( $module );																//  adds needed modules recursively
		$installed	= $library->listInstalledModules();
		$list		= [];
		foreach( $graph->getModulesOrderedByDependency() as $neededModule ){
			if( $neededModule->id === $module->id || array_key_exists( $neededModule->id, $installed ) )
				continue;
			$sourceId	= $this->detectModuleSource( $neededModule->id );
			if( NULL === $sourceId )
				$this->outError( 'Needed module "'.$neededModule->id.'" is not available', Hymn_Client::EXIT_ON_RUN );
			$sourceId	= $this->client->getModuleInstallSource( $neededModule->id, $this->activeSourceIds, $sourceId );
			$list[]		= $library->getUncachedAvailableModuleFromSource( $neededModule->id, $sourceId );
		}
		return $list;
	}

	protected function installModule( Hymn_Structure_Module $module ): void
	{
		$library		= $this->getLibrary();
		$installer		= new Hymn_Module_Installer( $this->client, $library );
		$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
		$this->out( vsprintf( "%sInstalling module '%s' (from %s) version %s as %s ...", [
			$this->flags->dry ? 'Dry: ' : '',
			$module->id,
			$module->sourceId,
			$module->version->current,
			$installType
		] ) );
		$installer->install( $module, $installType );
	}
}