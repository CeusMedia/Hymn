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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */

use Hymn_Client as Client;
use Hymn_Tool_CLI_Question as CliQuestion;

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
 */
class Hymn_Command_App_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";

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
		$this->denyOnProductionMode();

	//	$config				= $this->client->getConfig();
	//	$this->client->getDatabase()->connect();													//  setup connection to database

		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$listInstalled		= $this->library->listInstalledModules();								//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			$this->outError("No installed modules found", Client::EXIT_ON_SETUP );	//  not even one module is installed, no update

		/*  fetch arguments  */
		$moduleIds			= $this->client->arguments->getArguments();								//  get all arguments as one or more module IDs
		if( $moduleIds && ['*'] !== $moduleIds ){
			$installedModuleIds	= array_keys( $listInstalled );
			$moduleIds	= $this->realizeWildcardedModuleIds( $moduleIds, $installedModuleIds );		//  replace wildcard modules
			foreach( $moduleIds as $moduleId ){
				if( !array_key_exists( $moduleId, $listInstalled ) ){
					$this->out( "Module '".$moduleId."' is not installed." );					//  error, but continue, not exit
					continue;
				}
				$this->uninstallModuleById( $moduleId, $listInstalled );
			}
		}
		else{
			$answer	= TRUE;
			if( !$this->flags->force )
				$answer	= CliQuestion::getInstance(
					$this->client,
					"Do you really want to uninstall ALL installed modules?",
					'boolean',
					'no'
				)->ask();
			if( !$answer )
				return;
			$this->uninstallAllModules( $listInstalled );
		}
	}

	protected function __onInit(): void
	{
		$this->library		= $this->getLibrary();													//  get module library instance
	}

	private function uninstallAllModules( array $listInstalled ): void
	{
		$relation	= new Hymn_Module_Graph( $this->client, $this->library );
		foreach( array_keys( $listInstalled ) as $installedModuleId ){
			$module	= $this->library->getAvailableModule( $installedModuleId );
			$relation->addModule( $module );
		}
		$orderedInstalledModules	= array_reverse( $relation->getOrder(), TRUE );
		foreach( $orderedInstalledModules as $orderedModule ){
			if( array_key_exists( $orderedModule->id, $listInstalled ) ){
				$this->uninstallModuleById( $orderedModule->id, $listInstalled );
				$listInstalled		= $this->library->listInstalledModules();						//  get list of installed modules
			}
		}
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		array<Hymn_Structure_Module>	$listInstalled
	 *	@return		void
	 */
	private function uninstallModuleById( string $moduleId, array $listInstalled ): void
	{
		$neededBy	= [];
		foreach( $listInstalled as $installedModuleId => $installedModule )
			if( array_key_exists( $moduleId, $installedModule->relations->needs ) )
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $installedModule->relations->needs[$moduleId]->type )
					$neededBy[]	= $installedModuleId;

		$module		= $listInstalled[$moduleId];
		if( $neededBy && !$this->flags->force ) {
			$list	= implode( ', ', $neededBy );
			$msg	= "Module '%s' is needed by %d other modules (%s)";
			$this->out( sprintf( $msg, $module->id, count( $neededBy ), $list ) );
		}
		else{
			$module->install->path	= 'not_relevant/';
			$installer	= new Hymn_Module_Installer( $this->client, $this->library );
			if( !$this->flags->quiet ) {
				$this->out( vsprintf( '%sUninstalling module %s ...', [
					$this->flags->dry ? 'Dry: ' : '',
					$module->id
				] ) );
			}
			$installer->uninstall( $module );
		}
	}
}
