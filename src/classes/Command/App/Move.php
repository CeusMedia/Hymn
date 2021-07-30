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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Move extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	const ACTION_CREATE_FOLDER	= 1;
	const ACTION_MOVE_FILE		= 2;
	const ACTION_LINK_FILE		= 3;
	const ACTION_REMOVE_FOLDER	= 4;

	protected $regExpSource;

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no?(if new 3rd arg would be target database), dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->flags->dry )
			$this->client->out( "## DRY RUN: Simulated actions - no changes will take place." );

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
		$source		= rtrim( $config->application->uri, '/' ).'/';
		$sourceUriRegex	= '/^'.preg_quote( $source, '/' ).'/';

		$this->client->out( "Move application" );
		$this->client->out( "- from: ".$source );
		$this->client->out( "- to:   ".$dest );
		if( strlen( $url ) )
			$this->client->out( "- URL:  ".$url );

		$this->updateConfigFile( $url );
		$this->updateHymnFile( $config, $url, $sourceUriRegex, $dest );
		$this->moveProject( $source, $dest );

		if( !$this->flags->dry ){
			$this->client->outVerbose( "- fixing links" );
		}
		else{
			$this->client->outVerbose( "- would fix links" );
		}
		$this->fixLinks( $source, $sourceUriRegex, $dest );
		$this->client->out( "DONE!" );
		$this->client->out( "Now run: cd ".$dest." && make set-permissions" );
	}

	protected function moveProject( $source, $dest ){
		if( $this->flags->dry ){
			$this->client->outVerbose( "- would move from ".$source." to ".$dest );
			return;
		}
		$this->client->outVerbose( "- moving folders, files and links" );
		rename( $source, $dest );
	}

	protected function fixLinks( $source, $sourceUriRegex, $dest, $path = '' ){
		if( !$this->flags->dry )
			$index	= new DirectoryIterator( $dest.$path );
		else
			$index	= new DirectoryIterator( $source.$path );
		foreach( $index as $entry ){
			$pathName	= $entry->getPathname();
			if( $entry->isDot() )
				continue;
			if( $entry->isDir() )
				$this->fixLinks( $source, $sourceUriRegex, $dest, $path.$entry->getFilename().'/' );
			else if( is_link( $pathName ) ){
				$link = readlink( $pathName );
				if( preg_match( $sourceUriRegex, $link ) ){
					$link	= preg_replace( $sourceUriRegex, $dest, $link );
					$this->client->outVeryVerbose( '  - '.preg_replace( $sourceUriRegex, '', readlink( $pathName ) ) );
					if( !$this->flags->dry ){
						unlink( $pathName );
						symlink( $link, $pathName );
					}
				}
			}
		}
	}

	protected function updateConfigFile( $url ){
		if( !strlen( $url ) )
			return;
		$pathConfig	= $this->client->getConfigPath();
		$editor	= new Hymn_Tool_BaseConfigEditor( $pathConfig."config.ini" );
		if( $editor->hasProperty( 'app.base.url', FALSE ) ){
			if( !$this->flags->dry ){
				$this->client->outVerbose( "- setting URL in config file" );
				$editor->setProperty( 'app.base.url', $url );
				clearstatcache();
			}
			else
				$this->client->outVerbose( "- would set URL in config file" );
		}
	}

	protected function updateHymnFile( $config, $url, $sourceUriRegex, $dest ){
		if( !$this->flags->dry )
			$this->client->outVerbose( "- updating hymn file" );
		else
			$this->client->outVerbose( "- would update hymn file" );
		if( strlen( $url ) ){
			$this->client->outVerbose( "  - setting URL in hymn file" );
			$config->application->url	= $url;
		}
		$this->client->outVerbose( "  - setting URI in hymn file" );
		$config->application->uri	= $dest;

		$this->client->outVerbose( "  - update module sources in hymn file" );
		foreach( $config->sources as $sourceKey => $sourceData )
			foreach( $sourceData as $key => $value )
				if( $key === 'path' )
					$sourceData->path	= preg_replace( $sourceUriRegex, $dest, $value );

		if( !$this->flags->dry ){
			$json	= json_encode( $config, JSON_PRETTY_PRINT );
			file_put_contents( Hymn_Client::$fileName, $json );
		}
	}
}
