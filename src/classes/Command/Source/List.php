<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_List extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: force?(ignore disabled), verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$library	= $this->getLibrary();
		$shelves	= $library->getActiveShelves();
		$this->out( sprintf( 'Found %d source(s):', count( $shelves ) ) );
		foreach( $shelves as $sourceId => $source ){
			$modules	= $library->getAvailableModules( $sourceId );
			$this->out( [
				'* '.$sourceId.':',
				'  - Title:    '.$source->title,
				'  - Type:     '.ucfirst( $source->type ),
				'  - Path:     '.$source->path,
				'  - Active:   '.( $source->active ? 'yes' : 'no' ),
				'  - Default:  '.( $source->default ? 'yes' : 'no' ),
				'  - Modules:  '.count( $modules ),
			] );
			if( !empty( $source->date ) )
				$this->out( '  - Date:     '.$source->date );
			if( !empty( $source->url ) )
				$this->out( '  - Link:     '.$source->url );

			if( $this->flags->verbose ){
				foreach( $modules as $moduleId => $module )
					$this->out( '    - '.$moduleId.' ('.$module->version.')' );
			}
		}
	}
}
