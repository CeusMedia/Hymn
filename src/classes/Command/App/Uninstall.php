<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	protected function __onInit(){
		$this->library		= $this->getLibrary();													//  get module library instance
	}

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no, dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
	//	$config				= $this->client->getConfig();
	//	$this->client->getDatabase()->connect();													//  setup connection to database

		if( $this->flags->dry )
			$this->client->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$listInstalled		= $this->library->listInstalledModules();								//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			return $this->client->out( "No installed modules found" );								//  not even one module is installed, no update

		/*  fetch arguments  */
		$moduleIds			= $this->client->arguments->getArguments();								//  get all arguments as one or more module IDs
		if( $moduleIds ){
			$installedModuleIds	= array_keys( $listInstalled );
			$moduleIds	= $this->realizeWildcardedModuleIds( $moduleIds, $installedModuleIds );		//  replace wildcarded modules
			foreach( $moduleIds as $moduleId ){
				if( !array_key_exists( $moduleId, $listInstalled ) ){
					$this->client->out( "Module '".$moduleId."' is not installed." );
					continue;
				}
				$this->uninstallModuleById( $moduleId, $listInstalled );
			}
		}
		else{
			$answer = TRUE;
			if( !$this->flags->force ){
				$question	= new Hymn_Tool_CLI_Question(
					$this->client,
					"Do you really want to uninstall ALL installed modules?",
					'boolean',
					'no'
				);
				$answer	= $question->ask();
			}
			if( !$answer )
				return;
			$this->uninstallAllModules( $listInstalled );
		}
	}

	private function uninstallAllModules( $listInstalled ){
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

	private function uninstallModuleById( $moduleId, $listInstalled ){
		$neededBy	= array();
		foreach( $listInstalled as $installedModuleId => $installedModule )
			if( array_key_exists( $moduleId, $installedModule->relations->needs ) )
				$neededBy[]	= $installedModuleId;

		$module		= $listInstalled[$moduleId];
		if( $neededBy && !$this->flags->force ) {
			$list	= implode( ', ', $neededBy );
			$msg	= "Module '%s' is needed by %d other modules (%s)";
			$this->client->out( sprintf( $msg, $module->id, count( $neededBy ), $list ) );
		}
		else{
			$module->path	= 'not_relevant/';
			$installer	= new Hymn_Module_Installer( $this->client, $this->library );
			if( !$this->flags->quiet ) {
				$this->client->out( sprintf(
					'%sUninstalling module %s ...',
					$this->flags->dry ? 'Dry: ' : '',
					$module->id
				) );
			}
			$installer->uninstall( $module );
		}
	}
}
