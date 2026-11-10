<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Self
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Self_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Downloads PHAR file of given version (tag or branch, default: master) and replaces installed one.
	 *	Implements flags: dry, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$version	= trim( $this->client->arguments->getArgument() ?? '' );
		$version	= '' !== $version ? $version : 'master';
		$updater	= new Hymn_Tool_PharUpdate();
		$url		= $updater->getUrl( $version );												//  throws exception on invalid version
		$pathFile	= Hymn_Tool_PharUpdate::detectInstalledFile();
		if( NULL === $pathFile )
			throw new RuntimeException( 'Hymn not found' );
		$this->out( 'Version installed: '.Hymn_Client::$version );
		$this->out( 'Download: '.$url );
		$this->client->outVerbose( 'Target: '.$pathFile );

		if( $this->flags->dry )
			return;
		$bytes	= $updater->update( $version, $pathFile );
		$this->client->outVerbose( 'Saved '.Hymn_Tool_FileSize::formatBytes( $bytes ).' to: '.$pathFile );
		if( !$this->flags->quiet ){
			$this->out( 'Version installed: ', FALSE );
			passthru( escapeshellarg( $pathFile ).' version' );									//  ask new version of replaced file
		}
	}
}
