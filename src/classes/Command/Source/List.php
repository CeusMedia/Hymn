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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_List extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config	= $this->client->getConfig();
		$app	= $this->config->application;														//  shortcut to application config

		if( !isset( $config->sources ) )
			$config->sources	= (object) array();

		Hymn_Client::out( sprintf( "Found %d source(s):", count( (array) $config->sources ) ) );
		foreach( $config->sources as $sourceId => $source ){
			$status		= 'not defined';
			if( isset( $source->path ) && strlen( trim ( $source->path ) ) )
			 	$status	= 'not existing';
			if( isset( $source->path ) && file_exists( $source->path ) )
			 	$status	= 'defined and existing';
			$active	= isset( $source->path ) && file_exists( $source->path ) && $source->active;
			Hymn_Client::out( "* ".$sourceId.":" );
			Hymn_Client::out( "  - Status: ".$status );
			Hymn_Client::out( "  - Active: ".( $active ? 'yes' : 'no' ) );
			Hymn_Client::out( "  - Type: ".$source->type );
			Hymn_Client::out( "  - Path: ".$source->path );
		}
	}
}
?>
