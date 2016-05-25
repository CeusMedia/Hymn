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
 */
abstract class Hymn_Command_Abstract{

	public function __construct( Hymn_Client $client ){
		$this->client = $client;
	}

	public function help(){
		$class		= preg_replace( "/^Hymn_Command_/", "", get_class( $this ) );
		$command	= strtolower( str_replace( "_", "-", $class ) );
		$fileName	= "phar://hymn.phar/locales/en/help/".$command.".txt";
		if( file_exists( $fileName ) )
			Hymn_Client::out( file( $fileName ) );
		else{
			Hymn_Client::out();
			Hymn_Client::out( "Outch! Help on this topic is not available yet. I am sorry :-/" );
			Hymn_Client::out();
			Hymn_Client::out( "But YOU can improve this situation :-)" );
			Hymn_Client::out( "- get more information on: https://ceusmedia.de/" );
			Hymn_Client::out( "- make a fork or patch on: https://github.com/CeusMedia/Hymn" );
			Hymn_Client::out();
		}
	}
}
