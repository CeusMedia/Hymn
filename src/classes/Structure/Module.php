<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	Module definition.
 *
 *	Copyright (c) 2022-2024 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

/**
 *	Module definition.
 *
 *	@category		Library
 *	@package		Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Structure_Module
{
	public string $id;
	public ?string $source				= NULL;
	public ?string $sourceId				= NULL;
	public ?string $sourcePath				= NULL;
	public ?string $sourceType				= NULL;
	public string $file;
	public ?string $uri					= NULL;
	public ?string $path				= NULL;
//	public ?string $pathName			= NULL;
	public ?string $absolutePath		= NULL;
	public string $title;
	public string $category;
	public string $description;
	public bool $isActive				= TRUE;
	public bool $isInstalled			= FALSE;
	public array $frameworks			= [];
//	public string $version;

	/**	@var Hymn_Structure_Module_Version $version */
	public Hymn_Structure_Module_Version $version;

	/**	@var ?Hymn_Structure_Module_Deprecation $deprecation */
	public ?Hymn_Structure_Module_Deprecation $deprecation	= NULL;

	/**	@var array<Hymn_Structure_Module_Company> $companies */
	public array $companies				= [];

	/**	@var array<Hymn_Structure_Module_Author> $authors */
	public array $authors				= [];

	/**	@var array<Hymn_Structure_Module_License> $authors */
	public array $licenses				= [];

	/**	@var Hymn_Structure_Module_Files $files */
	public Hymn_Structure_Module_Files $files;

	/** @var array<Hymn_Structure_Module_Config> $config */
	public array $config				= [];

	/**	@var Hymn_Structure_Module_Relations $relations */
	public Hymn_Structure_Module_Relations $relations;

	/** @var array<string,Hymn_Structure_Module_SQL> $sql */
	public array $sql					= [];

	public array $links					= [];

	/** @var array<string,array<string,array<int,Hymn_Structure_Module_Hook>>> $hooks */
	public array $hooks					= [];

	/** @var array<int|string,Hymn_Structure_Module_Job> $jobs */
	public array $jobs					= [];

	/** @var ?Hymn_Structure_Module_Installation $install */
	public ?Hymn_Structure_Module_Installation $install		= NULL;

	public ?string $price				= NULL;
	public ?string $icon				= NULL;

	/**
	 *	Constructor.
	 *	@param		string			$id			Module ID
	 *	@param		string			$version	Version of module
	 *	@param		string			$file		Path to XML file holding the module definition
	 *	@param		string|NULL		$uri		Path to module (=folder of module file)
	 */
	public function __construct( string $id, string $version, string $file, ?string $uri = NULL )
	{
		$this->id			= $id;
		$this->file			= $file;
		$this->uri			= $uri ?? ( realpath( $file ) ?: NULL );
		$this->version		= new Hymn_Structure_Module_Version( $version );
		$this->files		= new Hymn_Structure_Module_Files();
		$this->relations	= new Hymn_Structure_Module_Relations();
		$this->install		= new Hymn_Structure_Module_Installation();
	}
}
