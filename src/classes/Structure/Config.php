<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Structure
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Structure
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Structure_Config
{
	/** @var Hymn_Structure_Config_Application $application */
	public Hymn_Structure_Config_Application $application;

	/** @var Hymn_Structure_Config_Source[] $sources */
	public array $sources		= [];

	/** @var Hymn_Structure_Config_Module[] $modules */
	public array $modules		= [];

	/** @var Hymn_Structure_Config_Paths $paths */
	public Hymn_Structure_Config_Paths $paths;

	/** @var Hymn_Structure_Config_System $application */
	public Hymn_Structure_Config_System $system;

	/** @var Hymn_Structure_Config_Database $application */
	public Hymn_Structure_Config_Database $database;

	public Hymn_Structure_Config_Layout $layout;

	public function __construct()
	{
		$this->application	= new Hymn_Structure_Config_Application();
		$this->system		= new Hymn_Structure_Config_System();
		$this->database		= new Hymn_Structure_Config_Database();
		$this->paths		= new Hymn_Structure_Config_Paths();
		$this->layout		= new Hymn_Structure_Config_Layout();
	}
}