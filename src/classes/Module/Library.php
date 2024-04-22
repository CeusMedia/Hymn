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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
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

	public function addShelf( string $shelfId, string $path, string $type, bool $active = TRUE, string $title = NULL ): void
	{
		$this->available->addShelf( $shelfId, $path, $type, $active, $title );
	}

	public function getActiveShelves( bool $withModules = FALSE ): array
	{
		return $this->available->getActiveShelves( $withModules );
	}

	public function getAvailableModule( string $moduleId, ?string $shelfId = NULL, bool $strict = TRUE )
	{
		return $this->available->get( $moduleId, $shelfId, $strict );
	}

	public function getAvailableModuleFromShelf( string $moduleId, string $shelfId, bool $strict = TRUE )
	{
		return $this->available->getFromShelf( $moduleId, $shelfId, $strict );
	}

	public function getAvailableModuleLogChanges( string $moduleId, string $shelfId, string $versionInstalled, string $versionAvailable ): array
	{
		return $this->available->getModuleLogChanges( $moduleId, $shelfId, $versionInstalled, $versionAvailable );
	}

	public function getAvailableModules( ?string $shelfId = NULL ): array
	{
		return $this->available->getAll( $shelfId );
	}

	public function getAvailableModuleShelves( string $moduleId ): array
	{
		return $this->available->getModuleShelves( $moduleId );
	}

	public function getDefaultShelf(): string
	{
		return $this->available->getDefaultShelf();
	}

	public function getShelf( string $shelfId, bool $withModules = FALSE )
	{
		return $this->available->getShelf( $shelfId, $withModules );
	}

	public function getShelves( array $filters = [], bool $withModules = FALSE ): array
	{
		return $this->available->getShelves( $filters, $withModules );
	}

	/**
	 *	Reads an available (within a shelf) module directly from source folder, bypassing any caches.
	 *	This is handy, if full module information is needed, e.G. on installation.
	 *	The pure module data structure will be extended by shelf information.
	 *	@access		public
	 *	@param		string		$moduleId
	 *	@param		string		$shelfId
	 *	@return		object		Uncached module data object
	 */
	public function getUncachedAvailableModuleFromShelf( string $moduleId, string $shelfId ): object
	{
		$shelf		= $this->getShelf( $shelfId );								//  get shelf
		$module		= $this->available->readModule( $shelf->path, $moduleId );	//  try to read module from source folder
		$module->sourceId	= $shelf->id;										//  extend found module by source ID
		$module->sourcePath	= $shelf->path;										//  extend found module by source path
		$module->sourceType	= $shelf->type;										//  extend found module by source type
		return $module;
	}

	public function isAvailableModuleInShelf( string $moduleId, string $shelfId ): bool
	{
		return (bool) $this->available->getFromShelf( $moduleId, $shelfId, FALSE );
	}

	public function isInstalledModule( string $moduleId ): bool
	{
		return $this->installed->has( $moduleId );
	}

	public function isActiveShelf( string $shelfId ): bool
	{
		return array_key_exists( $shelfId, $this->getActiveShelves() );
	}

	public function isShelf( string $shelfId ): bool
	{
		return array_key_exists( $shelfId, $this->getShelves() );
	}

	public function listInstalledModules( ?string $shelfId = NULL ): array
	{
//		if( $this->useCache && $this->listModulesInstalled !== NULL )			//  @todo realize shelves in cache
//			return $this->listModulesInstalled;									//  @todo realize shelves in cache
		$list	= $this->installed->getAll( $shelfId );
//		$this->listModulesInstalled	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	public function readInstalledModule( string $moduleId ): object
	{
		return $this->installed->get( $moduleId );
	}

	public function useCache( bool $useCache = TRUE ): self
	{
		$this->useCache		= $useCache;
		return $this;
	}

	public function setReadMode( int $mode ): self
	{
		$this->available->setMode( $mode );
		return $this;
	}
}
