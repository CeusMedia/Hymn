<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 *	@todo    		implement option includes and excludes (using inference and recursion)
 */
class Hymn_Tool_CLI_Arguments
{
	protected $arguments	= [];

	protected $options	= [];

	public function __construct( $arguments = NULL, array $options = [] )
	{
		if( !is_array( $options ) )
			throw new InvalidArgumentException( 'Options must be given as array' );
		$this->registerOptions( $options );
//		if( $arguments )
		$this->arguments	= $arguments;
		$this->parse( $arguments );
	}

	public function getArgument( int $index = 0 )
	{
		if( isset( $this->arguments[$index] ) )
			return $this->arguments[$index];
		return NULL;
	}

	public function getArguments(): array
	{
		return $this->arguments;
	}

	public function getOption( string $key, ?string $default = NULL )
	{
		if( isset( $this->options[$key] ) )
			return $this->options[$key]['value'];
		return $default;
	}

	public function getOptions(): array
	{
		$options	= [];
		foreach( $this->options as $key => $option ){
			$options[$key]	= $option['value'];
		}
		return $options;
	}

	public function hasOption( string $key, bool $hasValue = FALSE ): bool
	{
		if( !isset( $this->options[$key] ) )
			return FALSE;
		if( $hasValue && !strlen( $this->options[$key]['value'] ) )
			return FALSE;
		return TRUE;
	}

	public function parse( $arguments = NULL )
	{
		$arguments	= is_null( $arguments ) ? $this->arguments : $arguments;
		foreach( $arguments as $nr => $argument ){
			foreach( $this->options as $key => $option ){
				if( preg_match( $option['pattern'], $argument ) ){
					$this->options[$key]['value']	= $option['resolve'];
					if( is_string( $option['resolve'] ) ){
						$value		= preg_replace( $option['pattern'], $option['resolve'], $argument );
						$hasValues	= isset( $this->options[$key]['values'] );
						if( $hasValues && count( $this->options[$key]['values'] ) ){
							if( !in_array( $value, $this->options[$key]['values'] ) ){
								throw new RangeException( sprintf(
									'Invalid value "%s" for option "%s" (must be one of [%s])',
									$value,
									$key,
									join( '|', $this->options[$key]['values'] )
								) );
							}
						}
						$this->options[$key]['value']	= $value;
					}
					unset( $arguments[$nr] );
				}
			}
		}
		$this->arguments	= array_values( $arguments );
/* todo implement */
//		$this->validateOptionClusions();
	}

//	protected function validateOptionClusions(){
//	}

	/** @todo change behavior of values (string) while includes and excludes are array, already */
	public function registerOption( string $key, $pattern, $resolve, $default = NULL, $values = NULL, array $includes = [], array $excludes = [] )
	{
		$this->options[$key]	= [
			'pattern'	=> $pattern,
			'resolve'	=> $resolve,
			'default'	=> $default,
			'values'	=> $values,
			'value'		=> $default,
			'includes'	=> $includes,
			'excludes'	=> $excludes,
		];
	}

	/** @todo change behavior of values (string) while includes and excludes are array, already */
	public function registerOptions( array $options )
	{
		foreach( $options as $key => $rules ){
			if( !isset( $rules['pattern']  ) )
				throw new RangeException( 'Option "'.$key.'" is missing rule "pattern"' );
			if( !isset( $rules['resolve']  ) )
				throw new RangeException( 'Option "'.$key.'" is missing rule "resolve"' );
			$this->registerOption(
				$key,
				$rules['pattern'],
				$rules['resolve'],
				$rules['default'],
				$this->getEnumerationFromArrayKeyIfSet( $rules, 'values' ),
				$this->getEnumerationFromArrayKeyIfSet( $rules, 'includes' ),
				$this->getEnumerationFromArrayKeyIfSet( $rules, 'excludes' )
			);
		}
	}

	public function removeArgument( int $nr )
	{
		if( isset( $this->arguments[$nr] ) ){
			unset( $this->arguments[$nr] );
			$this->arguments	= array_values( $this->arguments );
		}
	}

	public function setArgument( int $nr = 0, $value )
	{
		$this->arguments[$nr]	= $value;
	}

	public function unregisterOption( $key )
	{
		if( !isset( $this->options[$key] ) )
			throw new RangeException( 'Option "'.$key.'" is not registered' );
		unset( $this->options[$key] );
	}

	public function unregisterOptions( array $keys )
	{
		foreach( $keys as $key )
			$this->unregisterOption( $key );
	}

	protected function getEnumerationFromArrayKeyIfSet( array $array, $key ): array
	{
		$list	= [];
		if( array_key_exists( $key, $array ) && !is_null( $array[$key] ) ){
			$list	= $array[$key];
			if( !is_array( $list ) )
				$list	= preg_split( '/\s*,\s*/', $list );
		}
		return $list;
	}
}
