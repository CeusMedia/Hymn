<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Modules_Required extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose?
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 *	@todo		replace by recursive library solution or remove
	 */
	public function run()
	{
		$modules	= (array) $this->client->getConfig()->modules;
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		foreach( $modules as $moduleId => $moduleConfig ){
			$module		= $library->getAvailableModule( $moduleId, NULL, FALSE );
			if( !$module ){
				$this->client->out( "! ".$moduleId.' MISSING' );
				continue;
			}
			$relation->addModule( $module );
		}
		$listInstalled	= [];
		$listRequired	= [];
		$modules	= $relation->getOrder();
//		$this->client->out( count( $modules )." modules required:" );
		foreach( $modules as $module ){
			if( $library->isInstalledModule( $module->id ) )
				$listInstalled[]	= $module;
			else
				$listRequired[]	= $module;
		}

		if( !count( $listRequired ) ){
			if( !$this->flags->verbose )
				$this->client->out( 'All '.count( $listInstalled ).' required module(s) installed' );
		}
		if( count( $listInstalled ) ){
			$this->client->outVerbose( count( $listInstalled ).' required module(s) installed:' );
			foreach( $listInstalled as $module ){
				$this->client->outVerbose( "- ".$module->id.'' );
			}
		}
		if( count( $listRequired ) ){
			$this->client->out( count( $listRequired ).' required module(s) NOT INSTALLED:' );
			foreach( $listRequired as $module ){
				$this->client->out( "- ".$module->id.'' );
			}
		}
	}
}
