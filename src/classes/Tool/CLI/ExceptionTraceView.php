<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_CLI_ExceptionTraceView
{
	protected ?Exception $exception = NULL;

	/**
	 *	Static constructor.
	 *	@param		Exception|NULL		$e
	 *	@return		self
	 */
	public static function getInstance( ?Exception $e ): self
	{
		return new self( $e );
	}

	/**
	 *	Constructor.
	 *	@param		Exception|NULL		$e
	 *	@return		void
	 */
	public  function __construct( ?Exception $e )
	{
		if( NULL !== $e )
			$this->setException( $e );
	}

	/**
	 *	Returns rendered trace as multiline string.
	 *	@return		string
	 *	@throws		RuntimeException		if no exception has been set
	 */
	public function render(): string
	{
		if( NULL === $this->exception )
			throw new RuntimeException( 'No exception set' );

		$lines	= [];
		$count	= 0;
		$steps	= $this->exception->getTrace();
		array_pop( $steps );
		array_pop( $steps );
		foreach( $steps as $step ){
			$count++;
			$lines[]	= '#'.$count;
			if( isset( $step['file'] ) && isset( $step['line'] ) ){
				$step['file']	.= ':'.$step['line'];
				unset( $step['line'] );
			}
			if( isset( $step['type'] ) && in_array( $step['type'], ['->', '::'], TRUE ) ){
				$step['call']	= $step['class'].$step['type'].$step['function'];
				unset( $step['type'], $step['function'], $step['class'] );
			}
			foreach( $step as $key => $value ){
				if( $key === 'file' && str_starts_with( $value, 'phar:' ) )
					$value	= preg_replace( '@^(phar:).+(hymn.phar/)@', '\\2', $value );
				if( is_array( $value ) )
					$value	= json_encode( $value );
				$lines[]	= ' - '.$key.': '.$value;
			}
		}
		return join( PHP_EOL, $lines );
	}

	public function setException( Exception $e ): self
	{
		$this->exception	= $e;
		return $this;
	}
}