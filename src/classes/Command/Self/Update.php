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
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Self_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected function downloadFile( $url, $file ){
		$file	= $file ? $file : basename( $file );
		$fp1	= fopen( $url, "rb" );
		if( !$fp1 )
			throw new Exception( "Failed to open stream to URL" );
		$fp2 = fopen( $file, "wb" );
		while( !feof( $fp1 ) )
			fwrite( $fp2, fread( $fp1, 1024 ) );
		fclose( $fp1 );
		fclose( $fp2 );
	}

	protected function getHymnFilePath(){
		exec( "whereis hymn", $a, $b );
		if( is_array( $a ) && count( $a ) ){
			foreach( $a as $item ){
				if( preg_match( '/^hymn: (.+)$/', $item ) ){
					return preg_replace( '/^hymn: /', '', $item );
				}
			}
		}
		return NULL;
	}

	public function run(){
		$urlHymn	= "https://github.com/CeusMedia/Hymn/raw/master/hymn.phar";
		$pathFile	= $this->getHymnFilePath();
		if( !$pathFile )
			throw new Exception( "Hymn not found" );
		Hymn_Client::out( "Download: ".$urlHymn );
		$this->downloadFile( $urlHymn, $pathFile );
		Hymn_Client::out( "Saved to: ".$pathFile );
		passthru( "hymn version" );
	}
}
