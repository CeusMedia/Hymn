<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Library{

	protected $listModulesAvailable	= NULL;
	protected $listModulesInstalled	= NULL;
	protected $useCache				= FALSE;

	protected $modules		= array();
	protected $client;
	protected $installed;
	protected $available;

	public function __construct( Hymn_Client $client ){
		$this->client		= $client;
		$this->installed	= new Hymn_Module_Library_Installed( $client );
		$this->available	= new Hymn_Module_Library_Available( $client );
	}

	public function addShelf( $moduleId, $path, $type, $active = TRUE, $title = NULL ){
		return $this->available->addShelf( $moduleId, $path, $type, $active, $title );
	}

	public function getActiveShelves( $withModules = FALSE ){
		return $this->available->getActiveShelves( $withModules );
	}

	public function getAvailableModule( $moduleId, $shelfId = NULL, $strict = TRUE ){
		return $this->available->get( $moduleId, $shelfId = NULL, $strict = TRUE );
	}

	public function getAvailableModuleFromShelf( $moduleId, $shelfId, $strict = TRUE ){
		return $this->available->getFromShelf( $moduleId, $shelfId, $strict );
	}

	public function getAvailableModuleLogChanges( $moduleId, $shelfId, $versionInstalled, $versionAvailable ){
		return $this->available->getModuleLogChanges( $moduleId, $shelfId, $versionInstalled, $versionAvailable );
	}

	public function getAvailableModules( $shelfId = NULL ){
		return $this->available->getAll( $shelfId );
	}

	public function getAvailableModuleShelves( $moduleId ){
		return $this->available->getModuleShelves( $moduleId );
	}

	public function getDefaultShelf(){
		return $this->available->getDefaultShelf();
	}

	public function getShelf( $moduleId, $withModules = FALSE ){
		return $this->available->getShelf( $moduleId, $withModules );
	}

	public function getShelves( $filters = array(), $withModules = FALSE ){
		return $this->available->getShelves( $filters, $withModules );
	}

	public function isAvailableModuleInShelf( $moduleId, $shelfId ){
		return (bool) $this->available->getFromShelf( $moduleId, $shelfId, FALSE );
	}

	public function isInstalledModule( $moduleId ){
		return $this->installed->has( $moduleId );
	}

	public function isActiveShelf( $shelfId ){
		return array_key_exists( $shelfId, $this->getActiveShelves() );
	}

	public function isShelf( $shelfId ){
		return array_key_exists( $shelfId, $this->getShelves() );
	}

	public function listInstalledModules( $shelfId = NULL ){
//		if( $this->useCache && $this->listModulesInstalled !== NULL )			//  @todo realize shelves in cache
//			return $this->listModulesInstalled;									//  @todo realize shelves in cache
		$list	= $this->installed->getAll( $shelfId );
//		$this->listModulesInstalled	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	public function readInstalledModule( $moduleId ){
		return $this->installed->get( $moduleId );
	}

	public function useCache( $useCache = TRUE ){
		$this->useCache		= $useCache;
	}
}
