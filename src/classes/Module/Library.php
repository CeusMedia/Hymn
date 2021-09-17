<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Library
{
	protected $listModulesAvailable	= NULL;
	protected $listModulesInstalled	= NULL;
	protected $useCache				= FALSE;

	protected $client;

	protected $modules		= array();

	/**
	 * @var		Hymn_Module_Library_Available		$available
	 */
	protected $available;

	/**
	 * @var		Hymn_Module_Library_Installed		$installed
	 */
	protected $installed;

	public function __construct( Hymn_Client $client )
	{
		$this->client		= $client;
		$this->installed	= new Hymn_Module_Library_Installed( $client );
		$this->available	= new Hymn_Module_Library_Available( $client );
	}

	public function addShelf( string $shelfId, string $path, string $type, bool $active = TRUE, string $title = NULL )
	{
		return $this->available->addShelf( $shelfId, $path, $type, $active, $title );
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

	public function getShelf( string $moduleId, bool $withModules = FALSE )
	{
		return $this->available->getShelf( $moduleId, $withModules );
	}

	public function getShelves( array $filters = array(), bool $withModules = FALSE ): array
	{
		return $this->available->getShelves( $filters, $withModules );
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

	public function readInstalledModule( string $moduleId )
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
