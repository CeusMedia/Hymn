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
 *	@package		CeusMedia.Hymn.Tool.Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Cache
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_Cache_AppModules{

	protected $client;

	public function __construct( Hymn_Client $client ){
		$this->client	= $client;
	}

	public function invalidate(){
		return static::staticInvalidate( $this->client );
	}

	/**
	 *	Returns result of cache invalidation.
	 *	Returns NULL if modules were not cached.
	 *	@static
	 *	@access		public
	 *	@param		Hymn_Client		$hymnClient		Hymn client instance
	 *	@return		bool|NULL		Result of cache invalidation, NULL if not cached
	 */
	static public function staticInvalidate( Hymn_Client $hymnClient ){
		$filePath	= $hymnClient->getConfigPath().'modules.cache.serial';
		if( !file_exists( $filePath ) )
			return NULL;
		return @\unlink( $filePath );
	}
}
