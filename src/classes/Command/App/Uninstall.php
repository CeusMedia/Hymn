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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config				= $this->client->getConfig();
		$library			= $this->getLibrary();												//  get module library instance
		$relation			= new Hymn_Module_Graph( $this->client, $library );

		if( $this->flags->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

		$listInstalled		= $library->listInstalledModules();									//  get list of installed modules
		if( !$listInstalled )																	//  application has no installed modules
			return Hymn_Client::out( "No installed modules found" );							//  not even one module is installed, no update

		/*  fetch arguments  */
		$moduleIds			= $this->client->arguments->getArguments();							//  get all arguments as one or more module IDs
		if( $moduleIds ){
			$installedModuleIds	= array_keys( $listInstalled );
			$moduleIds	= $this->realizeWildcardedModuleIds( $moduleIds, $installedModuleIds );	//  replace wildcarded modules
			if( !$moduleIds ){
				Hymn_Client::out( "No uninstallable modules given" );
				return;
			}
			foreach( $moduleIds as $moduleId ){
				if( !array_key_exists( $moduleId, $listInstalled ) ){
					Hymn_Client::out( "Module '".$moduleId."' is not installed." );
					continue;
				}
				$this->uninstallModuleById( $moduleId, $listInstalled );
			}
		}
		throw new Exception( 'Uninstallation of all modules is not supported at the moment' );
	}

	private function uninstallModule( $moduleId, $listInstalled ){
		$neededBy	= array();
		foreach( $listInstalled as $installedModuleId => $installedModule )
			if( in_array( $moduleId, $installedModule->relations->needs ) )
				$neededBy[]	= $installedModuleId;

		$module		= $listInstalled[$moduleId];
		if( $neededBy && !$this->flags->force ) {
			$list	= implode( ', ', $neededBy );
			$msg	= "Module '%s' is needed by %d other modules (%s)";
			Hymn_Client::out( sprintf( $msg, $module->id, count( $neededBy ), $list ) );
		}
		else{
			$module->path	= 'not_relevant/';
			$installer	= new Hymn_Module_Installer( $this->client, $library );
			if( !$this->flags->quiet ) {
				Hymn_Client::out( sprintf(
					'%sUninstalling module %s ...',
					$this->flags->dry ? 'Dry: ' : '',
					$module->id
				) );
			}
			if( !$this->flags->dry )
				$installer->uninstall( $module );
		}
	}
}
