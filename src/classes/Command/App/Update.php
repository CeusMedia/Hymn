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
 *	@todo 			handle relations (new relations after update)
 */
class Hymn_Command_App_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $installMode	= "dev";

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->flags->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

//		$start		= microtime( TRUE );

		$config		= $this->client->getConfig();
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;
		if( isset( $config->application->installMode ) )
			$this->installMode	= $config->application->installMode;

		$outdatedModules	= array();																//  prepare empty list of updatable modules
		$library			= $this->getLibrary();													//  get module library instance
		$listInstalled		= $library->listInstalledModules();										//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			return Hymn_Client::out( "No installed modules found" );								//  not even one module is installed, no update
		foreach( $listInstalled as $installedModule ){												//  iterate installed modules
			$source				= $installedModule->installSource;									//  get installed module
			$availableModule	= $library->getModule( $installedModule->id, $source, FALSE );		//  get available module
			if( $availableModule ){																	//  installed module is available atleast
				if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){	//  installed module is outdated
					$outdatedModules[$installedModule->id]	= (object) array(						//  note updatable module
						'id'		=> $installedModule->id,
						'installed'	=> $installedModule->version,
						'available'	=> $availableModule->version,
						'source'	=> $installedModule->installSource,
					);
				}
			}
		}

		$relation			= new Hymn_Module_Graph( $this->client, $library );
		$modules			= array();																//  prepare list of modules to update
		$modulesToUpdate	= $outdatedModules;														//  updatable modules are all outdated modules

		$moduleIds			= $this->client->arguments->getArguments();
		if( $moduleIds ){
			$modulesToUpdate	= array();															//  start with empty list again
			foreach( $moduleIds as $moduleId ){														//  iterate given modules
				if( !array_key_exists( $moduleId, $listInstalled ) )								//  module is not installed, no update
					Hymn_Client::out( "Module '".$moduleId."' is not installed and cannot be updated" );
				else if( !array_key_exists( $moduleId, $outdatedModules ) )							//  module is not outdated, no update
					Hymn_Client::out( "Module '".$moduleId."' is not outdated and cannot be updated" );
				else																				//  module is updatable
					$modulesToUpdate[$moduleId]	= $outdatedModules[$moduleId];						//  note module by copy from outdated modules
			}
		}

		foreach( $modulesToUpdate as $update ){
			$module			= $library->getModule( $update->id );
			$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
//			$installMode	= $this->client->getModuleInstallMode( $module->id, $this->installMode );
/*			$relation->addModule( $module, $installType );
*/
			$message	= "Updating module '%s' from %s to %s as %s ...";
			$message	= sprintf(
				$message,
				$module->id,
				$update->installed,
				$update->available,
				$installType
			);
			Hymn_Client::out( $message );
			$installer	= new Hymn_Module_Updater( $this->client, $library );
			$installer->update( $module, $installType );
		}
	}
}
