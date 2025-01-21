<?php
declare(strict_types=1);

class Hymn_Tool_ConfigValue
{
	public const int COMPARED_UNDONE			= 0;
	public const int COMPARED_EQUAL				= 1;
	public const int COMPARED_UNEQUAL			= 2;
	public const int COMPARED_UNSET_BOTH		= 4;
	public const int COMPARED_UNSET_SELF		= 8;
	public const int COMPARED_UNSET_OTHER		= 16;
	public const int COMPARED_EMPTY_BOTH		= 32;
	public const int COMPARED_EMPTY_SELF		= 64;
	public const int COMPARED_EMPTY_OTHER		= 128;
	public const int COMPARED_MISMATCH_TYPE		= 256;
	public const int COMPARED_MISMATCH_LENGTH	= 512;

	protected bool|int|float|string|NULL $value		= NULL;
	protected string $type			= 'string';

	/**
	 *	Constructor.
	 *	@param		bool|int|float|string|NULL	$value
	 *	@param		string|NULL					$type
	 */
	public function __construct( bool|int|float|string|NULL $value = NULL, ?string $type = NULL )
	{
		if( NULL !== $type )
			$this->setType( $type );
		if( NULL !== $value )
			$this->setValue( $value );
	}

	/**
	 *	@param		Hymn_Tool_ConfigValue		$value
	 *	@return		static
	 */
	public function applyIfSet( Hymn_Tool_ConfigValue $value ): static
	{
		if( $value->is() )
			$this->setValue( $value->getValue() );
		return $this;
	}

	/**
	 *	@param		Hymn_Tool_ConfigValue	$value
	 *	@param		bool					$typeSafe			Default: yes
	 *	@return		int
	 */
	public function compareTo( Hymn_Tool_ConfigValue $value, bool $typeSafe = TRUE ): int
	{
		if( !$this->is() || !$value->is() ){
			if( !$this->is() && !$value->is() )
				return static::COMPARED_UNSET_BOTH;
			if( !$this->is() )
				return static::COMPARED_UNSET_SELF;
			if( !$value->is() )
				return static::COMPARED_UNSET_OTHER;
		}
		if( !$this->hasValue() || !$value->hasValue() ){
			if( !$this->hasValue() && !$value->hasValue() )
				return static::COMPARED_EMPTY_BOTH;
			if( !$this->hasValue() )
				return static::COMPARED_EMPTY_SELF;
			if( !$value->hasValue() )
				return static::COMPARED_EMPTY_OTHER;
		}
		if( $typeSafe ){
			if( $this->getValue() === $value->getValue() )
				return static::COMPARED_EQUAL;
			return static::COMPARED_UNEQUAL;
		}
		if( $this->getType() !== $value->getType() )
			return static::COMPARED_MISMATCH_TYPE;
		if( strlen( (string) $this->getValue() ) !== strlen( (string) $value->getValue() ) )
			return static::COMPARED_MISMATCH_LENGTH;
		if( $this->getValue() == $value->getValue() )
			return static::COMPARED_EQUAL;
		return static::COMPARED_UNEQUAL;
	}

	/**
	 *	@param		Hymn_Tool_ConfigValue	$value
	 *	@param		bool					$typeSafe		Default : yes
	 *	@return		bool
	 */
	public function differsFromIfBothSet( Hymn_Tool_ConfigValue $value, bool $typeSafe = TRUE ): bool
	{
		if( !$this->is() || !$value->is() )
 			return FALSE;
		if( $typeSafe )
			return $this->getValue() !== $value->getValue();
		return $this->getValue( TRUE ) !== $value->getValue( TRUE );
	}

	/**
	 *	@param		Hymn_Tool_ConfigValue	$value
	 *	@param		bool					$typeSafe		Default: yes
	 *	@return		bool
	 */
	public function equalsToIfBothSet( Hymn_Tool_ConfigValue $value, bool $typeSafe = TRUE ): bool
	{
		return !$this->differsFromIfBothSet( $value, $typeSafe );
	}

	/**
	 *	@param		bool		$asTrimmedString		Default: no
	 *	@return		bool|float|int|string|NULL
	 */
	public function getValue( bool $asTrimmedString = FALSE ): bool|float|int|string|NULL
	{
		if( $asTrimmedString ){
			if( in_array( $this->type, ['bool', 'boolean'], TRUE ) )
				return $this->value ? 'yes' : 'no';
			return trim( strval( $this->value ) );
		}
		return $this->value;
	}

	/**
	 *	@return		string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 *	@return		bool
	 */
	public function hasValue(): bool
	{
		return $this->value !== NULL && 0 !== strlen( trim( strval( $this->value ) ) );
	}

	/**
	 *	@param		bool		$hasValue
	 *	@return		bool
	 */
	public function is( bool $hasValue = FALSE ): bool
	{
		if( $hasValue )
			return 0 !== strlen( trim( strval( $this->value ) ) );
		return NULL !== $this->value;
	}

	/**
	 *	@deprecated		use setValue and setType instead
	 */
	public function set( int|float|bool|string $value, string $type = NULL ): static
	{
		$this->setType( $type === NULL ? $this->type : $type );
		$value		= trim( (string) $value );
		if( in_array( strtolower( $this->type ), ['boolean', 'bool'] ) )						//  value is boolean
			$value	= !in_array( strtolower( $value ), ['no', 'false', '0', ''] );				//  value is not negative
		$this->value	= $value;
		return $this;
	}

	/**
	 *	@param		int|float|bool|string|NULL	$value
	 *	@return		static
	 */
	public function setValue( int|float|bool|string|NULL $value ): static
	{
		$value		= trim( (string) $value );
		if( 'bool' === $this->type )															//  value is boolean
			$value	= !in_array( strtolower( $value ), ['no', 'false', '0', ''] );				//  value is not negative
		else
			$value	= settype( $value, $this->type );
		$this->value	= $value;
		return $this;
	}

	/**
	 *	@param		string|NULL		$type
	 *	@return		static
	 */
	public function setType( ?string $type = NULL ): static
	{
		$types		= ['bool', 'int', 'double', 'float', 'string', 'null'];
		$shortmap	= ['boolean' => 'bool', 'integer' => 'int'];
		$type		= is_null( $type ) ? 'string' : $type;
		$type		= trim( strtolower( $type ) );
		$type		= strlen( $type ) > 0 ? $type : 'string';
		$type		= array_key_exists( $type, $shortmap ) ? $shortmap[$type] : $type;
		if( !in_array( $type, $types, TRUE ) )
			throw new DomainException( 'Invalid config value type: '.$type );
		$this->type	= $type;
		return $this;
	}
}
