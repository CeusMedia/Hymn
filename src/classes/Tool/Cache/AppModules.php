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
 *	@package		CeusMedia.Hymn.Tool.Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_Cache_AppModules
{
	protected $client;
	protected $flags;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
		$this->flags	= (object) array(
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
		);
	}

	/**
	 *	Remove app module cache file.
	 *	@access		public
	 *	@param		string		$outputPrefix		Perfix of verbose message
	 *	@return		bool|NULL
	 */
	public function invalidate( string $outputPrefix = '' ): ?bool
	{
		$filePath	= $this->client->getConfigPath().'modules.cache.serial';
		$exists		= file_exists( $filePath );
		if( !$exists )
			return NULL;
		if( !$this->flags->quiet )
			$this->client->outVerbose( $outputPrefix.'Clearing cache of installed modules ...' );
		return @\unlink( $filePath );
	}

	/**
	 *	Returns result of cache invalidation.
	 *	Returns NULL if modules were not cached.
	 *	@static
	 *	@access		public
	 *	@param		Hymn_Client		$hymnClient		Hymn client instance
	 *	@return		bool|NULL		Result of cache invalidation, NULL if not cached
	 */
	public static function staticInvalidate( Hymn_Client $hymnClient, string $outputPrefix = '' ): ?bool
	{
		$tool	= new Hymn_Tool_Cache_AppModules( $hymnClient );
		return $tool->invalidate( $outputPrefix );
	}
}
