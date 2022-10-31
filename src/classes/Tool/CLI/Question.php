<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_CLI_Question
{
	protected Hymn_Client $client;
	protected $message;
	protected $type			= 'string';
	protected $default		= NULL;
	protected $options		= [];
	protected $break		= TRUE;

	public function __construct( Hymn_Client $client, string $message, string $type = 'string', ?string $default = NULL, ?array $options = [], bool $break = TRUE )
	{
		$this->client	= $client;
		$this->message	= $message;
		$this->setType( $type );
		$this->setDefault( $default );
		$this->setOptions( $options ?? [] );
		$this->setBreak( $break );
	}

	public function ask()
	{
		$typeIsBoolean	= in_array( $this->type, array( 'bool', 'boolean' ) );
		$typeIsInteger	= in_array( $this->type, array( 'int', 'integer' ) );
		$typeIsNumber	= in_array( $this->type, array( 'float', 'double', 'decimal' ) );
		$default		= $this->default;
		$message		= $this->message;
		$options		= $this->options;
		if( $typeIsBoolean ){
			$options	= array( 'y', 'n' );
			$default	= 'no';
			if( in_array( strtolower( $this->default ), array( 'y', 'yes', '1' ) ) )
				$default	= 'yes';
		}
		if( /*!$typeIsBoolean && */strlen( trim( $default ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$this->break )
			$message	.= ": ";
		do{
			$this->client->out( $message, $this->break );
			$handle	= fopen( "php://stdin","r" );
			$input	= trim( fgets( $handle ) );
			if( !strlen( $input ) && $default )
				$input	= $default;
		}
		while( $options && is_null( $default ) && !in_array( $input, $options ) );
		if( $typeIsBoolean )
			$input	= in_array( strtolower( $input ), array( 'y', 'yes', '1' ) );
		if( $typeIsInteger )
			$input	= (int) $input;
		if( $typeIsNumber )
			$input	= (float) $input;
		return $input;
	}

	public function setBreak( bool $break = TRUE ): self
	{
		$this->break	= $break;
		return $this;
	}

	public function setDefault( ?string $default = NULL ): self
	{
		$this->default	= $default;
		return $this;
	}

	public function setOptions( ?array $options = [] ): self
	{
		$this->options	= $options ?? [];
		return $this;
	}

	public function setType( string $type ): self
	{
		$this->type		= $type;
		return $this;
	}
}
