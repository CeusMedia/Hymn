<?php
class Hymn_Arguments{

	protected $arguments	= array();

	protected $options	= array();

	public function __construct( $arguments = NULL, $options = array() ){
		if( !is_array( $options ) )
			throw new InvalidArgumentException( 'Options must be given as array' );
		$this->registerOptions( $options );
//		if( $arguments )
		$this->arguments	= $arguments;
		$this->parse( $arguments );
	}

	public function getArgument( $nr = 0 ){
		if( isset( $this->arguments[$nr] ) )
			return $this->arguments[$nr];
		return NULL;
	}

	public function getArguments(){
		return $this->arguments;
	}

	public function getOption( $key ){
		if( isset( $this->options[$key] ) )
			return $this->options[$key]['value'];
		return NULL;
	}

	public function getOptions(){
		$options	= array();
		foreach( $this->options as $key => $option ){
			$options[$key]	= $option['value'];
		}
		return $options;
	}

	public function parse( $arguments = NULL ){
		$arguments	= is_null( $arguments ) ? $this->arguments : $arguments;
		$list	= array();
		foreach( $arguments as $nr => $argument ){
			foreach( $this->options as $key => $option ){
				if( preg_match( $option['pattern'], $argument ) ){
					$this->options[$key]['value']	= $option['resolve'];
					if( is_string( $option['resolve'] ) )
						$this->options[$key]['value']	= preg_replace( $option['pattern'], $option['resolve'], $argument );
					unset( $arguments[$nr] );
				}
			}
		}
		$this->arguments	= array_values( $arguments );
	}

	public function registerOption( $key, $pattern, $resolve, $default = NULL ){
		$this->options[$key]	= array(
			'pattern'	=> $pattern,
			'resolve'	=> $resolve,
			'default'	=> $default,
			'value'		=> $default,
		);
	}

	public function registerOptions( $options ){
		foreach( $options as $key => $rules ){
			if( !isset( $rules['pattern']  ) )
				throw new RangeException( 'Option "'.$key.'" is missing rule "pattern"' );
			if( !isset( $rules['resolve']  ) )
				throw new RangeException( 'Option "'.$key.'" is missing rule "resolve"' );
			if( !isset( $rules['default']  ) )
				$rules['default']	= NULL;
			$this->registerOption( $key, $rules['pattern'], $rules['resolve'], $rules['default'] );
		}
	}

	public function removeArgument( $nr ){
		if( isset( $this->arguments[$nr] ) ){
			unset( $this->arguments[$nr] );
			$this->arguments	= array_values( $this->arguments );
		}
	}

	public function unregisterOption( $key ){
		if( !isset( $this->options[$key] ) )
			throw new RangeException( 'Option "'.$key.'" is not registered' );
		unset( $this->options[$key] );
	}

	public function unregisterOptions( $key ){
		foreach( $keys as $key )
			$this->unregisterOption( $key );
	}
}
