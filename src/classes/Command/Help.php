<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){

		$action		= $this->client->arguments->getArgument( 0 );
		$className	= "Hymn_Command_Default";
		if( strlen( $action ) ){
			$command	= ucwords( preg_replace( "/-+/", " ", $action ) );
			$className	= "Hymn_Command_".preg_replace( "/ +/", "_", $command );
			if( !class_exists( $className ) )
				throw new InvalidArgumentException( 'Command "'.$action.'" is not existing' );
			$class	= new ReflectionClass( $className );
			$object	= $class->newInstanceArgs( array( $this->client ) );
			call_user_func( array( $object, 'help' ) );
			return;
		}

		$config		= $this->client->getConfig();
		$lines		= file( "phar://hymn.phar/locales/en/help/default.txt" );
		Hymn_Client::out( $lines );
		return;
  }
}
