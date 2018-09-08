<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_List extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: force?(ignore disabled), verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config	= $this->client->getConfig();

		//  version 2: use library with loaded shelves
		//  comparison to replaced version 1:
		//  - more stable, OOP, less code, sources already evaluated
		//  - 50% slower than version 1 (180µs)
		#$start	= microtime( TRUE );
		$shelves		= $this->getLibrary()->getActiveShelves();
		$this->client->out( sprintf( "Found %d source(s):", count( $shelves ) ) );
		foreach( $shelves as $shelfId => $shelf ){
			$this->client->out();
			$this->client->out( "* ".$shelfId.":" );
			if( $shelf->title )
				$this->client->out( "  - Title:   ".$shelf->title );
			$this->client->out( "  - Type:    ".ucfirst( $shelf->type ) );
			$this->client->out( "  - Path:    ".$shelf->path );
			$this->client->out( "  - Active:  ".( $shelf->active ? 'yes' : 'no' ) );
			if( $shelf->default )
				$this->client->out( "  - Default: ".( $shelf->default ? 'yes' : 'no' ) );
		}
		#$this->client->outVerbose( 'Time: '.round( ( microtime( TRUE ) - $start ) * 1000 * 1000 ).'µs' );
	}
}
?>
