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
 *	@package		CeusMedia.Hymn.Command.Config.Base
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config.Base
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 *	@deprecated		use command 'app-base-config-disable' instead
 *	@todo   		to be removed in v1.0
 */
class Hymn_Command_Config_Base_Disable extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		Hymn_Client::out( "" );																		//  print empty line as optical separator
		Hymn_Client::out( "DEPRECATED: Please use command 'app-base-config-disable' instead!" );	//  output deprecation notice
		Hymn_Client::out( "" );																		//  print empty line as optical separator
		$key		= $this->client->arguments->getArgument( 0 );

		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );
		$editor	= new Hymn_Tool_BaseConfigEditor( "config/config.ini" );

		if( !$editor->hasProperty( $key, FALSE ) )
			throw new InvalidArgumentException( 'Base config key "'.$key.'" is missing' );
		if( !$editor->isActiveProperty( $key ) ){
			if( !$this->flags->quiet )
				Hymn_Client::out( 'Base config key "'.$key.'" already is disabled' );
			return;
		}
		if( !$this->flags->dry ){
			$editor->deactivateProperty( $key );
			clearstatcache();
		}
		if( $this->flags->verbose )
			Hymn_Client::out( 'Base config key "'.$key.'" disabled' );
	}
}
