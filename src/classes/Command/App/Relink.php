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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Relink extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no?(if new 3rd arg would be target database), dry, force, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config			= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		if( $config->application->installType !== 'link' ){
			$this->client->outVerbose( "Application has not been installed in link mode. Aborting." );
			return;
		}

		$sourcePath	= trim( $this->client->arguments->getArgument( 0 ) );

		if( !strlen( trim( $sourcePath ) ) )
			throw new InvalidArgumentException( 'First argument "source" is missing' );
		$sourceUriRegex	= '/^'.preg_quote( $sourcePath, '/' ).'/';
		$destPath		= rtrim( getcwd(), '/' ).'/';

		$this->out( "Move application" );
		$this->out( "- from: ".$sourcePath );
		$this->out( "- to:   ".$destPath );

		$this->client->outVerbose( $this->flags->dry ? "- would update hymn file" : "- updating hymn file" );
		$this->updateHymnFile( $config, $sourceUriRegex, $destPath );

		$this->client->outVerbose( $this->flags->dry ? "- would fix links" : "- fixing links" );
		$this->fixLinks( $sourcePath, $sourceUriRegex, $destPath );
		$this->out( "Done." );
	}

	protected function fixLinks( string $source, string $sourceUriRegex, string $dest, string $path = '' )
	{
		$index	= new DirectoryIterator( $this->flags->dry ? $source.$path : $dest.$path );
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

	protected function updateHymnFile( $config, string $sourceUriRegex, string $dest )
	{
		$this->client->outVerbose( "  - setting URI in hymn file" );
		$config->application->uri	= $dest;

		$this->client->outVerbose( "  - update module sources in hymn file" );
		foreach( $config->sources as $sourceData )
			foreach( $sourceData as $key => $value )
				if( $key === 'path' )
					$sourceData->path	= preg_replace( $sourceUriRegex, $dest, $value );

		if( !$this->flags->dry ){
			$json	= json_encode( $config, JSON_PRETTY_PRINT );
			file_put_contents( Hymn_Client::$fileName, $json );
		}
	}
}
