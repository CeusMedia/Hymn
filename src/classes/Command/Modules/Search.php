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
	 *	Implements flags:
	 *	Missing flags: verbose?
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();
		$term		= $this->client->arguments->getArgument( 0 );
		$shelfId	= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->evaluateShelfId( $shelfId );

		$msgTotal		= '%d module(s) found in all module sources:';
		$msgEntry		= '- %s (%s)';
		$foundModules	= array();

		if( $shelfId ){
			$msgTotal	= '%d module(s) found in module source "%s":';
			$msgEntry	= '- %s (%s)';
			$availableModules	= $this->getAvailableModulesMap( $shelfId );
			foreach( $availableModules as $moduleId => $module ){
				if( preg_match( '/'.preg_quote( $term ).'/', $moduleId ) ){
					$foundModules[$moduleId]	= $module;
				}
			}
		}
		else{
			$availableModules	= $this->getAvailableModulesMap();
			foreach( $availableModules as $moduleId => $module ){
				if( preg_match( '/'.preg_quote( $term ).'/', $moduleId ) ){
					$foundModules[$moduleId]	= $module;
				}
			}
		}
		$this->client->out( sprintf( $msgTotal, count( $foundModules ), $shelfId ) );
		foreach( $foundModules as $moduleId => $module ){
			$msg	= sprintf( $msgEntry, $module->id, $module->version, $module->sourceId );
			$this->client->out( $msg );
		}
	}
}
