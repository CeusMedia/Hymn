<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Library_Available
{
	protected Hymn_Client $client;

	/** @var array<string,array<string,Hymn_Structure_Module>> $modules  */
	protected array $modules			= [];

	/** @var	array<string,Hymn_Structure_Source>	$sources  */
	protected array $sources			= [];

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	/**
	 *	Not used atm.
	 *	@todo what to do with this?
	 */
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
	 *	@throws		InvalidArgumentException	if source already exists
	 */
	public function addSource( string $sourceId, string $path, string $type, bool $active = TRUE, ?string $title = NULL ): static
	{
		if( in_array( $sourceId, array_keys( $this->sources ) ) )
			throw new InvalidArgumentException( 'Source already set by ID: '.$sourceId );
		$defaultSources	= $this->getSources( ['isDefault' => TRUE] );
		$isDefault		= $active && [] === $defaultSources;										//  first active source is default
		$this->sources[$sourceId]	= Hymn_Structure_Source::fromArray( [
			'id'		=> $sourceId,
			'path'		=> $path,
			'type'		=> $type,
			'active'	=> $active,
			'isDefault'	=> $isDefault,
			'title'		=> $title ?? '',
			'date'		=> NULL,
		] );
//		ksort( $this->sources );
		return $this;
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		?string		$sourceId
	 *	@param		bool		$strict			Default: yes
	 *	@return		?Hymn_Structure_Module
	 *	@throws		InvalidArgumentException	if not module ID is given
	 *	@throws		DomainException				if source ID is invalid
	 *	@throws		DomainException				if module ID is invalid
	 */
	public function get( string $moduleId, ?string $sourceId = NULL, bool $strict = TRUE ): ?Hymn_Structure_Module
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
			throw new DomainException( __METHOD__.' > Invalid module ID: '.$moduleId.' (source: '.$sourceId.')' );
		return NULL;
	}

	/**
	 *	@param		bool		$withModules		Default: no
	 *	@return		array<string,Hymn_Structure_Source>
	 */
	public function getActiveSources( bool $withModules = FALSE ): array
	{
		return $this->getSources( ['active' => TRUE], $withModules );
	}

	/**
	 *	@param		string|NULL		$sourceId
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	public function getAll( ?string $sourceId = NULL ): array
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
	 *	@throws		InvalidArgumentException	if not module ID is given
	 *	@throws		DomainException				if source ID is invalid
	 *	@throws		DomainException				if module ID is invalid
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
			throw new DomainException( 'Invalid module ID: '.$moduleId.' (source: '.$sourceId.')' );
		return NULL;
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@param		string		$versionInstalled
	 *	@param		string		$versionAvailable
	 *	@return		array
	 *	@throws		InvalidArgumentException	if not module ID is given
	 *	@throws		DomainException				if source ID is invalid
	 *	@throws		DomainException				if module ID is invalid
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
		$source				= clone $this->sources[$sourceId];									//  copy: modules are set for caller, only
		$source->modules	= [];
		if( $withModules ){
			$this->loadModulesInSources();
			$source->modules	= $this->modules[$sourceId] ?? [];
		}
		return $source;
	}

	/**
	 *	@param		array		$filters
	 *	@param		bool		$withModules
	 *	@return		array<string,Hymn_Structure_Source>
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
	 *	@throws		RangeException		if module.xml not found in module path (resolved from ID)
	 *	@throws		RuntimeException	if XML file did not pass validation
	 *	@throws		Exception			if XML file could not been loaded and parsed
	 *	@throws		RuntimeException	if SQL update script is missing version
	 *	@throws		RuntimeException	if inline function found in hook (which is deprecated)
	 */
	public function readModule( string $path, string $moduleId ): Hymn_Structure_Module
	{
		$pathname	= str_replace( "_", "/", $moduleId ).'/';										//  assume source module path from module ID
		$filename	= $path.$pathname.'module.xml';													//  assume module config file name in assumed source module path
		if( !file_exists( $filename ) )																//  assume module config file is not existing
			throw new RangeException( 'Missing module XML file in '.$pathname );					//  throw exception
		$module		= Hymn_Module_Reader::load( $filename, $moduleId );								//  otherwise load module configuration from source XML file
		$this->decorateModuleWithPaths( $module, $path );
		return $module;																				//  return module
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

		//  modules are read from module folders – source indices (index.json, index.serial) are not used,
		//  since they do not support the module structure objects
		return $this->loadModulesFromSourceFolder( $path, [] );
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
			try{
				$module	= $this->readModule( $path, $key );
				$list[$key]	= $module;
			}
			catch( Throwable $e ){
				$this->client->outError( 'Skipped module '.$key.': '.$e->getMessage() );
			}
		}
		return $list;
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
}
