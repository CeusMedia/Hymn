<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2023 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Self_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected $pharDownloadUrl	= 'https://github.com/CeusMedia/Hymn/raw/<VERSION>/hymn.phar';

	/**
	 *	Execute this command.
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$version	= $this->client->arguments->getArgument();
		$version	= strlen( trim( $version ) ) ? $version : 'master';
		if( !$version === 'master' && !preg_match( '/^[0-9.]+$/', $version ) )
			throw new InvalidArgumentException( 'No valid version given: '.$version );
		$urlHymn	= str_replace( '<VERSION>', $version, $this->pharDownloadUrl );
		$pathFile	= $this->getHymnFilePath();
		if( !$pathFile )
			throw new Exception( 'Hymn not found' );
		if( !$this->flags->quiet ){
			$this->out( 'Version installed: '.Hymn_Client::$version );
			$this->out( 'Download: '.$urlHymn );
		}
		if( !$this->flags->dry ){
			$this->downloadFile( $urlHymn, $pathFile );
			$this->client->outVerbose( 'Saved to: '.$pathFile );
		}
		if( !$this->flags->quiet ){
			$this->out( 'Version installed: ', FALSE );
				passthru( 'hymn version' );
		}
	}

	protected function downloadFile( string $url, string $file )
	{
		if( !( $fpSave = @fopen( $file, 'wb' ) ) )													//  try to open target file for writing
			throw new RuntimeException( 'Permission denied to change '.$file );						//  otherwise quit with exception
		if( !( $fpLoad = fopen( $url, 'rb' ) ) )													//  try to open source URL for reading
			throw new RuntimeException( 'Failed to open stream to URL' );							//  otherwise quit with exception
		while( !feof( $fpLoad ) )																	//  read source until end of file
			fwrite( $fpSave, fread( $fpLoad, 4096 ) );												//  copy 4K block from source to target
		fclose( $fpSave );																			//  close target file
		fclose( $fpLoad );																			//  close connection to source URL
	}

	protected function getHymnFilePath()
	{
		exec( 'whereis hymn', $output/*, $b*/ );
		if( is_array( $output ) && count( $output ) ){
			foreach( $output as $line ){
				if( preg_match( '/^hymn: (.+)$/', $line ) ){
					return preg_replace( '/^hymn: /', '', $line );
				}
			}
		}
		return NULL;
	}
}
