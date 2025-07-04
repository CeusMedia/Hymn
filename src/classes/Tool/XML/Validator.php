<?php
/**
 *	Validates XML.
 *
 *	Copyright (c) 2007-2015 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool.XML
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Validates XML.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.XML
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			Unit Test
 */
class Hymn_Tool_XML_Validator
{
	/**	@var		array		$error		Array of Error Information */
	protected array $error	= [];

	/**
	 *	Returns last error line.
	 *	@access		public
	 *	@return		int
	 */
	public function getErrorLine(): int
	{
		if( $this->error )
			return $this->error['line'];
		return -1;
	}

	/**
	 *	Returns last error message.
	 *	@access		public
	 *	@return		string
	 */
	public function getErrorMessage(): string
	{
		if( $this->error )
			return $this->error['message'];
		return '';
	}

	/**
	 *	Validates a local XML file.
	 *	@access		public
	 *	@return		bool
	 */
	public function validateFile( string $fileName ): bool
	{
		if( !file_exists( $fileName ) )
			throw new InvalidArgumentException( "XML file '".$fileName."' is not existing" );
		$xml = file_get_contents( $fileName );
		return $this->validate( $xml );
	}

	/**
	 *	Validates a XML string.
	 *	@access		public
	 *	@param		string		$xml		XML string to validate
	 *	@return		bool
	 */
	public function validate( string $xml ): bool
	{
		$parser	= xml_parser_create();
		$dummy	= function(){};
		xml_set_element_handler( $parser, $dummy, $dummy );
		xml_set_character_data_handler( $parser, $dummy );
		if( !xml_parse( $parser, $xml ) ){
			$msg	= "%s at line %d";
			$error	= xml_error_string( xml_get_error_code( $parser ) );
			$line	= xml_get_current_line_number( $parser );
			$this->error['message']	= sprintf( $msg, $error, $line );
			$this->error['line']	= $line;
			$this->error['xml']		= $xml;
			xml_parser_free( $parser );
			return FALSE;
		}
		xml_parser_free( $parser );
		return TRUE;
	}
}
