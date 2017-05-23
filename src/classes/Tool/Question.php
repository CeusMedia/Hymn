<?php
class Hymn_Tool_Question{

	protected $message;
	protected $type			= 'string';
	protected $default		= NULL;
	protected $options		= array();
	protected $break		= TRUE;


	public function __construct( $message, $type = 'string', $default = NULL, $options = array(), $break = TRUE ){
		$this->message	= $message;
		$this->setType( $type );
		$this->setDefault( $default );
		$this->setOptions( $options );
		$this->setBreak( $break );
	}

	public function ask(){
		$typeIsBoolean	= in_array( $this->type, array( 'bool', 'boolean' ) );
		$typeIsInteger	= in_array( $this->type, array( 'int', 'integer' ) );
		$typeIsNumber	= in_array( $this->type, array( 'float', 'double', 'decimal' ) );
		$default		= $this->default;
		$message		= $this->message;
		$options		= $this->options;
		if( $typeIsBoolean ){
			if( in_array( strtolower( $this->default ), array( 'y', 'yes', '1' ) ) ){
				$options	= array( 'y', 'n' );
				$default	= 'yes';
			}
			else {
				$options	= array( 'y', 'n' );
				$default	= 'no';
			}
		}
		if( /*!$typeIsBoolean && */strlen( trim( $default ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$this->break )
			$message	.= ": ";
		do{
			Hymn_Client::out( $message, $this->break );
			$handle	= fopen( "php://stdin","r" );
			$input	= trim( fgets( $handle ) );
			if( !strlen( $input ) && $default )
				$input	= $default;
		}
		while( $options && is_null( $default ) && !in_array( $input, $options ) );
		if( $typeIsBoolean )
			$input	= in_array( $input, array( 'y', 'yes', '1' ) ) ? TRUE : FALSE;
		if( $typeIsInteger )
			$input	= (int) $input;
		if( $typeIsNumber )
			$input	= (float) $input;
		return $input;
	}

	static public function askStatic( $message, $type = 'string', $default = NULL, $options = array(), $break = TRUE ){
		$input	= new self( $message, $type, $default, $options, $break );
		return $input->get();
	}

	public function setBreak( $break = TRUE ){
		$this->break	= $break;
	}

	public function setDefault( $default = NULL ){
		$this->default	= $default;
	}

	public function setOptions( $options = array() ){
		$this->options	= $options;
	}

	public function setType( $type ){
		$this->type		= $type;
	}
}
