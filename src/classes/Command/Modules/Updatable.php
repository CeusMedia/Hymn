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
 *	@todo			code documentation
 *	@todo			handle relations (new relations after update)
 */
class Hymn_Command_Modules_Updatable extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose?
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
//		$config		= $this->client->getConfig();
//		$relation	= new Hymn_Module_Graph( $this->client, $this->getLibrary() );					//  @todo use to find new required modules

		/* @todo	find a better solution
					this is slow, because:
					- list* methods read from disk
 					- list* methods do not use a cache atm (this is prepared but disable)
 					- the module updater will read the list AGAIN
					solutions:
					- have a disk reading "count installed modules" method
					- let module updater cache list on first listing, use cache later
		*/
		if( !$this->getLibrary()->listInstalledModules() )											//  application has no installed modules
			$this->outError( "No installed modules found", Hymn_Client::EXIT_ON_SETUP );

		$moduleUpdater		= new Hymn_Module_Updater( $this->client, $this->getLibrary() );		//  use module updater on current application installation
		$modulesUpdatable	= $moduleUpdater->getUpdatableModules();
		if( !$modulesUpdatable ){
			$this->out( 'No modules outdated.' );
			return;
		}

		$this->out( count( $modulesUpdatable ).' module(s) outdated:' );
		foreach( $modulesUpdatable as $update ){												//  iterate list of outdated modules
			$this->out( vsprintf( "- %s: %s -> %s", [									//  print outdated module and:
				$update->id,																	//  - module ID
				$update->installed,																//  - currently installed version
				$update->available																//  - available version
			] ) );
		}
	}
}
