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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
abstract class Hymn_Command_Source_Abstract extends Hymn_Command_Abstract
{
	protected function getShelfByArgument( int $position = 0, ?Hymn_Tool_CLI_Arguments $arguments = NULL )
	{
		$arguments  = $arguments ? $arguments : $this->client->arguments;
		$shelfId	= $arguments->getArgument( $position );

		if( !strlen( trim( $shelfId ) ) ){
			if( $this->flags->force )
				return;
			$this->client->outError( 'No source ID given.', Hymn_Client::EXIT_ON_INPUT );
		}

		$shelves	= $this->getLibrary()->getShelves();
		if( !array_key_exists( $shelfId, $shelves ) ){
			if( $this->flags->force )
				return;
			$this->client->outError( 'Given source ID is invalid.', Hymn_Client::EXIT_ON_INPUT );
		}
		return $shelves[$shelfId];
	}
}
