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
		$command	= $this->client->arguments->getArgument( 0 );				//  get first argument as command
		$locale		= $this->client->getLocale();								//  shortcut client locale handler
		$words		= $locale->loadWords( 'command/help' );

		$command	= strlen( trim( $command ) ) ? $command : 'help';			//  set default command to show help index
		$path		= str_replace( '-', '/', strtolower( trim( $command ) ) );	//  realize locale file path
		$message	= $locale->loadText( 'command/help.miss' );					//  load fallback help text locale
		if( $locale->hasText( 'command/'.$path ) )								//  help text locale exists
			$message	= $locale->loadText( 'command/'.$path );				//  load help text locale

		$this->client->out( $message );											//	print command help text
		return TRUE;
	}
}
