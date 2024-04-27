<?php
/**
 *	...
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
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Tool_CLI_Table
{
	public int $consoleWidth	= 78;

	protected string $encoding	= "UTF-8";

	protected Hymn_Client $client;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function detectWidth(): void
  {
		$cols	= intval( `tput cols` );
		$this->consoleWidth	= ( $cols ?: 80 ) - 3;
	}

	public function render( array $data ): string
	{
		if( 0 === count( $data ) )
			return '';

		$data		= $this->cleanData( $data );
		$columns	= $this->calculateColumnWidths( $data );

		$row		= [];
		foreach( $columns as $column )
			$row[]	= $this->fit( $column->label, $column->size );

		$list		= [
			$this->line( "=", $this->consoleWidth ),
			join( ' ', $row ),
			$this->line( "-", $this->consoleWidth ),
		];

		foreach( $data as $entry ){
			$row	= [];
			foreach( array_values( $entry ) as $nr => $value ){
				$row[]	= $this->fit( $value, $columns[$nr]->size );
			}
			$list[]	= join( ' ', $row );
		}
		$list[]	= $this->line( "=", $this->consoleWidth );
		return join( "\n", $list );
	}

	//  --  PROTECTED  --  //

	protected function calculateColumnWidths( array $data ): array
	{
		$keys		= array_keys( reset( $data ) );
		$colWidths	= [];
		foreach( $keys as $nr => $key )
			$colWidths[$nr]	= (object) [
				'head'	=> $this->strlen( $key ),
				'label'	=> $key,
				'min'	=> pow( 10, 6 ),
				'max'	=> 0,
				'size'	=> 0,
			];

		foreach( $keys as $nr => $key ){
			$column	= array_column( $data, $key );
			foreach( $column as $item ){
				$length	= $this->strlen( $item );
				if( $length ){
					$colWidths[$nr]->min	= min( $colWidths[$nr]->min, $length );
					$colWidths[$nr]->max	= max( $colWidths[$nr]->max, $length );
				}
			}
			if( $colWidths[$nr]->max <= $colWidths[$nr]->head )
				$colWidths[$nr]->size	= $colWidths[$nr]->head + 2;
			else if( $colWidths[$nr]->max <= $colWidths[$nr]->head * 2 )
				$colWidths[$nr]->size	= $colWidths[$nr]->max + 2;
//			else if( $colWidths[$nr]->max <= $colWidths[$nr]->head * 4 && $colWidths[$nr]->max < $this->consoleWidth / 6 )
//				$colWidths[$nr]->size	= $colWidths[$nr]->max + 2;
			else
				$colWidths[$nr]->size	= '*';
		}

		$width	= $this->consoleWidth - 4;
		$stars	= 0;
		foreach( $colWidths as $nr => $col ){
			if( $col->size === '*' )
				$stars++;
			else
				$width	-= $col->size;
		}
		foreach( $colWidths as $nr => $col ){
			if( $col->size === '*' ){
				if( $width > $stars * 10 ){
					$col->size	= floor( ( $width - $stars * 2 ) / $stars );
				} else {
					$col->size	= $col->head + 2;
				}
			}
		}
		return $colWidths;
	}

	protected function cleanData( array $data ): array
	{
		foreach( $data as $nrRow => $row ){
			foreach( $row as $nrCol => $value ){
				$value	= preg_replace( '/\r?\n/', ' ', trim( $value ) );
				$value	= preg_replace( '/\t/', ' ', $value );
				$value	= preg_replace( '/<!--.+-->/u', '', $value );
//				$value	= $this->trimCentric( $value, $this->consoleWidth );
				$data[$nrRow][$nrCol]	= trim( $value );
			}
		}
		return $data;
	}

	protected function extend( string $text, int $toLength ): string
	{
		if( !function_exists( 'mb_strlen' ) )
			return str_pad( $text, $toLength, ' ' );
		$textLength		= $this->strlen( $text );
		if( !$toLength || $toLength <= $textLength )
			return $text;
		$repeat	= (int) ceil( max( 0, $textLength - 1 ) + $toLength );
		return mb_substr( $text.str_repeat( ' ', $repeat ), 0, $toLength, $this->encoding );
	}

	protected function fit( string $text, int $toLength ): string
	{
		return $this->extend( $this->trimCentric( trim( $text ), $toLength ), $toLength );
	}

	protected function line( string $sign = '-', int $lineLength = 76 ): string
	{
		$steps	= (int) floor( $lineLength / $this->strlen( $sign ) );
		return str_repeat( $sign, $steps );
	}

	protected function trimCentric( string $string, int $length = 0 ): string
	{
		$string	= trim( $string );
		if( $length === 0 || $this->strlen( $string ) <= $length )
			return $string;
		$range	= ( $length - 1 ) / 2;
		$length	= $this->strlen( $string ) - (int) floor( $range );
		$left	= $this->substr( $string, 0, (int) ceil( $range ) );
		$right	= $this->substr( $string, (int) -floor( $range ), $length );
		return $left.'…'.$right;
	}

	protected function strlen( string $string ): int
	{
		if( !function_exists( 'mb_strlen' ) )
			return strlen( utf8_decode( $string ) );
		return mb_strlen( $string, $this->encoding );
	}

	protected function substr( string $string, int $start, ?int $length = NULL ): string
	{
		if( !function_exists( 'mb_substr' ) )
			return utf8_encode( substr( utf8_decode( $string ), $start, $length ) );
		return mb_substr( $string, $start, $length, $this->encoding );
	}
}
