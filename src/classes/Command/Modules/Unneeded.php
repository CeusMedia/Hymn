<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Modules_Unneeded extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$config		= $this->client->getConfig();
//		$this->client->getDatabase()->connect();													//  setup connection to database
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		foreach( $config->modules as $moduleId => $moduleConfig ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			$sourceId	= NULL;
			if( !empty( $moduleConfig->source ) )
				$sourceId = $moduleConfig->source;
			$module			= $library->getAvailableModule( $moduleId, $sourceId );
			if( !$module->isActive )
				continue;
			$relation->addModule( $module );
		}
		$modulesNeeded	= array_keys( $relation->getOrder() );

		$modulesInstalled = array();
		foreach( $library->listInstalledModules() as $module )
			$modulesInstalled[]	= $module->id;
		$modules	= array_diff( $modulesInstalled, $modulesNeeded );
		$lines	= array();
		if( count( $modules )){
			$lines[]	= 'Found '.count( $modules ).' unneeded modules:';
			foreach( $modules as $module )
				$lines[]	= ' - '.$module;
		}
		else
			$lines[]	= 'Found no unneeded modules.';
		$this->client->out( $lines );
	}
}
