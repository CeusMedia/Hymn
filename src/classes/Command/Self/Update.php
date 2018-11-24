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
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Self_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected function downloadFile( $url, $file ){
		if( !( $fpSave = @fopen( $file, "wb" ) ) )													//  try to open target file for writing
			throw new RuntimeException( "Permission denied to change ".$file );						//  otherwise quit with exception
		if( !( $fpLoad = fopen( $url, "rb" ) ) )													//  try to open source URL for reading
			throw new RuntimeException( "Failed to open stream to URL" );							//  otherwise quit with exception
		while( !feof( $fpLoad ) )																	//  read source until end of file
			fwrite( $fpSave, fread( $fpLoad, 4096 ) );												//  copy 4K block from source to target
		fclose( $fpSave );																			//  close target file
		fclose( $fpLoad );																			//  close connection to source URL
	}

	protected function getHymnFilePath(){
		exec( "whereis hymn", $output/*, $b*/ );
		if( is_array( $output ) && count( $output ) ){
			foreach( $output as $line ){
				if( preg_match( '/^hymn: (.+)$/', $line ) ){
					return preg_replace( '/^hymn: /', '', $line );
				}
			}
		}
		return NULL;
	}

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$urlHymn	= "https://github.com/CeusMedia/Hymn/raw/master/hymn.phar";
		$pathFile	= $this->getHymnFilePath();
		if( !$pathFile )
			throw new Exception( "Hymn not found" );
		$this->client->out( "Download: ".$urlHymn );
		$this->downloadFile( $urlHymn, $pathFile );
		$this->client->out( "Saved to: ".$pathFile );
		$this->client->out( "Version installed: ", FALSE );
		passthru( "hymn version" );
	}
}
