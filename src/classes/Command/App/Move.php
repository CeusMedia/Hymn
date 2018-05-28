<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Move extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	const ACTION_CREATE_FOLDER	= 1;
	const ACTION_MOVE_FILE		= 2;
	const ACTION_LINK_FILE		= 3;
	const ACTION_REMOVE_FOLDER	= 4;

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->flags->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

		$dest	= trim( $this->client->arguments->getArgument( 0 ) );
		$url	= trim( $this->client->arguments->getArgument( 1 ) );
		$url	= $url ? rtrim( $url, '/' ).'/' : '';

		if( !strlen( trim( $dest ) ) )
			throw new InvalidArgumentException( 'First argument "destination" is missing' );
		if( !preg_match( '/^\//', $dest ) )
			throw new InvalidArgumentException( 'Destination must be absolute' );
		$dest	= rtrim( $dest, '/' ).'/';
		if( file_exists( $dest ) )
			throw new RuntimeException( 'Destination folder is already existing.' );

		$config		= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		if( !isset( $config->application->uri ) || !strlen( trim( $config->application->uri ) ) )
			throw new RuntimeException( 'No application URI configured.' );
		$source	= rtrim( $config->application->uri, '/' ).'/';

		Hymn_Client::out( "Move application" );
		Hymn_Client::out( "- from: ".$source );
		Hymn_Client::out( "- to:   ".$dest );
		if( strlen( $url ) )
			Hymn_Client::out( "- URL:  ".$url );
		if( !$this->flags->dry ){
			if( strlen( $url ) ){
				if( $this->flags->verbose )
					Hymn_Client::out( "- setting URL in config file" );
				$pathConfig	= $this->client->getConfigPath();
				$editor	= new Hymn_Tool_BaseConfigEditor( $pathConfig."config.ini" );
				if( $editor->hasProperty( 'app.base.url', FALSE ) ){
					if( !$this->flags->dry ){
						$editor->setProperty( 'app.base.url', $url );
						clearstatcache();
					}
				}
				if( $this->flags->verbose )
					Hymn_Client::out( "- setting URL in hymn file" );
				$config->application->url	= $url;
				$json	= json_encode( $config, JSON_PRETTY_PRINT );
				file_put_contents( Hymn_Client::$fileName, $json );
			}
			if( $this->flags->verbose )
				Hymn_Client::out( "- setting URI in hymn file" );
			$config->application->uri	= $dest;
			$json	= json_encode( $config, JSON_PRETTY_PRINT );
			file_put_contents( Hymn_Client::$fileName, $json );

			if( $this->flags->verbose )
				Hymn_Client::out( "- moving folder, files and links" );
			rename( $source, $dest );
			if( $this->flags->verbose )
				Hymn_Client::out( "- fixing links" );
			$this->fixLinks( $source, $dest );
			Hymn_Client::out( "DONE!" );
			Hymn_Client::out( "Now run: cd ".$dest." && make set-permissions" );
		}
	}

	protected function fixLinks( $source, $dest, $path = '' ){
		$index	= new DirectoryIterator( $dest.$path );
		foreach( $index as $entry ){
			$pathName	= $entry->getPathname();
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->fixLinks( $source, $dest, $path.$entry->getFilename().'/' );
			else if( is_link( $pathName ) ){
				$link = readlink( $pathName );
				if( preg_match( "/^".preg_quote( $source, "/" )."/", $link ) ){
					$link	= preg_replace( "/^".preg_quote( $source, "/" )."/", $dest, $link );
					unlink( $pathName );
					symlink( $link, $pathName );
				}
			}
		}
	}
}