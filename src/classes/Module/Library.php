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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Library
{
	protected array|NULL $listModulesAvailable	= NULL;
	protected array|NULL $listModulesInstalled	= NULL;
	protected bool $useCache					= FALSE;

	protected Hymn_Client $client;

	protected array $modules					= [];

	/**
	 * @var		Hymn_Module_Library_Available		$available
	 */
	protected Hymn_Module_Library_Available $available;

	/**
	 * @var		Hymn_Module_Library_Installed		$installed
	 */
	protected Hymn_Module_Library_Installed $installed;

	public function __construct( Hymn_Client $client )
	{
		$this->client		= $client;
		$this->installed	= new Hymn_Module_Library_Installed( $client );
		$this->available	= new Hymn_Module_Library_Available( $client );
	}

	/**
	 *	Not used atm.
	 *	@todo what to do with this?
	 */
	public function addModuleToSource( Hymn_Structure_Module $module, Hymn_Structure_Source $source ): static
	{
		$this->available->addModuleToSource( $module, $source );
		return $this;
	}

	/**
	 *	@param		string		$sourceId
	 *	@param		string		$path
	 *	@param		string		$type
	 *	@param		bool		$active
	 *	@param		?string		$title
	 *	@return		static
	 *	@throws		InvalidArgumentException	if source already exists
	 */
	public function addSource( string $sourceId, string $path, string $type, bool $active = TRUE, string $title = NULL ): static
	{
		$this->available->addSource( $sourceId, $path, $type, $active, $title );
		return $this;
	}

	/**
	 *	@param		bool		$withModules		Default: no
	 *	@return		array<string,Hymn_Structure_Source>
	 */
	public function getActiveSources( bool $withModules = FALSE ): array
	{
		return $this->available->getActiveSources( $withModules );
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		?string		$sourceId
	 *	@param		bool		$strict
	 *	@return		Hymn_Structure_Module|NULL
	 */
	public function getAvailableModule( string $moduleId, ?string $sourceId = NULL, bool $strict = TRUE ): ?Hymn_Structure_Module
	{
		return $this->available->get( $moduleId, $sourceId, $strict );
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@param		bool		$strict
	 *	@return		Hymn_Structure_Module|NULL
	 *	@deprecated	not used anymore -> to be deleted
	 */
	public function getAvailableModuleFromSource( string $moduleId, string $sourceId, bool $strict = TRUE ): ?Hymn_Structure_Module
	{
		if( '' === trim( $moduleId ) ){
			if( $strict )
				throw new InvalidArgumentException( __METHOD__.' > Module ID cannot be empty' );
			return NULL;
		}
		return $this->available->getFromSource( $moduleId, $sourceId, $strict );
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@param		string		$versionInstalled
	 *	@param		string		$versionAvailable
	 *	@return		array
	 */
	public function getAvailableModuleLogChanges( string $moduleId, string $sourceId, string $versionInstalled, string $versionAvailable ): array
	{
		return $this->available->getModuleLogChanges( $moduleId, $sourceId, $versionInstalled, $versionAvailable );
	}

	/**
	 *	@param		string|NULL		$sourceId
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	public function getAvailableModules( ?string $sourceId = NULL ): array
	{
		return $this->available->getAll( $sourceId );
	}

	public function getAvailableModuleSources( string $moduleId ): array
	{
		return $this->available->getModuleSources( $moduleId );
	}

	public function getDefaultSource(): string
	{
		return $this->available->getDefaultSource();
	}

	public function getOutdatedModules(): array
	{
		$listInstalled		= $this->listInstalledModules();										//  get list of installed modules
		$outdatedModules	= [];																	//  prepare empty list of updatable modules
		foreach( $listInstalled as $installedModule ){												//  iterate installed modules
			$source				= $installedModule->install->source;								//  get installed module
			$availableModule	= $this->getAvailableModule( $installedModule->id, $source, FALSE );		//  get available module
			if( $availableModule ){																	//  installed module is available at least
				$versionInstalled	= $installedModule->version->current;							//  shortcut installed module version
				$versionAvailable	= $availableModule->version->current;							//  shortcut available module version
				if( version_compare( $versionAvailable, $versionInstalled, '>' ) ){			//  installed module is outdated
					$outdatedModules[$installedModule->id]	= (object) [							//  note updatable module
						'id'		=> $installedModule->id,
						'installed'	=> $versionInstalled,
						'available'	=> $versionAvailable,
						'source'	=> $installedModule->install->source,
					];
				}
			}
		}
		return $outdatedModules;
	}

	public function getSource( string $sourceId, bool $withModules = FALSE ): Hymn_Structure_Source
	{
		return $this->available->getSource( $sourceId, $withModules );
	}

	/**
	 *	@param		array		$filters
	 *	@param		bool		$withModules
	 *	@return		array<Hymn_Structure_Source>
	 */
	public function getSources( array $filters = [], bool $withModules = FALSE ): array
	{
		return $this->available->getSources( $filters, $withModules );
	}

	/**
	 *	Reads an available (within a source) module directly from source folder, bypassing any caches.
	 *	This is handy, if full module information is needed, e.G. on installation.
	 *	The pure module data structure will be extended by source information.
	 *	@access		public
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@return		object		Uncached module data object
	 *	@throws		RangeException		if module.xml not found in module path (resolved from ID)
	 *	@throws		RuntimeException	if XML file did not pass validation
	 *	@throws		Exception			if XML file could not been loaded and parsed
	 *	@throws		RuntimeException	if SQL update script is missing version
	 *	@throws		RuntimeException	if inline function found in hook (which is deprecated)
	 */
	public function getUncachedAvailableModuleFromSource( string $moduleId, string $sourceId ): object
	{
		$source		= $this->getSource( $sourceId );								//  get source
		$module		= $this->available->readModule( $source->path, $moduleId );	//  try to read module from source folder
		$module->sourceId	= $source->id;										//  extend found module by source ID
		$module->sourcePath	= $source->path;										//  extend found module by source path
		$module->sourceType	= $source->type;										//  extend found module by source type
		return $module;
	}

	/**
	 *	@param		string		$sourceId
	 *	@return		bool
	 *	@deprecated	not used anymore -> to be deleted
	 */
	public function isActiveSource( string $sourceId ): bool
	{
		return array_key_exists( $sourceId, $this->getActiveSources() );
	}

	/**
	 *	@param		string		$moduleId
	 *	@param		string		$sourceId
	 *	@return		bool
	 */
	public function isAvailableModuleInSource( string $moduleId, string $sourceId ): bool
	{
		if( '' === trim( $moduleId ) )
			throw new InvalidArgumentException( __METHOD__.' > Module ID cannot be empty' );
		return (bool) $this->available->getFromSource( $moduleId, $sourceId, FALSE );
	}

	/**
	 *	@param		string		$moduleId
	 *	@return		bool
	 */
	public function isInstalledModule( string $moduleId ): bool
	{
		return $this->installed->has( $moduleId );
	}

	/**
	 *	@param		string		$sourceId
	 *	@return		bool
	 */
	public function isSource( string $sourceId ): bool
	{
		return array_key_exists( $sourceId, $this->getSources() );
	}

	/**
	 *	@param		string|null		$sourceId
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	public function listInstalledModules( ?string $sourceId = NULL ): array
	{
//		if( $this->useCache && $this->listModulesInstalled !== NULL )			//  @todo realize sources in cache
//			return $this->listModulesInstalled;									//  @todo realize sources in cache
		$list	= $this->installed->getAll( $sourceId );
//		$this->listModulesInstalled	= $list;									//  @todo realize sources in cache
		return $list;
	}

	/**
	 *	@param		string		$moduleId
	 *	@return		Hymn_Structure_Module
	 *	@throws		RangeException		if module is not installed
	 *	@throws		RuntimeException	if XML file did not pass validation
	 *	@throws		Exception			if XML file could not been loaded and parsed
	 *	@throws		RuntimeException	if SQL update script is missing version
	 *	@throws		RuntimeException	if inline function found in hook (which is deprecated)
	 */
	public function readInstalledModule( string $moduleId ): Hymn_Structure_Module
	{
		return $this->installed->get( $moduleId );
	}

	/**
	 *	@param		int			$mode
	 *	@return		self
	 */
	public function setReadMode( int $mode ): self
	{
		$this->available->setMode( $mode );
		return $this;
	}

	/**
	 *	@param		bool		$useCache
	 *	@return		self
	 */
	public function useCache( bool $useCache = TRUE ): self
	{
		$this->useCache		= $useCache;
		return $this;
	}
}
