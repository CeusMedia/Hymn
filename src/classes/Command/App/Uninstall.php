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
		if( $this->flags->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$moduleId		= trim( $this->client->arguments->getArgument() );
		$listInstalled	= $library->listInstalledModules();
		$isInstalled	= array_key_exists( $moduleId, $listInstalled );
		if( !$moduleId )
			Hymn_Client::out( "No module id given" );
		else if( !$isInstalled )
			Hymn_Client::out( "Module '".$moduleId."' is not installed" );
		else{
			$module		= $listInstalled[$moduleId];
			$neededBy	= array();
			foreach( $listInstalled as $installedModuleId => $installedModule )
				if( in_array( $moduleId, $installedModule->relations->needs ) )
					$neededBy[]	= $installedModuleId;
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
}
