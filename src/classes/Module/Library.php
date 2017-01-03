<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
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

	public function __construct(){
	}

	public function addShelf( $id, $path, $active = TRUE ){
		if( in_array( $id, array_keys( $this->shelves ) ) )
			throw new Exception( 'Shelf already set by ID: '.$id );
		$this->shelves[$id]	= (object) array(
			'id'		=> $id,
			'path'		=> $path,
			'active'	=> $active,
		);
		ksort( $this->shelves );
		$this->modules[$id]	= array();
		if( $active ){
			foreach( self::listModules( $path ) as $module ){
				$module->sourceId	= $id;
				$module->sourcePath	= $path;
				$this->modules[$id][$module->id] = $module;
				ksort( $this->modules[$id] );
			}
		}
		ksort( $this->modules );
	}

	public function getModule( $id, $shelfId = NULL, $strict = TRUE ){
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

	public function getShelves( $withModules = FALSE ){
		$list	= array();
		foreach( $this->shelves as $shelfId => $shelf ){
			$list[$shelfId]	= $this->getShelf( $shelfId, $withModules );
		}
		return $list;
	}

	static public function isInstalledModule( $pathApp = "", $moduleId ){
		$list	= self::listInstalledModules( $pathApp );
		return array_key_exists( $moduleId, $list );
	}

	static public function listModules( $path = "" ){
		if( self::$useCache && self::$listModulesAvailable !== NULL )
			return self::$listModulesAvailable;

		$list	= array();
		$iterator	= new RecursiveDirectoryIterator( $path );
		$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
		foreach( $index as $entry ){
			if( !$entry->isFile() || !preg_match( "/^module\.xml$/", $entry->getFilename() ) )
				continue;
			$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
			$module	= self::readModule( $path, $key );
			$list[$key]	= $module;
		}
		self::$listModulesAvailable	= $list;
		return $list;
	}

	static public function listInstalledModules( $pathApp = "" ){
		if( self::$useCache && self::$listModulesInstalled !== NULL )
			return self::$listModulesInstalled;
		$list	= array();
		if( file_exists( $pathApp.'/config/modules/' ) ){
			$iterator	= new RecursiveDirectoryIterator( $pathApp.'/config/modules/' );
			$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
			foreach( $index as $entry ){
				if( !$entry->isFile() || !preg_match( "/\.xml$/", $entry->getFilename() ) )
					continue;
				$key	= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
				$module	= self::readInstalledModule( $pathApp, $key );
				$list[$key]	= $module;
			}
		}
		ksort( $list );
		self::$listModulesInstalled	= $list;
		return $list;
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

	static public function readInstalledModule( $path, $id ){
		$filename	= $path.'config/modules/'.$id.'.xml';
		if( !file_exists( $filename ) )
			throw new Exception( 'Module "'.$id.'" not installed in '.$path );
		return Hymn_Module_Reader::load( $filename, $id );
	}
}
