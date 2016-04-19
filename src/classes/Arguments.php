<?php
class Hymn_Arguments{

	protected $arguments	= array();

	protected $options	= array(
		'dry'		=> array(
			'pattern'	=> '/^-d|--dry/',
			'resolve'	=> TRUE,
			'value'		=> NULL,
		),
		'file'		=> array(
			'pattern'	=> '/^--file=(\S+)$/',
			'resolve'	=> '\\1',
			'value'		=> '.hymn',
		),
		'force'		=> array(
			'pattern'	=> '/^-f|--force$/',
			'resolve'	=> TRUE,
			'value'		=> NULL,
		),
		'quiet'		=> array(
			'pattern'	=> '/^-q|--quiet$/',
			'resolve'	=> TRUE,
			'value'		=> NULL,
		),
		'verbose'	=> array(
			'pattern'	=> '/^-v|--verbose$/',
			'resolve'	=> TRUE,
			'value'		=> NULL,
		)
	);

	public function __construct( $arguments ){
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

	protected function parse( $arguments ){
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

	public function removeArgument( $nr ){
		if( isset( $this->arguments[$nr] ) ){
			unset( $this->arguments[$nr] );
			$this->arguments	= array_values( $this->arguments );
		}
	}
}
