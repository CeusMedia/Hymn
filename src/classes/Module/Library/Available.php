<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2023 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Library_Available
{
	const MODE_AUTO			= 0;
	const MODE_FOLDER		= 1;
	const MODE_JSON			= 2;
	const MODE_SERIAL		= 3;

	const MODES				= [
		self::MODE_AUTO,
		self::MODE_FOLDER,
		self::MODE_JSON,
		self::MODE_SERIAL,
	];

	protected Hymn_Client $client;
	protected int $mode			= self::MODE_AUTO;
	protected array $modules		= [];
	protected array $shelves		= [];

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function addShelf( string $shelfId, string $path, string $type, bool $active = TRUE, string $title = NULL ): void
  {
		if( in_array( $shelfId, array_keys( $this->shelves ) ) )
			throw new Exception( 'Source already set by ID: '.$shelfId );
		$activeShelves	= $this->getShelves( ['default' => TRUE] );
		$isDefault		= $active && !count( $activeShelves );
		$this->shelves[$shelfId]	= (object) [
			'id'		=> $shelfId,
			'path'		=> $path,
			'type'		=> $type,
			'active'	=> $active,
			'default'	=> $isDefault,
			'title'		=> $title,
			'date'		=> NULL,
		];
//		ksort( $this->shelves );
	}

	public function get( string $moduleId, string $shelfId = NULL, bool $strict = TRUE ): ?object
	{
		$this->loadModulesInShelves();
		if( $shelfId )
			return $this->getFromShelf( $moduleId, $shelfId, $strict );
		$candidates	= [];
		foreach( $this->modules as $shelfId => $shelfModules )
			foreach( $shelfModules as $shelfModuleId => $shelfModule )
				if( $shelfModuleId === $moduleId )
					$candidates[]	= $shelfModule;
		if( count( $candidates ) === 1 )
			return $candidates[0];
		if( count( $candidates ) > 1 )
			foreach( $candidates as $candidate )
				if( !$candidate->isDeprecated )
					return $candidate;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId );
		return NULL;
	}

	public function getActiveShelves( bool $withModules = FALSE ): array
	{
		return $this->getShelves( ['active' => TRUE], $withModules );
	}

	public function getAll( string $shelfId = NULL ): array
	{
		$this->loadModulesInShelves();
		$list	= [];
		if( $shelfId ){
			if( !isset( $this->modules[$shelfId] ) )
				throw new DomainException( 'Invalid source ID: '.$shelfId );

			foreach( $this->modules[$shelfId] as $module ){
				$module->sourceId	= $shelfId;
				$list[$module->id]	= $module;
			}
			ksort( $list );
			return $list;
		}
		foreach( $this->modules as $shelfId => $modules ){
			foreach( $modules as $module ){
				$module->sourceId	= $shelfId;
				$key	= $module->id.'_AAA_'.$shelfId;
				$list[$key]	= $module;
			}
		}
		ksort( $list );
		$modules	= [];
		foreach( $list as $module )
			$modules[$module->id] = $module;
		return $modules;
	}

	public function getDefaultShelf(): string
	{
		foreach( $this->shelves as $shelfId => $shelf )
			if( $shelf->active && $shelf->default )
				return $shelfId;
		throw new RuntimeException( 'No default source available' );
	}

	public function getFromShelf( string $moduleId, string $shelfId, bool $strict = TRUE )
	{
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

	public function getModuleLogChanges( string $moduleId, string $shelfId, string $versionInstalled, string $versionAvailable ): array
	{
		$module	= $this->get( $moduleId, $shelfId );
		$list	= [];
		foreach( $module->versionLog as $change ){
			if( version_compare( $change->version, $versionInstalled, '<=' ) )					//  log version is lower than installed
				continue;
			if( version_compare( $change->version, $versionAvailable, '>' ) )					//  log version is higher than available
				continue;
			$list[]	= $change;
		}
		return $list;
	}

	public function getModuleShelves( string $moduleId ): array
	{
		$this->loadModulesInShelves();
		$list	= [];
		foreach( $this->modules as $shelfId => $modules ){
			if( array_key_exists($moduleId, $modules ) )
				$list[$shelfId]	= $modules[$moduleId];
		}
		return $list;
	}

	public function getShelf( string $shelfId, bool $withModules = FALSE )
	{
		if( !array_key_exists( $shelfId, $this->shelves ) )
			throw new DomainException( 'Invalid source ID: '.$shelfId );
		$shelf	= $this->shelves[$shelfId];
		if( !$withModules )
			unset( $shelf->modules );
		return $shelf;
	}

	public function getShelves( array $filters = [], bool $withModules = FALSE ): array
	{
		$list	= [];																			//  prepare empty shelf list
		foreach( $this->shelves as $shelfId => $shelf ){											//  iterate known shelves
			foreach( $filters as $filterKey => $filterValue )										//  iterate given filters
				if( property_exists( $shelf, $filterKey ) )											//  filter key is shelf property
					if( $shelf->{$filterKey} !== $filterValue )										//  shelf property value mismatches filter value
						continue 2;																	//  skip this shelf
			$list[$shelfId]	= $this->getShelf( $shelfId, $withModules );							//  enlist shelf
		}
		return $list;																				//  return list of found shelves
	}

	public function readModule( string $path, string $moduleId ): stdClass
  {
		$pathname	= str_replace( "_", "/", $moduleId ).'/';										//  assume source module path from module ID
		$filename	= $path.$pathname.'module.xml';													//  assume module config file name in assumed source module path
		if( !file_exists( $filename ) )																//  assume module config file is not existing
			throw new RuntimeException( 'Module "'.$moduleId.'" not found in '.$pathname );			//  throw exception
		$reader		= new Hymn_Module_Reader();
		$module		= $reader->load( $filename, $moduleId );										//  otherwise load module configuration from source XML file
		$this->decorateModuleWithPaths( $module, $path );
		return $module;																				//  return module
	}

	public function setMode( int $mode ): self
	{
		if( !in_array( $mode, self::MODES, TRUE ) )
			throw new InvalidArgumentException( 'Invalid mode' );
		$this->mode	= $mode;
		return $this;
	}

	//  --  PROTECTED  --  //

	protected function decorateModuleWithPaths( $module, $shelfPath ): void
  {
		$pathname	= str_replace( "_", "/", $module->id ).'/';										//  assume source module path from module ID
		$module->absolutePath	= realpath( $shelfPath.$pathname )."/";								//  extend found module by real source path
		$module->pathname		= $pathname;														//  extend found module by relative path
		$module->path			= $shelfPath.$pathname;												//  extend found module by pseudo real path
		if( empty( $module->frameworks ) || !isset( $module->frameworks['Hydrogen'] ) )
			$module->frameworks['Hydrogen']	= '<0.9';
	}

	protected function listModulesInShelf( $shelf ): array
	{
		$path	= $shelf->path;
		$this->client->outVeryVerbose( '- Path: '.$path );
		$fileJson	= $path.'/index.json';
		$fileSerial	= $path.'/index.serial';
		$mode		= $this->mode;
		if( $mode === self::MODE_AUTO ){
			$mode	= self::MODE_FOLDER;
			$mode	= file_exists( $fileJson ) ? self::MODE_JSON : $mode;
			$mode	= file_exists( $fileSerial ) ? self::MODE_SERIAL : $mode;
		}
		$list	= [];
		switch( $mode ){
			case self::MODE_SERIAL;
				$this->client->outVeryVerbose( '- Strategy: serial file' );
				$index	= unserialize( file_get_contents( $fileSerial ) );
				foreach( $index->modules as $module ){
					$module->frameworks		= (array) $module->frameworks;
					$module->isDeprecated	= isset( $module->deprecation );
					$this->decorateModuleWithPaths( $module, $path );
				}
				$list	= $index->modules;
				break;
			case self::MODE_JSON;
				$this->client->outVeryVerbose( '- Strategy: JSON file' );
				$index	= json_decode( file_get_contents( $fileJson ) );
				foreach( $index->modules as $module ){
					$list[$module->id]	= $module;
					$module->config					= (array) $module->config;
					$module->hooks					= (array) $module->hooks;
					foreach( $module->hooks as $resource => $events )
						$module->hooks[$resource]	= (array) $module->hooks[$resource];
					foreach( $module->files as $category => $files )
						$module->files->{$category}	=  (array) $files;
					$module->relations->needs		= (array) $module->relations->needs;
					$module->relations->supports	= (array) $module->relations->supports;
					$module->isDeprecated			= isset( $module->deprecation );
					if( isset( $module->frameworks ) )
						$module->frameworks			= (array) $module->frameworks;
					$this->decorateModuleWithPaths( $module, $path );
				}
				break;
			case self::MODE_FOLDER:
				$this->client->outVeryVerbose( '- Strategy: folder' );
	//			if( $this->useCache && $this->listModulesAvailable !== NULL )			//  @todo realize shelves in cache
	//				return $this->listModulesAvailable;									//  @todo realize shelves in cache
				$iterator	= new RecursiveDirectoryIterator( $path );
				$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
				foreach( $index as $entry ){
					if( !$entry->isFile() || !preg_match( "/^module\.xml$/", $entry->getFilename() ) )
						continue;
					$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
					$module	= $this->readModule( $path, $key );
					$list[$key]	= $module;
				}
				break;
		}
		$sourceTypesHavingMetaData = [self::MODE_SERIAL, self::MODE_JSON];				//  list of source types supporting source meta data
		if( isset( $index ) && in_array( $mode, $sourceTypesHavingMetaData, TRUE ) ){	//  found source has meta data
			$shelf	= $this->shelves[$shelf->id];
			if( isset( $index->date ) && strlen( trim( $index->date ) ) )				//  source index has date
				$shelf->date	= $index->date;											//  define date of shelf
			if( isset( $index->url ) && strlen( trim( $index->url ) ) )					//  source index a hyperlink
				$shelf->url	= $index->url;												//  define hyperlink of shelf
			if( isset( $index->description ) && strlen( trim( $index->description ) ) )	//  source index has a description
				$shelf->title	= strip_tags( $index->description );
		}
//		$this->listModulesAvailable	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	protected function loadModulesInShelves( bool $force = FALSE ): void
  {
		if( count( $this->modules ) && !$force )													//  modules of all sources already mapped
			return;																					//  skip this rerun
		$this->modules	= [];																	//  reset module list
		foreach( $this->shelves as $shelf ){														//  iterate sources
			$this->client->outVeryVerbose( sprintf( 'Loading source "%s":', $shelf->id ) );
			if( !$shelf->active )																	//  if source if deactivated
				continue;																			//  skip this source
			$this->modules[$shelf->id]	= [];													//  prepare empty module list for source
			foreach( $this->listModulesInShelf( $shelf ) as $module ){								//  iterate modules in source path
				$module->sourceId	= $shelf->id;													//  extend found module by source ID
				$module->sourcePath	= $shelf->path;													//  extend found module by source path
				$module->sourceType	= $shelf->type;													//  extend found module by source type
				$this->modules[$shelf->id][$module->id] = $module;									//  add found module to general module map
				ksort( $this->modules[$shelf->id] );												//  sort source modules in general module map
			}
			$this->client->outVeryVerbose( vsprintf( '- Found %d modules', [
				count( $this->modules[$shelf->id] )
			] ) );
		}
		$this->client->outVeryVerbose( $this->client->getMemoryUsage( 'after loading module sources' ) );
//		ksort( $this->modules );																	//  sort general module map by source IDs
	}
}

class CMF_Hydrogen_Environment_Resource_Module_Component_File
{
	public $file;
	public $load;
	public $level;
	public $source;
}
class CMF_Hydrogen_Environment_Resource_Module_Component_Config
{
	public $key;
	public $value;
	public $type;
	public $values;
	public $mandatory;
	public $protected;
	public $title;
}
