<?php
/**
 *	Module definition: Relations.
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
 *	@package		CeusMedia.HydrogenFramework.Environment.Resource.Module.Definition
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

/**
 *	Module definition: Relations.
 *
 *	@category		Library
 *	@package		CeusMedia.HydrogenFramework.Environment.Resource.Module.Definition
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Structure_Module_Relations
{
	/**	@var		array<Hymn_Structure_Module_Relation>		$needs */
	public array $needs			= [];

	/**	@var		array<Hymn_Structure_Module_Relation>		$supports */
	public array $supports		= [];

	/**	@var		array<Hymn_Structure_Module>		$requiredBy */
	public array $requiredBy	= [];

	/**	@var		array<Hymn_Structure_Module>		$neededBy */
	public array $neededBy	= [];
}