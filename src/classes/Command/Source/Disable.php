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
class Hymn_Command_Source_Disable extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags: dry, force, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$shelfId	= $this->client->arguments->getArgument( 0 );

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

		$shelf	= $shelves[$shelfId];
		if( !$shelf->active && !$this->flags->force ){
			$this->client->outVerbose( 'Source "'.$shelfId.'" already disabled.' );
			return;
		}

		$installedShelfModules	= $this->getLibrary()->listInstalledModules( $shelfId );
		if( count( $installedShelfModules ) && !$this->flags->force ){
			$this->client->outError( sprintf(
				'Source cannot be disabled since %d installed modules are related.',
				count( $installedShelfModules )
			), Hymn_Client::EXIT_ON_EXEC );
		}

		if( $this->flags->dry ){
			if( !$this->flags->quiet )
				$this->client->out( 'Source "'.$shelfId.'" would have been disabled.' );
		}
		else{
			$json	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
			$json->sources->{$shelfId}->active	= FALSE;
			if( isset( $json->sources->{$shelfId}->default ) )
				unset( $json->sources->{$shelfId}->default );
			file_put_contents( Hymn_Client::$fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
			if( !$this->flags->quiet )
				$this->client->out( 'Source "'.$shelfId.'" has been disabled.' );
		}

	}
}
?>
