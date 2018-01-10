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
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 *	@todo 			handle relations (new relations after update)
 */
class Hymn_Command_Modules_Updatable extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$modules		= array();																	//  prepare list of modules to update
		$listInstalled	= $library->listInstalledModules();											//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			return Hymn_Client::out( "No installed modules found" );

		$outdatedModules	= array();																//
		foreach( $listInstalled as $installedModule ){
			$source				= $installedModule->installSource;
			$availableModule	= $library->getModule( $installedModule->id, $source, FALSE );
			if( $availableModule ){
				if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
					$outdatedModules[$installedModule->id]	= (object) array(
						'id'		=> $installedModule->id,
						'installed'	=> $installedModule->version,
						'available'	=> $availableModule->version,
						'source'	=> $installedModule->installSource,
					);
				}
			}
		}

		foreach( $outdatedModules as $update ){
			$message	= "- %s: %s -> %s";
			$message	= sprintf( $message, $update->id, $update->installed, $update->available );
			Hymn_Client::out( $message );
		}
	}
}
