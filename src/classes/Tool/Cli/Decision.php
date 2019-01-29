<?php
/**
 *	@deprecated		seems to be not used anymore
 *	@todo			check deprecation assumption and remove
 */
class Hymn_Tool_Cli_Decision{

	protected $client;
	protected $message;
	protected $default		= 'y';
	protected $options		= array(
		'y'	=> 'yes',
		'n'	=> 'no',
	);

	public function __construct( Hymn_Client $client, $message, $default = NULL, $options = array(), $break = TRUE ){
		$this->client	= $client;
		$this->message	= $message;
		if( $options )
			$this->setOptions( $options );
		if( $default !== NULL )
			$this->setDefault( $default );
		$this->setBreak( $break );
	}

	public function ask(){
		$options	= array();
		foreach( $this->options as $key => $value )
			$options[]	= $key.':'.$value;

		if( $this->default )
			$message	= $this->message." [".$this->default."]";
		$message	.= " (".implode( "|", $options ).")";
		if( !$this->break )
			$message	.= ": ";
		do{
			$this->client->out( $message, $this->break );
			$handle	= fopen( "php://stdin","r" );
			$input	= trim( fgets( $handle ) );
			if( !strlen( $input ) && $this->default )
				$input	= $this->default;
		}
		while( $options && !array_key_exists( $input, $this->options ) );
		return $input;
	}

	public function setBreak( $break = TRUE ){
		$this->break	= $break;
	}

	public function setDefault( $default = NULL ){
		if( !in_array( $default, array_keys( $this->options ) ) )
			throw new RangeException( 'Given default is not an option' );
		$this->default	= $default;
	}

	public function setOptions( $options = array() ){
		if( !count( $options ) )
			throw new InvalidArgumentException( 'No options provided' );
		$this->options	= $options;
	}

	public function setType( $type ){
		$this->type		= $type;
	}
}
?>
