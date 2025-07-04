<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Module_Reconfigure extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( $this->client->flags & Hymn_Client::FLAG_DRY )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

	//	$config			= $this->client->getConfig();
		$library		= $this->getLibrary();
		$moduleId		= trim( $this->client->arguments->getArgument() );

		if( !$moduleId )
			$this->outError( "No module id given", Hymn_Client::EXIT_ON_INPUT );
		else if( !$library->isInstalledModule( $moduleId ) )
			$this->outError( "Module '".$moduleId."' is not installed", Hymn_Client::EXIT_ON_SETUP );
		else{
			$moduleLocal	= $library->readInstalledModule( $moduleId );
			$moduleSource	= $library->getAvailableModule( $moduleId, $moduleLocal->install->source );
			$updater		= new Hymn_Module_Updater( $this->client, $library );
			$updater->reconfigure( $moduleSource );
		}
	}
}
