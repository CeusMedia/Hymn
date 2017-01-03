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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$action		= $this->client->arguments->getArgument( 0 );									//  get first argument as action
		$className	= "Hymn_Command_Default";														//  @todo remove this line since it is not used anymore
		if( strlen( $action ) ){																	//  a help topic has been given (by first argument after 'help')
			$command	= ucwords( preg_replace( "/-+/", " ", $action ) );							//  resolve folder and classes from command
			$className	= "Hymn_Command_".preg_replace( "/ +/", "_", $command );					//  build possible command class name
			if( !class_exists( $className ) )														//  built command class it not existing
				throw new InvalidArgumentException( "Command '".$action."' is not existing" );		//  quit with exception
			$class	= new ReflectionClass( $className );											//  otherwise reflect command class
			$object	= $class->newInstanceArgs( array( $this->client ) );							//  invoke command class
			call_user_func( array( $object, 'help' ) );												//  call public custom public help method of command class
			return;																					//  return here to avoid fallback
		}
		$lines		= file( "phar://hymn.phar/locales/en/help/default.txt" );						//  point to default help text file
		Hymn_Client::out( $lines );																	//	print default help text
	}
}
