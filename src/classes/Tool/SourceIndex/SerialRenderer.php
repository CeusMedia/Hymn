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
 *	@package		CeusMedia.Hymn.Tool.SourceIndex
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.SourceIndex
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_SourceIndex_SerialRenderer
{
	/**	@var	array		$modules */
	protected array $modules	= [];

	/**	@var	Hymn_Tool_SourceIndex_IniReader|NULL	$settings */
	protected ?Hymn_Tool_SourceIndex_IniReader $settings;

	/**
	 *	@access		public
	 *	@return		string
	 *	@throws		RuntimeException		if not settings are set
	 */
	public function render(): string
	{
		if( $this->settings === NULL )
			throw new RuntimeException( 'No settings set' );

		$data	= (object) [
			'id'			=> $this->settings->get( 'id' ),
			'title'			=> $this->settings->get( 'title' ),
//			'version'		=> $this->settings->get( 'version' ),
			'url'			=> $this->settings->get( 'url' ),
			'description'	=> $this->settings->get( 'description' ),
			'date'			=> date( 'Y-m-d' ),
			'modules'		=> $this->modules,
		];
		return serialize( $data );
	}

	/**
	 *	@access		public
	 *	@param		array		$modules		...
	 *	@return		self
	 */
	public function setModules( array $modules ): self
	{
		$this->modules	= $modules;
		return $this;
	}

	/**
	 *	@access		public
	 *	@param		Hymn_Tool_SourceIndex_IniReader	$settings		...
	 *	@return		self
	 */
	public function setSettings( Hymn_Tool_SourceIndex_IniReader $settings ): self
	{
		$this->settings	= $settings;
		return $this;
	}
}
