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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Library{

	static protected $listModulesAvailable	= NULL;
	static protected $listModulesInstalled	= NULL;
	static protected $useCache		= FALSE;

	protected $modules		= array();
	protected $shelves		= array();

	public function __construct( Hymn_Client $client ){
		$this->client		= $client;
	}

	public function addShelf( $id, $path, $type, $active = TRUE, $title = NULL ){
		if( in_array( $id, array_keys( $this->shelves ) ) )
			throw new Exception( 'Shelf already set by ID: '.$id );
		$activeShelves	= $this->getShelves( array( 'default' => TRUE ) );
		$isDefault		= $active && !count( $activeShelves );
		$this->shelves[$id]	= (object) array(
			'id'		=> $id,
			'path'		=> $path,
			'type'		=> $type,
			'active'	=> $active,
			'default'	=> $isDefault,
			'title'		=> $title,
		);
		ksort( $this->shelves );
	}

	public function getDefaultShelf(){
		foreach( $this->shelves as $shelfId => $shelf )
			if( $shelf->default )
				return $shelfId;
		throw new RuntimeException( 'No default shelf available' );
	}

	public function getModule( $id, $shelfId = NULL, $strict = TRUE ){
		$this->loadModulesInShelves();
		if( $shelfId ){
			if( !in_array( $shelfId, array_keys( $this->shelves ) ) )
				throw new Exception( 'Invalid shelf ID: '.$shelfId );
			foreach( $this->modules[$shelfId] as $module )
				if( $module->id === $id )
					return $module;
		}
		else{
			foreach( $this->modules as $shelf => $modules )
				foreach( $modules as $module )
					if( $module->id === $id )
						return $module;
		}
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$id );
		return NULL;
	}

	public function getModules( $shelfId = NULL ){
		$this->loadModulesInShelves();
		if( $shelfId ){
			if( !isset( $this->modules[$shelfId] ) )
				throw new Exception( 'Invalid shelf ID: '.$shelfId );
			$modules	= array();
			foreach( $this->modules[$shelfId] as $module ){
				$module->sourceId	= $shelfId;
				$list[]	= $module;
			}
			return $list;
		}
		$list	= array();
		foreach( $this->modules as $shelfId => $modules ){
			foreach( $modules as $module ){
				$module->sourceId	= $shelfId;
				$key	= $module->id.'_AAA_'.$shelfId;
				$list[$key]	= $module;
			}
		}
		ksort( $list );
		return array_values( $list );
	}

	public function getShelf( $id, $withModules = FALSE ){
		if( !in_array( $id, array_keys( $this->shelves ) ) )
			throw new Exception( 'Invalid shelf ID: '.$id );
		$shelf	= $this->shelves[$id];
		if( !$withModules )
			unset( $shelf->modules );
		return $shelf;
	}

	public function getActiveShelves( $withModules = FALSE ){
		return $this->getShelves( array( 'active' => TRUE ), $withModules );
	}

	public function getShelves( $filters = array(), $withModules = FALSE ){
		$list	= array();																			//  prepare empty shelf list
		foreach( $this->shelves as $shelfId => $shelf ){											//  iterate known shelves
			foreach( $filters as $filterKey => $filterValue )										//  iterate given filters
				if( property_exists( $shelf, $filterKey ) )											//  filter key is shelf property
					if( $shelf->{$filterKey} !== $filterValue )										//  shelf property value mismatches filter value
						continue 2;																	//  skip this shelf
			$list[$shelfId]	= $this->getShelf( $shelfId, $withModules );							//  enlist shelf
		}
		return $list;																				//  return list of found shelves
	}

	public function isInstalledModule( $moduleId ){
		$list	= self::listInstalledModules();
		return array_key_exists( $moduleId, $list );
	}

	public function isShelf( $shelfId ){
		return array_key_exists( $shelfId, $this->getShelves() );
	}

	static protected function listModulesInPath( $path = "" ){
//		if( self::$useCache && self::$listModulesAvailable !== NULL )			//  @todo realize shelves in cache
//			return self::$listModulesAvailable;									//  @todo realize shelves in cache
		$list		= array();
		$iterator	= new RecursiveDirectoryIterator( $path );
		$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
		foreach( $index as $entry ){
			if( !$entry->isFile() || !preg_match( "/^module\.xml$/", $entry->getFilename() ) )
				continue;
			$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
			$module	= self::readModule( $path, $key );
			$list[$key]	= $module;
		}
//		self::$listModulesAvailable	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	public function listInstalledModules( $shelfId = NULL ){
//		if( self::$useCache && self::$listModulesInstalled !== NULL )			//  @todo realize shelves in cache
//			return self::$listModulesInstalled;									//  @todo realize shelves in cache
		$list			= array();
		$pathModules	= $this->client->getConfigPath().'modules/';
		if( file_exists( $pathModules ) ){
			$iterator	= new RecursiveDirectoryIterator( realpath( $pathModules ) );
			$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
			foreach( $index as $entry ){
				if( !$entry->isFile() || !preg_match( "/\.xml$/", $entry->getFilename() ) )
					continue;
				$key	= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
				$module	= $this->readInstalledModule( $key );
				if( !$shelfId || $module->installSource === $shelfId )
					$list[$key]	= $module;
			}
		}
		ksort( $list );
//		self::$listModulesInstalled	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	protected function loadModulesInShelves( $force = FALSE ){
		if( count( $this->modules ) && !$force )
			return;
		foreach( $this->shelves as $shelf ){
			if( !$shelf->active )
				continue;
			$this->modules[$shelf->id]	= array();
			foreach( self::listModulesInPath( $shelf->path ) as $module ){
				$module->sourceId	= $shelf->id;
				$module->sourcePath	= $shelf->path;
				$module->sourceType	= $shelf->type;
				$this->modules[$shelf->id][$module->id] = $module;
				ksort( $this->modules[$shelf->id] );
			}
		}
		ksort( $this->modules );
	}

	static public function readModule( $path, $id ){
		$pathname	= str_replace( "_", "/", $id ).'/';
		$filename	= $path.$pathname.'module.xml';
		if( !file_exists( $filename ) )
			throw new Exception( 'Module "'.$id.'" not found in '.$pathname );
		$module		= Hymn_Module_Reader::load( $filename, $id );
		$module->absolutePath	= realpath( $pathname )."/";
		$module->pathname		= $pathname;
		$module->path			= $path.$pathname;
		return $module;
	}

	public function readInstalledModule( $id ){
		$pathModules	= $this->client->getConfigPath().'modules/';
		$filename		= $pathModules.$id.'.xml';
		if( !file_exists( $filename ) )
			throw new Exception( 'Module "'.$id.'" not installed in '.$pathModules );
		return Hymn_Module_Reader::load( $filename, $id );
	}
}
