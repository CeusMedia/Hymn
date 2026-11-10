<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo				code documentation
 */
class Hymn_Tool_CLI_Question
{
	protected const ANSWERS_YES		= ['y', 'yes', 'j', 'ja', '1', 'true'];
	protected const ANSWERS_NO		= ['n', 'no', 'nein', '0', 'false'];

	protected Hymn_Client $client;
	protected string $message;
	protected string $type			= 'string';
	protected ?string $default		= NULL;
	protected ?array $options		= NULL;
	protected bool $break			= TRUE;
	protected bool $required		= FALSE;

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
			$default	= in_array( strtolower( $this->default ?? '' ), self::ANSWERS_YES, TRUE ) ? 'y' : 'n';
		}
		if( /*!$typeIsBoolean && */strlen( trim( $default ?? '' ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$this->break )
			$message	.= ": ";
		if( $this->client->flags & Hymn_Client::FLAG_NO_INTERACTION )							//  questions are disabled by --interactive=no
			return $this->convert( $this->answerWithoutInteraction( $default, $options ), $typeIsBoolean, $typeIsInteger, $typeIsNumber );

		$handle	= defined( 'STDIN' ) ? STDIN : fopen( 'php://stdin', 'r' );						//  shared handle: keeps buffered input for next questions
		do{
			$this->client->out( $message, $this->break );
			$line	= $handle ? fgets( $handle ) : FALSE;
			$isEnd	= FALSE === $line;															//  end of input reached, eg. in pipe or cron job
			$input	= trim( (string) $line );
			if( !strlen( $input ) && $default )
				$input	= $default;
			if( $typeIsBoolean )																	//  accept yes/no in several notations
				$input	= match( TRUE ){
					in_array( strtolower( $input ), self::ANSWERS_YES, TRUE )	=> 'y',
					in_array( strtolower( $input ), self::ANSWERS_NO, TRUE )	=> 'n',
					default														=> $input,
				};
			$isValid	= !is_array( $options ) || 0 === count( $options ) || in_array( $input, $options );
			if( $this->required && '' === $input )													//  answer is required but empty
				$isValid	= FALSE;
			if( !$isValid && $isEnd )																//  no more input to ask for
				throw new RuntimeException( 'No valid answer for question "'.$this->message.'": end of input' );
		}
		while( !$isValid );
		return $this->convert( $input, $typeIsBoolean, $typeIsInteger, $typeIsNumber );
	}

	/**
	 *	Sets whether an empty answer is refused.
	 *	Without input (end of input or --interactive=no), an exception is thrown instead of asking again.
	 *	@param		bool		$required
	 *	@return		self
	 */
	public function setRequired( bool $required = TRUE ): self
	{
		$this->required	= $required;
		return $this;
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

	//  --  PROTECTED  --  //

	/**
	 *	Returns default answer, if interaction is disabled.
	 *	@param		string|NULL		$default
	 *	@param		array|NULL		$options
	 *	@return		string
	 *	@throws		RuntimeException		if no valid default is available
	 */
	protected function answerWithoutInteraction( ?string $default, ?array $options ): string
	{
		$answer		= $default ?? '';
		$isValid	= !is_array( $options ) || 0 === count( $options ) || in_array( $answer, $options, TRUE );
		if( $isValid && !( $this->required && '' === $answer ) )
			return $answer;
		throw new RuntimeException( 'Answer for question "'.$this->message.'" needed, but interaction is disabled' );
	}

	protected function convert( string $input, bool $typeIsBoolean, bool $typeIsInteger, bool $typeIsNumber ): float|bool|int|string
	{
		if( $typeIsBoolean )
			return 'y' === $input;
		if( $typeIsInteger )
			return (int) $input;
		if( $typeIsNumber )
			return (float) $input;
		return $input;
	}
}
