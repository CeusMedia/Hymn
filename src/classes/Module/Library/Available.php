<?php
class Hymn_Module_Library_Available{

	protected $modules		= array();
	protected $shelves		= array();

	public function addShelf( $shelfId, $path, $type, $active = TRUE, $title = NULL ){
		if( in_array( $shelfId, array_keys( $this->shelves ) ) )
			throw new Exception( 'Source already set by ID: '.$shelfId );
		$activeShelves	= $this->getShelves( array( 'default' => TRUE ) );
		$isDefault		= $active && !count( $activeShelves );
		$this->shelves[$shelfId]	= (object) array(
			'id'		=> $shelfId,
			'path'		=> $path,
			'type'		=> $type,
			'active'	=> $active,
			'default'	=> $isDefault,
			'title'		=> $title,
		);
//		ksort( $this->shelves );
	}

	public function get( $moduleId, $shelfId = NULL, $strict = TRUE ){
		$this->loadModulesInShelves();
		if( $shelfId )
			return $this->getFromShelf( $moduleId, $shelfId, $strict );
		$candidates	= array();
		foreach( $this->modules as $shelfId => $shelfModules )
			foreach( $shelfModules as $shelfModuleId => $shelfModule )
				if( $shelfModuleId === $moduleId )
					$candidates[]	= $shelfModule;
		if( count( $candidates ) === 1 )
			return $candidate[0];
		if( count( $candidates ) > 1 )
			foreach( $candidates as $candidate )
				if( !$candidate->isDeprecated )
					return $candidate;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId );
		return NULL;
	}

	public function getActiveShelves( $withModules = FALSE ){
		return $this->getShelves( array( 'active' => TRUE ), $withModules );
	}

	public function getAll( $shelfId = NULL ){
		$this->loadModulesInShelves();
		if( $shelfId ){
			if( !isset( $this->modules[$shelfId] ) )
				throw new DomainException( 'Invalid source ID: '.$shelfId );
			$modules	= array();
			foreach( $this->modules[$shelfId] as $module ){
				$module->sourceId	= $shelfId;
				$list[$module->id]	= $module;
			}
			ksort( $list );
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
		$modules	= array();
		foreach( array_values( $list ) as $module )
			$modules[$module->id] = $module;
		return $modules;
	}

	public function getDefaultShelf(){
		foreach( $this->shelves as $shelfId => $shelf )
			if( $shelf->active && $shelf->default )
				return $shelfId;
		throw new RuntimeException( 'No default source available' );
	}

	public function getFromShelf( $moduleId, $shelfId, $strict = TRUE ){
		$this->loadModulesInShelves();
		if( !in_array( $shelfId, array_keys( $this->getActiveShelves() ) ) ){
			if( $strict )
				throw new DomainException( 'Source "'.$shelfId.'" is not active' );
			return NULL;
		}
		foreach( $this->modules[$shelfId] as $module )
			if( $module->id === $moduleId )
				return $module;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId );
		return NULL;
	}

	public function getModuleLogChanges( $moduleId, $shelfId, $versionInstalled, $versionAvailable ){
		$module	= $this->get( $moduleId, $shelfId );
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

	public function getModuleShelves( $moduleId ){
		$this->loadModulesInShelves();
		$list	= array();
		foreach( $this->modules as $shelfId => $modules ){
			if( array_key_exists($moduleId, $modules ) )
				$list[$shelfId]	= $modules[$moduleId];
		}
		return $list;
	}

	public function getShelf( $moduleId, $withModules = FALSE ){
		if( !in_array( $moduleId, array_keys( $this->shelves ) ) )
			throw new DomainException( 'Invalid source ID: '.$moduleId );
		$shelf	= $this->shelves[$moduleId];
		if( !$withModules )
			unset( $shelf->modules );
		return $shelf;
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

	public function readModule( $path, $moduleId ){
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

	//  --  PROTECTED  --  //

	protected function listModulesInPath( $path = "" ){
//		if( $this->useCache && $this->listModulesAvailable !== NULL )			//  @todo realize shelves in cache
//			return $this->listModulesAvailable;									//  @todo realize shelves in cache
		$list		= array();
		$iterator	= new RecursiveDirectoryIterator( $path );
		$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
		foreach( $index as $entry ){
			if( !$entry->isFile() || !preg_match( "/^module\.xml$/", $entry->getFilename() ) )
				continue;
			$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
			$module	= $this->readModule( $path, $key );
			$list[$key]	= $module;
		}
//		$this->listModulesAvailable	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	protected function loadModulesInShelves( $force = FALSE ){
		if( count( $this->modules ) && !$force )													//  modules of all sources already mapped
			return;																					//  skip this rerun
		$this->modules	= array();																	//  reset module list
		foreach( $this->shelves as $shelf ){														//  iterate sources
			if( !$shelf->active )																	//  if source if deactivated
				continue;																			//  skip this source
			$this->modules[$shelf->id]	= array();													//  prepare empty module list for source
			foreach( $this->listModulesInPath( $shelf->path ) as $module ){							//  iterate modules in source path
				$module->sourceId	= $shelf->id;													//  extend found module by source ID
				$module->sourcePath	= $shelf->path;													//  extend found module by source path
				$module->sourceType	= $shelf->type;													//  extend found module by source type
				$this->modules[$shelf->id][$module->id] = $module;									//  add found module to general module map
				ksort( $this->modules[$shelf->id] );												//  sort source modules in general module map
			}
		}
//		ksort( $this->modules );																	//  sort general module map by source IDs
	}
}
