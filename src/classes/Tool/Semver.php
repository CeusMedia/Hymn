<?php
/**
 *	...
 *
 *	Copyright (c) 2021-2025 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_Semver
{
	protected ?string $actualVersion		= NULL;
	protected ?string $semanticVersion		= NULL;
	protected string $defaultOperator		= '=';

	public function __construct()
	{
	}

	public function check(): bool
	{
		if( NULL === $this->actualVersion )
			throw new RuntimeException( 'No actual version set' );
		if( NULL === $this->semanticVersion )
			throw new RuntimeException( 'No semantic version set' );
		return static::staticCheck(
			$this->actualVersion,
			$this->semanticVersion,
			$this->defaultOperator
		);
	}

	public function setActualVersion( string $version ): self
	{
		$this->actualVersion	= $version;
		return $this;
	}

	public function setDefaultOperator( string $operator ): self
	{
		$validOperators	= ['^', '>', '<', '>=', '<=', '<>', '!=', '==', '='];
		if( !in_array( $operator, $validOperators, TRUE ) )
			throw new InvalidArgumentException( 'Unsupported operator: '.$operator );
		$this->defaultOperator	= $operator;
		return $this;
	}

	public function setSemanticVersion( string $version ): self
	{
		$this->semanticVersion	= $version;
		return $this;
	}

	public static function staticCheck( string $actualVersion, string $semanticVersion, string $defaultOperator = '==' ): bool
	{
		$operator		= $defaultOperator;
		$version		= $semanticVersion;
		$patternPrefix	= '/^(\^|>|<|>=|<=|<>|!=|==|=)(.+)$/';
		$matches	= [];
		preg_match( $patternPrefix, $semanticVersion, $matches );
		if( 0 !== count( $matches ) ){
			$operator	= $matches[1];
			$version	= $matches[2];
		}
		if( $operator === '^' )
			$operator	= '>=';
		return version_compare( $actualVersion, $version, $operator );
	}
}
