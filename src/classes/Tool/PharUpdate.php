<?php
declare(strict_types=1);

/**
 *	Downloads a PHAR file of Hymn and replaces an installed one safely.
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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Downloads a PHAR file of Hymn and replaces an installed one safely:
 *	the download goes into a temporary file next to the target, is validated
 *	and then moved over the target in one step, keeping its permissions.
 *	On any error, the installed file stays untouched.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_PharUpdate
{
	public const DEFAULT_URL		= 'https://github.com/CeusMedia/Hymn/raw/<VERSION>/hymn.phar';

	protected string $url;

	public function __construct( string $url = self::DEFAULT_URL )
	{
		$this->url	= $url;
	}

	/**
	 *	Returns path of installed Hymn PHAR file: the running one, else the one found in PATH.
	 *	Symbolic links are resolved, so the linked file is replaced, not the link.
	 *	@return		string|NULL
	 */
	public static function detectInstalledFile(): ?string
	{
		$path	= Phar::running( FALSE );
		if( '' === $path ){
			$found	= trim( (string) shell_exec( 'command -v hymn 2>/dev/null' ) );
			$path	= explode( PHP_EOL, $found )[0];
		}
		if( '' === $path )
			return NULL;
		return realpath( $path ) ?: NULL;
	}

	/**
	 *	Returns download URL for version, which is a tag or branch, eg. 1.0.4, 1.0.x or master.
	 *	@param		string		$version
	 *	@return		string
	 *	@throws		InvalidArgumentException	if version is not a valid tag or branch name
	 */
	public function getUrl( string $version ): string
	{
		if( !self::isValidVersion( $version ) )
			throw new InvalidArgumentException( 'No valid version given: '.$version );
		return str_replace( '<VERSION>', $version, $this->url );
	}

	/**
	 *	Indicates whether version is a valid tag or branch name, without paths.
	 *	@param		string		$version
	 *	@return		bool
	 */
	public static function isValidVersion( string $version ): bool
	{
		return 1 === preg_match( '/^[0-9a-z][0-9a-z._-]*$/i', $version ) && !str_contains( $version, '..' );
	}

	/**
	 *	Downloads PHAR file of version and replaces target file.
	 *	@param		string		$version		Tag or branch, eg. 1.0.4 or master
	 *	@param		string		$targetFile		File to replace
	 *	@return		int			Number of downloaded bytes
	 *	@throws		RuntimeException			if target is not writable, download failed or is not a PHAR file
	 */
	public function update( string $version, string $targetFile ): int
	{
		$url		= $this->getUrl( $version );
		$folder		= dirname( $targetFile );
		if( !is_writable( $folder ) || ( file_exists( $targetFile ) && !is_writable( $targetFile ) ) )
			throw new RuntimeException( 'Permission denied to change '.$targetFile );

		$tempFile	= tempnam( $folder, '.hymn-update-' );
		if( FALSE === $tempFile )
			throw new RuntimeException( 'Creating temporary file in '.$folder.' failed' );
		try{
			$content	= @file_get_contents( $url );
			if( FALSE === $content || '' === $content )
				throw new RuntimeException( 'Download from '.$url.' failed' );
			if( !str_contains( $content, '__HALT_COMPILER();' ) )
				throw new RuntimeException( 'Download from '.$url.' is not a PHAR file' );
			if( FALSE === file_put_contents( $tempFile, $content ) )
				throw new RuntimeException( 'Writing temporary file '.$tempFile.' failed' );
			$mode	= file_exists( $targetFile ) ? fileperms( $targetFile ) & 0777 : 0755;
			chmod( $tempFile, $mode );
			if( !rename( $tempFile, $targetFile ) )
				throw new RuntimeException( 'Replacing '.$targetFile.' failed' );
			return strlen( $content );
		}
		finally{
			if( file_exists( $tempFile ) )
				unlink( $tempFile );
		}
	}
}
