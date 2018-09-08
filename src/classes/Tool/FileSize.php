<?php
/**
 *	Formats Numbers intelligently and adds Units to Bytes and Seconds.
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2018 Christian Würker
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
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_FileSize{

	/**	@var		array		$unitBytes		List of Byte Units */
	public static $unitBytes	= array(
		'B',
		'KB',
		'MB',
		'GB',
		'TB',
		'PB',
		'EB',
		'ZB',
		'YB'
	);

	/**
	 *	Formats Number of Bytes by switching to next higher Unit if an set Edge is reached.
	 *	Edge is a Factor when to switch to ne next higher Unit, eG. 0.5 means 50% of 1024.
	 *	If you enter 512 (B) it will return 0.5 KB.
	 *	Caution! With Precision at 0 you may have Errors from rounding.
	 *	To avoid the Units to be appended, enter FALSE or NULL for indent.
	 *	@access		public
	 *	@static
	 *	@param		float		$float			Number of Bytes
	 *	@param		int			$precision		Number of Floating Point Digits
	 *	@param		string		$indent			Space between Number and Unit
	 *	@param		float		$edge			Factor of next higher Unit when to break
	 *	@return		string
	 */
	public static function get( $filePath, $precision = 1, $indent = " ", $edge = 0.5 ){
		if( !file_exists( $filePath ) )
			throw new RuntimeException( 'File "'.$filePath.'" is not existing' );
		$float		= (float) filesize( $filePath );
		$unitKey	= 0;														//  step to first Unit
		$edge		= abs( $edge );												//  avoid negative Edges
		$edge		= $edge > 1 ? 1 : $edge;									//  avoid senseless Edges
		$edgeValue	= 1024 * $edge;												//  calculate Edge Value
		while( $float >= $edgeValue ){											//  Value is larger than Edge
			$unitKey ++;														//  step to next Unit
			$float	/= 1024;													//  calculate Value in new Unit
		}
		if( is_int( $precision ) )												//  Precision is set
			$float	= round( $float, $precision );								//  round Value
		if( is_string( $indent ) )												//  Indention is set
			$float	= $float.$indent.self::$unitBytes[$unitKey];					//  append Unit
		return $float;															//  return resultung Value
	}
}
?>
