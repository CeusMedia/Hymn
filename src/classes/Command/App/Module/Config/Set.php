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
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Module_Config_Set extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$dry		= $this->client->arguments->getOption( 'dry' );
		$quiet		= $this->client->arguments->getOption( 'quiet' );
		$verbose	= $this->client->arguments->getOption( 'verbose' );

		$key		= $this->client->arguments->getArgument( 0 );
		$value		= $this->client->arguments->getArgument( 1 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'First argument "key" is missing' );

		$parts		= explode( ".", $key );
		$moduleId	= array_shift( $parts );
		if( !$parts )
			throw new InvalidArgumentException( 'Key must be of syntax "Module_Name.(section.)key"' );
		$configKey	= join( ".", $parts );

		$configurator	= new Hymn_Module_Config( $this->client, $this->getLibrary(), $quiet );
		$config			= $configurator->set( $moduleId, $configKey, $value, $verbose, $dry );
	}
}