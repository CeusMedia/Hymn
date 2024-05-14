<?php
/**
 *	Locale singleton handler.
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Locale singleton handler.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_Locale
{
	public const TYPE_UNKNOWN	= 0;
	public const TYPE_TEXT		= 1;
	public const TYPE_WORDS		= 2;

	protected string $baseUri		= 'phar://hymn.phar/locales/';
	protected string $language;
	protected string $version;

	/**
	 *	Constructor, shortcutting client version and language.
	 *	@access		public
	 *	@param		?string		$language
	 *	@return		void
	 */
	public function __construct( string $language = NULL )
	{
		$this->version		= Hymn_Client::$version;
		$this->setLanguage( $language ?? Hymn_Client::$language );
	}

	/**
	 *	Returns language currently set for locale.
	 *	@access		public
	 *	@return		?string		Language currently set for locale
	 */
	public function getLanguage(): ?string
	{
		return $this->language;
	}

	/**
	 *	Indicates whether locale file exists by path and type.
	 *	@access		public
	 *	@param		string		$path		Path of file
	 *	@param		integer		$type		Type of locale file
	 *	@return		boolean
	 *	@throws		RuntimeException		if given type is invalid
	 */
	public function has( string $path, int $type ): bool
	{
		if( $type === static::TYPE_TEXT )
			return $this->hasText( $path );
		else if( $type === static::TYPE_WORDS )
			return $this->hasWords( $path );
		else
			throw new RangeException( 'Invalid type given' );
	}

	public function hasText( string $path ): bool
	{
		$filePath	= $this->baseUri.$this->language.'/'.$path.'.txt';
		if( !file_exists( $filePath ) )
			return FALSE;
		return TRUE;
	}

	public function hasWords( string $path ): bool
	{
		$filePath	= $this->baseUri.$this->language.'/'.$path.'.ini';
		if( !file_exists( $filePath ) )
			return FALSE;
		return TRUE;
	}

	/**
	 *	Tries to load locale file by path and type.
	 *	@access		public
	 *	@param		string				$path		Path of text file
	 *	@return		string|object		Content of locale file
	 *	@throws		RuntimeException	if text file is not existing
	 */
	public function load( string $path, int $type ): string|object
	{
		if( $type === static::TYPE_TEXT )
			return $this->loadText( $path );
		else if( $type === static::TYPE_WORDS )
			return $this->loadWords( $path );
		else
			throw new RangeException( 'Invalid type given' );
	}

	/**
	 *	Tries to load locale text by path.
	 *	@access		public
	 *	@param		string		$path		Path of text file
	 *	@return		string		Content of locale file
	 *	@throws		RuntimeException		if text file is not existing
	 */
	public function loadText( string $path ): string
	{
		if( !$this->hasText( $path ) ){
			throw new RuntimeException( sprintf(
				'Missing text file for "%s"',
				$path
			) );
		}
		$filePath	= $this->baseUri.$this->language.'/'.$path.'.txt';
		/** @var string $text */
		$text		= file_get_contents( $filePath );								//  read existing text file
		$text		= str_replace( "%version%", $this->version, $text );		//  insert client version
		$text		= str_replace( "%language%", $this->language, $text );	//  insert client language
		return $text;																//  return text as string
	}

	/**
	 *	Tries to load locale words by path.
	 *	@access		public
	 *	@param		string		$path		Path of words file
	 *	@return		object		Object map of words
	 *	@throws		RuntimeException		if words file is not existing
	 */
	public function loadWords( string $path ): object
	{
		if( !$this->hasWords( $path ) ){
			throw new RuntimeException( sprintf(
				'Missing words file for "%s"',
				$path
			) );
		}
		$filePath	= $this->baseUri.$this->language.'/'.$path.'.ini';
		/** @var string $text */
		$text		= file_get_contents( $filePath );								//  read existing words file
		$text		= str_replace( "%version%", $this->version, $text );		//  insert client version
		$text		= str_replace( "%language%", $this->language, $text );	//  insert client language
		$words		= parse_ini_string( $text );									//  parse ini structure in plain mode
		return (object) $words;														//  return words map as object
	}

	/**
	 *	Sets language of locale.
	 *	@access		public
	 *	@param		string		$language	Language to set for locale
	 *	@return		self					for method chaining
	 */
	public function setLanguage( string $language ): self
	{
		$this->language		= $language;
		return $this;
	}
}
