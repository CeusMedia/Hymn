<?php
class Hymn_Tool_Cli_Question{

	protected $client;
	protected $message;
	protected $type			= 'string';
	protected $default		= NULL;
	protected $options		= array();
	protected $break		= TRUE;

	public function __construct( Hymn_Client $client, $message, $type = 'string', $default = NULL, $options = array(), $break = TRUE ){
		$this->client	= $client;
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
