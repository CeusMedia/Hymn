<?php
/**
 *	...
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
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
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
	protected int $mode					= self::MODE_AUTO;

	/** @var array<string,array<string,Hymn_Structure_Module>> $modules  */
	protected array $modules			= [];

	/** @var	array<string,Hymn_Structure_Source>	$sources  */
	protected array $sources			= [];

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function addModuleToSource( Hymn_Structure_Module $module, Hymn_Structure_Source $source ): static
	{
		$this->modules[$source->id][$module->id]	= $module;
		return $this;
	}

	/**
	 *	Add source.
	 *	@param		string		$sourceId
	 *	@param		string		$path
	 *	@param		string		$type
	 *	@param		bool		$active
	 *	@param		?string		$title
	 *	@return		static
	 *	@throws		Exception
	 */
	public function addSource( string $sourceId, string $path, string $type, bool $active = TRUE, string $title = NULL ): static
	{
		if( in_array( $sourceId, array_keys( $this->sources ) ) )
			throw new Exception( 'Source already set by ID: '.$sourceId );
		$activeSources	= $this->getSources( ['default' => TRUE] );
		$isDefault		= $active && !count( $activeSources );
		$this->sources[$sourceId]	= Hymn_Structure_Source::fromArray( [
			'id'		=> $sourceId,
			'path'		=> $path,
			'type'		=> $type,
			'active'	=> $active,
			'isDefault'	=> $isDefault,
			'title'		=> $title ?? '',
			'date'		=> NULL,
		] );
		if( 1 === count( $this->sources ) )
			$this->sources[$sourceId]->isDefault	= TRUE;
//		ksort( $this->sources );
		return $this;
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		?string		$sourceId
	 *	@param		bool		$strict			Default: yes
	 *	@return		?Hymn_Structure_Module
	 *	@throws		Exception
	 */
	public function get( string $moduleId, string $sourceId = NULL, bool $strict = TRUE ): ?Hymn_Structure_Module
	{
		$this->loadModulesInSources();
		if( $sourceId )
			return $this->getFromSource( $moduleId, $sourceId, $strict );
		$candidates	= [];
		foreach( $this->modules as $sourceModules )
			foreach( $sourceModules as $sourceModuleId => $sourceModule )
				if( $sourceModuleId === $moduleId )
					$candidates[]	= $sourceModule;
		if( 1 === count( $candidates ) )
			return $candidates[0];
		if( count( $candidates ) > 1 )
			foreach( $candidates as $candidate )
				if( NULL === $candidate->deprecation )
					return $candidate;
		if( $strict )
			throw new Exception( __METHOD__.' > Invalid module ID: '.$moduleId.' (source: '.$sourceId.')' );
		return NULL;
	}

	/**
	 *	@param		bool		$withModules		Default: no
	 *	@return		Hymn_Structure_Source[]
	 */
	public function getActiveSources( bool $withModules = FALSE ): array
	{
		return $this->getSources( ['active' => TRUE], $withModules );
	}

	/**
	 *	@param		string|NULL		$sourceId
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	public function getAll( string $sourceId = NULL ): array
	{
		$this->loadModulesInSources();
		$list	= [];
		if( $sourceId ){
			if( !isset( $this->modules[$sourceId] ) )
				throw new DomainException( 'Invalid source ID: '.$sourceId );

			foreach( $this->modules[$sourceId] as $module ){
				$module->sourceId	= $sourceId;
				$list[$module->id]	= $module;
			}
			ksort( $list );
			return $list;
		}
		foreach( $this->modules as $sourceId => $modules ){
			foreach( $modules as $module ){
				$module->sourceId	= $sourceId;
				$key	= $module->id.'_AAA_'.$sourceId;
				$list[$key]	= $module;
			}
		}
		ksort( $list );
		$modules	= [];
		foreach( $list as $module )
			$modules[$module->id] = $module;
		return $modules;
	}

	/**
	 *	@return		string
	 */
	public function getDefaultSource(): string
	{
		foreach($this->sources as $sourceId => $source )
			if( $source->active && $source->isDefault )
				return $sourceId;
		throw new RuntimeException( 'No default source available' );
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@param		bool		$strict			Default: yes
	 *	@return		?Hymn_Structure_Module
	 *	@throws		Exception
	 */
	public function getFromSource( string $moduleId, string $sourceId, bool $strict = TRUE ): ?Hymn_Structure_Module
	{
		if( '' === trim( $moduleId ) ){
			if( $strict )
				throw new InvalidArgumentException( __METHOD__.' > Module ID cannot be empty' );
			return NULL;
		}
		$this->loadModulesInSources();
		if( !in_array( $sourceId, array_keys( $this->getActiveSources() ) ) ){
			if( $strict )
				throw new DomainException( 'Source "'.$sourceId.'" is not active' );
			return NULL;
		}
		foreach( $this->modules[$sourceId] as $module )
			if( $module->id === $moduleId )
				return $module;
		if( $strict )
			throw new Exception( 'Invalid module ID: '.$moduleId.' (source: '.$sourceId.')' );
		return NULL;
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@param		string		$versionInstalled
	 *	@param		string		$versionAvailable
	 *	@return		array
	 *	@throws		Exception
	 */
	public function getModuleLogChanges( string $moduleId, string $sourceId, string $versionInstalled, string $versionAvailable ): array
	{
		$module	= $this->get( $moduleId, $sourceId );
		$list	= [];
		foreach( $module->version->log as $change ){
			if( version_compare( $change->version, $versionInstalled, '<=' ) )					//  log version is lower than installed
				continue;
			if( version_compare( $change->version, $versionAvailable, '>' ) )					//  log version is higher than available
				continue;
			$list[]	= $change;
		}
		return $list;
	}

	public function getModuleSources( string $moduleId ): array
	{
		$this->loadModulesInSources();
		$list	= [];
		foreach( $this->modules as $sourceId => $modules ){
			if( array_key_exists( $moduleId, $modules ) )
				$list[$sourceId]	= $modules[$moduleId];
		}
		return $list;
	}

	/**
	 *	@param		string		$sourceId
	 *	@param		bool		$withModules
	 *	@return		Hymn_Structure_Source
	 */
	public function getSource( string $sourceId, bool $withModules = FALSE ): Hymn_Structure_Source
	{
		if( !array_key_exists( $sourceId, $this->sources ) )
			throw new DomainException( 'Invalid source ID: '.$sourceId );
		$source	= $this->sources[$sourceId];
		if( !$withModules )
			unset( $source->modules );
		return $source;
	}

	/**
	 *	@param		array		$filters
	 *	@param		bool		$withModules
	 *	@return		array<Hymn_Structure_Source>
	 */
	public function getSources( array $filters = [], bool $withModules = FALSE ): array
	{
		$list	= [];																			//  prepare empty source list
		foreach($this->sources as $sourceId => $source ){											//  iterate known sources
			foreach( $filters as $filterKey => $filterValue )										//  iterate given filters
				if( property_exists( $source, $filterKey ) )											//  filter key is source property
					if( $source->{$filterKey} !== $filterValue )										//  source property value mismatches filter value
						continue 2;																	//  skip this source
			$list[$sourceId]	= $this->getSource( $sourceId, $withModules );							//  enlist source
		}
		return $list;																				//  return list of found sources
	}

	/**
	 *	@param		string		$path
	 *	@param		string		$moduleId
	 *	@return		Hymn_Structure_Module
	 *	@throws		RuntimeException
	 */
	public function readModule( string $path, string $moduleId ): Hymn_Structure_Module
	{
		$pathname	= str_replace( "_", "/", $moduleId ).'/';										//  assume source module path from module ID
		$filename	= $path.$pathname.'module.xml';													//  assume module config file name in assumed source module path
		if( !file_exists( $filename ) )																//  assume module config file is not existing
			throw new RuntimeException( 'Module "'.$moduleId.'" not found in '.$pathname );			//  throw exception
		$module		= Hymn_Module_Reader2::load( $filename, $moduleId );								//  otherwise load module configuration from source XML file
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

	protected function decorateModuleWithPaths( Hymn_Structure_Module $module, string $sourcePath ): void
  {
		$pathname	= str_replace( "_", "/", $module->id ).'/';						//  assume source module path from module ID
		$module->absolutePath	= realpath( $sourcePath.$pathname )."/";						//  extend found module by real source path
//		$module->pathname		= $pathname;														//  extend found module by relative path
		$module->install->path	= $sourcePath.$pathname;											//  extend found module by pseudo real path
		if( empty( $module->frameworks ) || !isset( $module->frameworks['Hydrogen'] ) )
			$module->frameworks['Hydrogen']	= '<1.0';
	}

	/**
	 *	@param		Hymn_Structure_Source		$source
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	protected function listModulesInSource( Hymn_Structure_Source $source ): array
	{
		$path	= $source->path;
		$this->client->outVeryVerbose( '- Path: '.$path );

//		!!! Cache has been disabled, since new module config structure
//			- breaks with JSON serialization (classes objects vs stdclass)
//			- breaks: Hymn_Structure_Module vs CeusMedia\HydrogenFramework\Environment\Resource\Module\Definition
		return $this->loadModulesFromSourceFolder( $path, [] );
/*
		$fileJson	= $path.'/index.json';
		$fileSerial	= $path.'/index.serial';

		$mode		= $this->mode;

//		!!! Auto mode and cache have been disabled, since new module config structure
//		if( $mode === self::MODE_AUTO ){
//			$mode	= self::MODE_FOLDER;
//			$mode	= file_exists( $fileJson ) ? self::MODE_JSON : $mode;
//			$mode	= file_exists( $fileSerial ) ? self::MODE_SERIAL : $mode;
//		}

		$list	= [];
		switch( $mode ){
			case self::MODE_SERIAL;
				list($index, $list) = $this->loadModulesFromSerialFile( $fileSerial, $path );
				break;
			case self::MODE_JSON;
				list($index, $list) = $this->loadModulesFromJsonFile( $fileJson, $list, $path );
				break;
			case self::MODE_FOLDER:
				list($index, $list) = $this->loadModulesFrom($path, $list);
				break;
		}
		$sourceTypesHavingMetaData = [self::MODE_SERIAL, self::MODE_JSON];				//  list of source types supporting source metadata
		if( isset( $index ) && in_array( $mode, $sourceTypesHavingMetaData, TRUE ) ){	//  found source has meta data
			$source	= $this->sources[$source->id];
			if( isset( $index->date ) && strlen( trim( $index->date ) ) )				//  source index has date
				$source->date	= $index->date;											//  define date of source
			if( isset( $index->url ) && strlen( trim( $index->url ) ) )					//  source index a hyperlink
				$source->url	= $index->url;												//  define hyperlink of source
			if( isset( $index->description ) && strlen( trim( $index->description ) ) )	//  source index has a description
				$source->title	= strip_tags( $index->description );
		}
//		$this->listModulesAvailable	= $list;									//  @todo realize sources in cache
		return $list;*/
	}

	protected function loadModulesInSources( bool $force = FALSE ): void
	{
		if( count( $this->modules ) && !$force )													//  modules of all sources already mapped
			return;																					//  skip this rerun
		$this->modules	= [];																	//  reset module list
		foreach($this->sources as $source ){														//  iterate sources
			$this->client->outVeryVerbose( sprintf( 'Loading source "%s":', $source->id ) );
			if( !$source->active )																	//  if source is deactivated
				continue;																			//  skip this source
			$this->modules[$source->id]	= [];													//  prepare empty module list for source
			foreach( $this->listModulesInSource( $source ) as $module ){								//  iterate modules in source path
				$module->sourceId	= $source->id;													//  extend found module by source ID
				$module->sourcePath	= $source->path;													//  extend found module by source path
				$module->sourceType	= $source->type;													//  extend found module by source type
				$this->modules[$source->id][$module->id] = $module;									//  add found module to general module map
				ksort( $this->modules[$source->id] );												//  sort source modules in general module map
			}
			$this->client->outVeryVerbose( vsprintf( '- Found %d modules', [
				count( $this->modules[$source->id] )
			] ) );
		}
		$this->client->outVeryVerbose( $this->client->getMemoryUsage( 'after loading module sources' ) );
//		ksort( $this->modules );																	//  sort general module map by source IDs
	}

	/**
	 * @param string $fileSerial
	 * @param string $path
	 * @return array<mixed,array<string,Hymn_Structure_Module>>
	 */
	protected function loadModulesFromSerialFile( string $fileSerial, string $path ): array
	{
		$this->client->outVeryVerbose( '- Strategy: serial file' );
		$content	= file_get_contents( $fileSerial );
		if( FALSE === $content )
			throw new RuntimeException( 'Reading file "'.$fileSerial.'" failed' );
		$index		= unserialize( $content );
		foreach( $index->modules as $module ){
			$module->frameworks		= (array) $module->frameworks;
//			$module->isDeprecated	= isset( $module->deprecation );
			$this->decorateModuleWithPaths( $module, $path );
		}
		$list	= $index->modules;
		return [$index, $list];
	}

	/**
	 *	@param		string		$fileJson
	 *	@param		array<string,Hymn_Structure_Module>	$list
	 *	@param		string		$path
	 *	@return		array
	 */
	protected function loadModulesFromJsonFile( string $fileJson, array $list, string $path ): array
	{
		$this->client->outVeryVerbose( '- Strategy: JSON file' );
		$content	= file_get_contents( $fileJson );
		if( FALSE === $content )
			throw new RuntimeException( 'Reading file "'.$fileJson.'" failed' );
		/** @var object{modules: array<object>} $index */
		$index	= json_decode( $content );
		foreach( $index->modules as $module ){
			$module	= $this->convertModuleDataObjectToStructureObject( $module, $fileJson );
			$this->decorateModuleWithPaths( $module, $path );
			$list[$module->id]	= $module;
		}
		return [$index, $list];
	}

	/**
	 *	@param		object	$module
	 *	@param		string											$filePath
	 *	@return		Hymn_Structure_Module
	 */
	protected function convertModuleDataObjectToStructureObject( object $module, string $filePath ): Hymn_Structure_Module
	{
		/** @var Hymn_Structure_Module $module $obj */
		//  work in progress
		$obj	= new Hymn_Structure_Module( $module->id, $module->version->current, $filePath );
		foreach( $module->config ?? [] as $config )
			$obj->config[]	= new Hymn_Structure_Module_Config( $config->key, $config->value, $config->type, $config->title );
		/** @var object{callback: string, resource: string, event: string, level: int} $hook */
		foreach( $config->hooks ?? [] as $hook )
			$obj->hooks[]	= new Hymn_Structure_Module_Hook( $hook->callback, $hook->resource, $hook->event, $hook->level );

		$list[$module->id]	= $module;
		$module->config					= (array) $module->config;
		$module->hooks					= (array) $module->hooks;
		foreach( $module->hooks as $resource => $events )
			$module->hooks[$resource]	= (array) $module->hooks[$resource];
		foreach( $module->files->toArray() as $category => $files )
			$module->files->{$category}	=  (array) $files;
		$module->relations->needs		= (array) $module->relations->needs;
		$module->relations->supports	= (array) $module->relations->supports;
//			$module->isDeprecated			= isset( $module->deprecation );
		if( isset( $module->frameworks ) )
			$module->frameworks			= (array) $module->frameworks;
		return $obj;

	}
	/**
	 *	@param		string		$path
	 *	@param		array		$list
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	protected function loadModulesFromSourceFolder( string $path, array $list ): array
	{
		$this->client->outVeryVerbose( '- Strategy: folder' );
		//			if( $this->useCache && $this->listModulesAvailable !== NULL )			//  @todo realize sources in cache
		//				return $this->listModulesAvailable;									//  @todo realize sources in cache
		$iterator	= new RecursiveDirectoryIterator( $path );
		$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
		foreach( $index as $entry ){
			if( !$entry->isFile() || !preg_match( "/^module\.xml$/", $entry->getFilename() ) )
				continue;
			$key	= str_replace( "/", "_", substr( $entry->getPath(), strlen( $path ) ) );
			$module	= $this->readModule( $path, $key );
			$list[$key]	= $module;
		}
		return $list;
	}
}
/*
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
*/