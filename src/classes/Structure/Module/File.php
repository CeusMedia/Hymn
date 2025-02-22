<?php
declare(strict_types=1);

/**
 *	Module definition: File.
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
 *	@package		CeusMedia.Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2025 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

/**
 *	Module definition: File.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2022-2025 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Structure_Module_File
{
	/** @var	string				$file */
	public string $file;

	/** @var	bool|string|NULL	$load */
	public string|bool|NULL $load	= NULL;

	/** @var	int|string|NULL		$level */
	public string|int|NULL $level	= NULL;

	/** @var	string|NULL			$source */
	public ?string $source			= NULL;

	/** @var	string|NULL			$theme */
	public ?string $theme			= NULL;

	/**
	 *	@param		string		$file
	 */
	public function __construct( string $file )
	{
		$this->file		= $file;
	}
}
