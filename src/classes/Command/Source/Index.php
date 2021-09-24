<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_Index extends Hymn_Command_Source_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags: dry, force, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		if( !( $shelfId = $this->getShelfByArgument() ) )
			return;

		$library	= $this->getLibrary();
		if( !$library->getShelves() )
			$this->outError( 'No modules sources configured.', Hymn_Client::EXIT_ON_RUN );

		if( !$this->evaluateShelfId( $shelfId, FALSE ) ){
			$message	= sprintf( 'Source ID "%s" is invalid.', $shelfId );
			$this->outError( $message, Hymn_Client::EXIT_ON_INPUT );
		}

		$shelf		= $library->getShelf( $shelfId );
		$modules	= $library->getAvailableModuleFromShelf( $shelfId );
		if( !count( $modules ) )
			$this->outError( 'No available modules found.', Hymn_Client::EXIT_ON_RUN );

		$this->out( vsprintf( 'Found %2$d available modules in source %2$s', [
			$shelfId, count( $modules ),
		] ) );


		if( file_exists( $shelf->path.'index.serial' ) ){
			$this->out( 'Found index serial file.' );
			// ....

		}
		else if( file_exists( $shelf->path.'index.json' ) ){
			$this->out( 'Found index JSON file.' );
			// ....

		}

//		if( !$this->flags->quiet )
//			$this->client->out( 'Source "'.$shelf->id.'" has been enabled.' );
	}
}
