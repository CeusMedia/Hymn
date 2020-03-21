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
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Modules_Search extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags: verbose, veryVerbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$term		= $this->client->arguments->getArgument( 0 );
		$shelfId	= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->evaluateShelfId( $shelfId );

		$msgTotal		= '%d module(s) found in all module sources:';
		$msgEntry		= '%s (%s)';
		$modulesFound	= array();

		if( $shelfId ){
			$msgTotal	= '%d module(s) found in module source "%s":';
			$msgEntry	= '- %s (%s)';
			$modulesAvailable	= $this->getAvailableModulesMap( $shelfId );
			$modulesInstalled	= $library->listInstalledModules( $shelfId );
			foreach( $modulesAvailable as $moduleId => $module ){
				if( preg_match( '/'.preg_quote( $term ).'/', $moduleId ) ){
					$modulesFound[$moduleId]	= $module;
				}
			}
		}
		else{
			$modulesAvailable	= $this->getAvailableModulesMap();
			$modulesInstalled	= $library->listInstalledModules();
			foreach( $modulesAvailable as $moduleId => $module ){
				if( preg_match( '/'.preg_quote( $term ).'/', $moduleId ) ){
					$modulesFound[$moduleId]	= $module;
				}
			}
		}
		$this->client->out( sprintf( $msgTotal, count( $modulesFound ), $shelfId ) );
		foreach( $modulesFound as $moduleId => $module ){
			if( $this->flags->verbose ){
				$msg	= sprintf( $msgEntry, $module->id, $module->version, $module->sourceId );
				$this->client->out( $msg );
				if( isset( $modulesInstalled[$module->id] ) ){
					$this->client->out( ' - Title:       '.$module->description );
					$moduleInstalled	= $modulesInstalled[$module->id];
					$this->client->out( ' - Installed:' );
					$this->client->out( '   - Version: '.$moduleInstalled->version );
					$this->client->out( '   - Source: '.$moduleInstalled->installSource );
					if( $this->flags->veryVerbose ){
						$this->client->out( ' - Description: '.$module->description );
						$moduleInfo	= new Hymn_Module_Info( $this->client );
						$moduleInfo->showModuleVersions( $moduleInstalled );
						$moduleInfo->showModuleFiles( $moduleInstalled );
						$moduleInfo->showModuleConfig( $moduleInstalled );
						$moduleInfo->showModuleRelations( $library, $moduleInstalled );
						$moduleInfo->showModuleHook( $moduleInstalled );
						$this->client->out();
					}
				}
				$this->client->out();
			}
			else{
				$msg	= sprintf( ' - '.$msgEntry, $module->id, $module->version, $module->sourceId );
				$this->client->out( $msg );
			}
		}
	}
}
