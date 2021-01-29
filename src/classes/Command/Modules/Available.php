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
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Modules
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Modules_Available extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose?
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$library	= $this->getLibrary();
		$shelfId	= $this->client->arguments->getArgument( 0 );
		$shelfId	= $this->evaluateShelfId( $shelfId );

		$modules	= $library->getAvailableModules( $shelfId );
		if( count( $modules ) ){
			$message	= 'Found '.count( $modules ).' available modules:';
			if( $shelfId )
				$message	= 'Found '.count( $modules ).' available modules in source '.$shelfId.':';
		}
		else{
			$message	= 'No available modules found.';
			if( $shelfId )
				$message	= 'No available modules found in source '.$shelfId.'.';
			if( !$library->getShelves() )
				$message	= 'No available modules found. No modules sources configured.';
		}
		$this->client->out( $message );
		foreach( $modules as $module ){
			$line	= $module->id.' ('.$module->version.')';
			if( $module->isDeprecated )
				$line	.= ' [deprecated]';
			$this->client->out( '- '.$line );
		}
	}
}
