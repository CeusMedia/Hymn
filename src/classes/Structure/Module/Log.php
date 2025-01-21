<?php
declare(strict_types=1);

/**
 *	Module definition: Version Log Note.
 *
 *	Copyright (c) 2024-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

/**
 *	Module definition: Version Log Note.
 *
 *	@category		Library
 *	@package		Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Structure_Module_Log
{
	/** @var string $version */
	public string $version		= '';

	/** @var string $note */
	public string $note			= '';

	/**
	 *	@param		string			$version
	 *	@param		string			$note
	 */
	public function __construct( string $version, string $note )
	{
		$this->version	= $version;
		$this->note		= $note;
	}
}