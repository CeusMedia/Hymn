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
class Hymn_Tool_SourceIndex_IniReader
{
	/**	@var	array		$data */
	protected array $data;

	/**	@var	string		$fileName	Name of settings file */
	protected string $fileName	= '.index.ini';

	protected array $empty	= [
		'id'				=> NULL,
		'title'				=> NULL,
		'url'				=> NULL,
		'description'		=> NULL,
	];

	/**
	 *	@access		public
	 *	@param		string		$pathSource		Path to module source root
	 *	@return		void
	 */
	public function __construct( string $pathSource )
	{
		$this->data	= $this->empty;
		if( file_exists( $pathSource.$this->fileName ) ){
			$data	= parse_ini_file( $pathSource.$this->fileName );
			if( FALSE !== $data )
				$this->data	= array_merge( $this->data, $data );
		}
	}

	public function get( string $key ): int|float|bool|string|NULL
	{
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
		return NULL;
	}

	public function getAll(): array
	{
		return $this->data;
	}
}
