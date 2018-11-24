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
 *	@todo			code documentation
 */
class Hymn_Module_Library{

	static protected $listModulesAvailable	= NULL;
	static protected $listModulesInstalled	= NULL;
	static protected $useCache				= FALSE;

	protected $modules		= array();
	protected $shelves		= array();

	public function __construct( Hymn_Client $client ){
		$this->client		= $client;
	}

	public function addShelf( $moduleId, $path, $type, $active = TRUE, $title = NULL ){
		if( in_array( $moduleId, array_keys( $this->shelves ) ) )
			throw new Exception( 'Shelf already set by ID: '.$moduleId );
		$activeShelves	= $this->getShelves( array( 'default' => TRUE ) );
		$isDefault		= $active && !count( $activeShelves );
		$this->shelves[$moduleId]	= (object) array(
			'id'		=> $moduleId,
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

	public function getModule( $moduleId, $shelfId = NULL, $strict = TRUE ){
		$this->loadModulesInShelves();
		if( $shelfId )
			return $this->getModuleFromShelf( $moduleId, $shelfId, $strict );
		foreach( $this->modules as $modules )
			foreach( $modules as $module )
				if( $module->id === $moduleId )
					return $module;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId );
		return NULL;
	}

	public function getModuleChanges( $moduleId, $shelfId, $versionInstalled, $versionAvailable ){
		$module	= $this->getModule( $moduleId, $shelfId );
		$list	= array();
		foreach( $module->versionLog as $change ){
			if( version_compare( $change->version, $versionInstalled, '<=' ) )					//  log version is to lower than installed
				continue;
			if( version_compare( $change->version, $versionAvailable, '>' ) )					//  log version is to higher than available
				continue;
			$list[]	= $change;
		}
		return $list;
	}

	public function getModuleFromShelf( $moduleId, $shelfId, $strict = TRUE ){
		$this->loadModulesInShelves();
		if( !in_array( $shelfId, array_keys( $this->getActiveShelves() ) ) ){
			if( $strict )
				throw new DomainException( 'Shelf "'.$shelfId.'" is not active' );
			return NULL;
		}
		foreach( $this->modules[$shelfId] as $module )
			if( $module->id === $moduleId )
				return $module;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId );
		return NULL;
	}

	public function getModules( $shelfId = NULL ){
		$this->loadModulesInShelves();
		if( $shelfId ){
			if( !isset( $this->modules[$shelfId] ) )
				throw new DomainException( '__Invalid shelf ID: '.$shelfId );
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

	public function getShelf( $moduleId, $withModules = FALSE ){
		if( !in_array( $moduleId, array_keys( $this->shelves ) ) )
			throw new DomainException( '_Invalid shelf ID: '.$moduleId );
		$shelf	= $this->shelves[$moduleId];
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
		if( count( $this->modules ) && !$force )													//  modules of all sources already mapped
			return;																					//  skip this rerun
		foreach( $this->shelves as $shelf ){														//  iterate sources
			if( !$shelf->active )																	//  if source if deactivated
				continue;																			//  skip this source
			$this->modules[$shelf->id]	= array();													//  prepare empty module list for source
			foreach( self::listModulesInPath( $shelf->path ) as $module ){							//  iterate modules in source path
				$module->sourceId	= $shelf->id;													//  extend found module by source ID
				$module->sourcePath	= $shelf->path;													//  extend found module by source path
				$module->sourceType	= $shelf->type;													//  extend found module by source type
				$this->modules[$shelf->id][$module->id] = $module;									//  add found module to general module map
				ksort( $this->modules[$shelf->id] );												//  sort source modules in general module map
			}
		}
		ksort( $this->modules );																	//  sort general module map by source IDs
	}

	static public function readModule( $path, $moduleId ){
		$pathname	= str_replace( "_", "/", $moduleId ).'/';										//  assume source module path from module ID
		$filename	= $path.$pathname.'module.xml';													//  assume module config file name in assumed source module path
		if( !file_exists( $filename ) )																//  assume module config file is not existing
			throw new RuntimeException( 'Module "'.$moduleId.'" not found in '.$pathname );			//  throw exception
		$reader		= new Hymn_Module_Reader();
		$module		= $reader->load( $filename, $moduleId );										//  otherwise load module configuration from source XML file
		$module->absolutePath	= realpath( $pathname )."/";										//  extend found module by real source path
		$module->pathname		= $pathname;														//  extend found module by relative path
		$module->path			= $path.$pathname;													//  extebd found module by pseudo real path
		return $module;																				//  return module
	}

	public function readInstalledModule( $moduleId ){
		$pathModules	= $this->client->getConfigPath().'modules/';
		$filename		= $pathModules.$moduleId.'.xml';
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'Module "'.$moduleId.'" not installed in '.$pathModules );
		$reader			= new Hymn_Module_Reader();
		return $reader->load( $filename, $moduleId );
	}
}
