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
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo				code documentation
 */
class Hymn_Tool_CLI_Question
{
	protected Hymn_Client $client;
	protected string $message;
	protected string $type			= 'string';
	protected ?string $default		= NULL;
	protected ?array $options		= NULL;
	protected bool $break			= TRUE;

	/**
	 *	Static constructor.
	 *	@param		Hymn_Client		$client
	 *	@param		string			$message
	 *	@param		string			$type			Default: string
	 *	@param		?string			$default
	 *	@param		?array			$options
	 *	@param		bool			$break			Default: yes
	 *	@return		self
	 */
	public static function getInstance( Hymn_Client $client, string $message, string $type = 'string', ?string $default = NULL, ?array $options = [], bool $break = TRUE ): self
	{
		return new self( $client, $message, $type, $default, $options, $break );
	}

	/**
	 *	Constructor.
	 *	@param		Hymn_Client		$client
	 *	@param		string			$message
	 *	@param		string			$type			Default: string
	 *	@param		?string			$default
	 *	@param		?array			$options
	 *	@param		bool			$break			Default: yes
	 */
	public function __construct( Hymn_Client $client, string $message, string $type = 'string', ?string $default = NULL, ?array $options = [], bool $break = TRUE )
	{
		$this->client	= $client;
		$this->message	= $message;
		$this->setType( $type );
		$this->setDefault( $default );
		$this->setOptions( $options );
		$this->setBreak( $break );
	}

	public function ask(): float|bool|int|string
  {
		$typeIsBoolean	= in_array( $this->type, ['bool', 'boolean'] );
		$typeIsInteger	= in_array( $this->type, ['int', 'integer'] );
		$typeIsNumber	= in_array( $this->type, ['float', 'double', 'decimal'] );
		$default		= $this->default;
		$message		= $this->message;
		$options		= $this->options;
		if( $typeIsBoolean ){
			$options	= ['y', 'n'];
			$default	= 'no';
			if( in_array( strtolower( $this->default ?? '' ), ['y', 'yes', '1'] ) )
				$default	= 'yes';
		}
		if( /*!$typeIsBoolean && */strlen( trim( $default ?? '' ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$this->break )
			$message	.= ": ";
		do{
			$this->client->out( $message, $this->break );
			/** @var resource $handle */
			$handle	= fopen( "php://stdin","r" );
			$input	= trim( (string) fgets( $handle ) );
			if( !strlen( $input ) && $default )
				$input	= $default;
		}
		while( is_array( $options ) && 0 !== count( $options ) && !in_array( $input, $options ) );
		if( $typeIsBoolean )
			$input	= in_array( strtolower( $input ), ['y', 'yes', '1'] );
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
		$this->options	= $options;
		return $this;
	}

	public function setType( string $type ): self
	{
		$this->type		= $type;
		return $this;
	}
}
