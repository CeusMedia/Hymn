<?php
class Hymn_Tool_ConfigValue{

	const COMPARED_UNDONE			= 0;
	const COMPARED_EQUAL			= 1;
	const COMPARED_UNEQUAL			= 2;
	const COMPARED_UNSET_BOTH		= 4;
	const COMPARED_UNSET_SELF		= 8;
	const COMPARED_UNSET_OTHER		= 16;
	const COMPARED_EMPTY_BOTH		= 32;
	const COMPARED_EMPTY_SELF		= 64;
	const COMPARED_EMPTY_OTHER		= 128;
	const COMPARED_MISMATCH_TYPE	= 256;
	const COMPARED_MISMATCH_LENGTH	= 512;

	protected $value;
	protected $type;

	public function __construct( $value = NULL, $type = NULL ){
		$this->type( $type === NULL ? gettype( $value ) : $type );
		$this->set( $value );
	}

	public function applyIfSet( Hymn_Tool_ConfigValue $value ){
		if( $value->is() )
			$this->set( $value->get() );
		return $this;
	}

	public function compareTo( Hymn_Tool_ConfigValue $value, $typeSafe = TRUE ){
		if( !$this->is() || !$value->is() ){
			if( !$this->is() && !$value->is() )
				return static::COMPARED_UNSET_BOTH;
			if( !$this->is() )
				return static::COMPARED_UNSET_SELF;
			if( !$value->is() )
				return static::COMPARED_UNSET_OTHER;
		}
		if( !$this->hasValue() || !$value->hasValue() ){
			if( !$this->hasValue() && !$value->is() )
				return static::COMPARED_EMPTY_BOTH;
			if( !$this->hasValue() )
				return static::COMPARED_EMPTY_SELF;
			if( !$value->hasValue() )
				return static::COMPARED_EMPTY_OTHER;
		}
		if( $typeSafe ){
			if( $this->get() === $value->get() )
				return static::COMPARED_EQUAL;
			return static::COMPARED_UNEQUAL;
		}
		if( $this->type() !== $value->type() )
			return static::COMPARED_MISMATCH_TYPE;
		if( strlen( $this->get() ) !== strlen( $value->get() ) )
			return static::COMPARED_MISMATCH_LENGTH;
		if( $this->get() == $value->get() )
			return static::COMPARED_EQUAL;
		return static::COMPARED_UNEQUAL;
	}

	public function differsFromIfBothSet( Hymn_Tool_ConfigValue $value, $typeSafe = TRUE ){
		if( !$this->is() || !$value->is() )
 			return FALSE;
		if( $typeSafe )
			return $this->get() !== $value->get();
		return $this->get( TRUE ) !== $value->get( TRUE );
	}

	public function equalsToIfBothSet( Hymn_Tool_ConfigValue $value, $typeSafe = TRUE ){
		return !$this->differsFromIfBothSet( $value, $typeSafe );
	}

	public function get( $asTrimmedString = FALSE ){
		if( $asTrimmedString ){
			if( in_array( $this->type, array( 'bool', 'boolean' ) ) )
				return $this->value ? 'yes' : 'no';
			return trim( $this->value );
		}
		return $this->value;
	}

	public function hasValue(){
		return $this->value !== NULL && strlen( trim( $this->value ) ) > 0;
	}

	public function is( $hasValue = FALSE ){
		if( $hasValue )
			return strlen( trim( $this->value ) ) > 0;
		return $this->value !== NULL;
	}

	public function set( $value, $type = NULL ){
		$this->value	= $value;
		$this->type( $type === NULL ? $this->type : $type );
		return $this;
	}

	public function type( $type = NULL ){
		if( $type === NULL )
			return $this->type;
		$type	= strtolower( $type );
		$types	= array( 'bool', 'boolean', 'int', 'integer', 'double', 'float', 'string', 'null' );
		if( !in_array( $type, $types ) )
			throw new DomainException( 'Invalid config value type: '.$type );
		$this->type	= $type;
		$shortmap	= array( 'boolean' => 'bool', 'integer' => 'int' );
		if( in_array( $type, $shortmap ) )
			$type	= $shortmap[$type];
		return $this;
	}
}
?>
