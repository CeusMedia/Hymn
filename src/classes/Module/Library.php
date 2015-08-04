<?php
class Hymn_Module_Library{

	protected $modules		= array();
	protected $shelves		= array();

	public function __construct(){
	}

	public function addShelf( $id, $path ){
		if( in_array( $id, array_keys( $this->shelves ) ) )
			throw new Exception( 'Shelf already set by ID: '.$id );
		$this->shelves[$id]	= (object) array(
			'id'			=> $id,
			'path'		=> $path,
		);
		ksort( $this->shelves );
		$this->modules[$id]	= array();
		foreach( self::listModules( $path ) as $module ){
			$module->sourceId	= $id;
			$module->sourcePath	= $path;
			$this->modules[$id][$module->id] = $module;
			ksort( $this->modules[$id] );
		}
		ksort( $this->modules );
	}

	public function getModule( $id, $shelfId = NULL ){
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
		throw new Exception( 'Invalid module ID: '.$id );
	}

	public function getModules( $shelfId = NULL ){
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
		if( $withModules ){
			$shelf->modules	= $this->modules[$id];
		}
		return $shelf;
	}

	public function getShelves( $withModules = FALSE ){
		$list	= array();
		foreach( $this->shelves as $shelfId => $shelf ){
			$list[$shelfId]	= $this->getShelf( $shelfId, $withModules );
		}
		return $list;
	}

	static public function listModules( $path = "" ){
		$list	= array();
		$index	= new Folder_RecursiveRegexFilter( $path, "/module.xml$/", TRUE, FALSE );
		foreach( $index as $entry ){
			if( $entry->isFile() ){
				$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
//				$module	= CMF_Hydrogen_Environment_Resource_Module_Reader::load( $entry->getPathname(), $key );
				$module	= self::readModule( $path, $key );
//print_m( $module->id ); die;
				$list[$key]	= $module;
			}
		}
		return $list;
	}

	static public function listInstalledModules( $path = "" ){
		$list	= array();
		if( file_exists( $path.'/config/modules/' ) ){
			$index	= new Folder_RegexFilter( $path.'/config/modules/', "/.xml$/", TRUE, FALSE );
			foreach( $index as $entry ){
				$key	= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
				$module	= self::readInstalledModule( $path, $key );
				$list[$key]	= $module;
			}
		}
		return $list;
	}

	static public function readModule( $path, $id ){
		$pathname	= str_replace( "_", "/", $id ).'/';
		$filename	= $path.$pathname.'module.xml';
		if( !file_exists( $filename ) )
			throw new Exception( 'Module "'.$id.'" not found in '.$pathname );
		$module		= CMF_Hydrogen_Environment_Resource_Module_Reader::load( $filename, $id );
		$module->absolutePath	= realpath( $pathname )."/";
		$module->pathname		= $pathname;
		$module->path			= $path.$pathname;
		return $module;
	}

	static public function readInstalledModule( $path, $id ){
		$filename	= $path.'config/modules/'.$id.'.xml';
		if( !file_exists( $filename ) )
			throw new Exception( 'Module "'.$id.'" not installed in '.$path );
		return CMF_Hydrogen_Environment_Resource_Module_Reader::load( $filename, $id );
	}
}
