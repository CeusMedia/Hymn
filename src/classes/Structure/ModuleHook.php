<?php
declare(strict_types=1);

/**
 *	Definition of collected hook information related to a module.
 *	This data structure is for use in hook related commands.
 *
 *	Attention: This is NOT the hook definition of a module.
 *	This would be Hymn_Structure_Module_Hook.
 *
 *	Copyright (c) 2022-2025 Christian Würker (ceusmedia.de)
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
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Structure
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2020-2025 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

/**
 *	Definition of collected hook information related to a module.
 *	This data structure is for use in hook related commands.
 *
 *	Attention: This is NOT the hook definition of a module.
 *	This would be Hymn_Structure_Module_Hook.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Structure
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2020-2025 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Structure_ModuleHook
{
	public string $moduleId;
	public string $event;
	public string $resource;
	public string $callback;
	public int $level;

	public function __construct( string $moduleId, string $event, string $resource, string $callback, int $level = 5 )
	{
		$this->moduleId = $moduleId;
		$this->event	= $event;
		$this->resource	= $resource;
		$this->callback	= $callback;
		$this->level	= $level;
	}
}