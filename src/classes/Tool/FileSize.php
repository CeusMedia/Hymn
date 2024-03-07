<?php
/**
 *	Formats Numbers intelligently and adds Units to Bytes and Seconds.
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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
define( 'SIZE_BYTE', pow( 1024, 0 ) );
define( 'SIZE_KILOBYTE', pow( 1024, 1 ) );
define( 'SIZE_MEGABYTE', pow( 1024, 2 ) );
define( 'SIZE_GIGABYTE', pow( 1024, 3 ) );
/**
 *	Formats Numbers intelligently and adds Units to Bytes and Seconds.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_FileSize
{
	/**	@var		array		$unitBytes		List of Byte Units */
	public static array $unitBytes	= [
		'B',
		'KB',
		'MB',
		'GB',
		'TB',
		'PB',
		'EB',
		'ZB',
		'YB'
	];

	/**
	 *	Formats file size by switching to next higher unit if a set edge is reached.
	 *	Edge is a factor when to switch to next higher unit, eG. 0.5 means 50% of 1024.
	 *	If you enter 512 (B) it will return 0.5 KB.
	 *	Caution! With precision at 0 you may have rounding errors.
	 *	To avoid the units to be appended, enter FALSE or NULL for indent.
	 *	@access		public
	 *	@static
	 *	@param		string		$filePath		...
	 *	@param		int			$precision		Number of floating point digits
	 *	@param		string		$indent			Space between number and unit
	 *	@param		float		$edge			Factor of next higher unit when to break
	 *	@return		string|float
	 */
	public static function get( string $filePath, int $precision = 1, string $indent = " ", float $edge = 0.5 ): float|string
	{
		if( !file_exists( $filePath ) )
			throw new RuntimeException( 'File "'.$filePath.'" is not existing' );
		/** @var int $size */
		$size	= filesize( $filePath );
		return self::formatBytes( $size, $precision, $indent, $edge );
	}

	/**
	 *	Formats number of bytes by switching to next higher unit if a set edge is reached.
	 *	Edge is a factor when to switch to next higher unit, eG. 0.5 means 50% of 1024.
	 *	If you enter 512 (B) it will return 0.5 KB.
	 *	Caution! With precision at 0 you may have rounding errors.
	 *	To avoid the units to be appended, enter FALSE or NULL for indent.
	 *	@access		public
	 *	@static
	 *	@param		int			$bytes			Number of bytes
	 *	@param		int			$precision		Number of floating point digits
	 *	@param		string		$indent			Space between number and unit
	 *	@param		float		$edge			Factor of next higher unit when to break
	 *	@return		string|float
	 */
	public static function formatBytes( int $bytes, int $precision = 1, string $indent = " ", float $edge = 0.5 ): float|string
  {
		$float		= (float) $bytes;
		$unitKey	= 0;														//  step to first Unit
		$edge		= abs( $edge );												//  avoid negative Edges
		$edge		= min( $edge, 1);									//  avoid senseless Edges
		$edgeValue	= 1024 * $edge;												//  calculate Edge Value
		while( $float >= $edgeValue ){											//  Value is larger than Edge
			$unitKey ++;														//  step to next Unit
			$float	/= 1024;													//  calculate Value in new Unit
		}
		$float	= round( $float, $precision );									//  round Value
		return $float.$indent.self::$unitBytes[$unitKey];						//  append Unit and return
	}
}
