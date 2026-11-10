<?php
declare(strict_types=1);

/**
 *	Parses a single version constraint (no AND/OR combinations) into a range.
 *	Semantics follow Composer:
 *	- exact:	1.2.3, =1.2.3, ==1.2.3
 *	- compare:	>1.2, >=1.2, <2.0, <=2.0, !=1.5, <>1.5
 *	- caret:	^1.2.3 → >=1.2.3 <2.0.0, ^0.3 → >=0.3.0 <0.4.0, ^0.0.3 → >=0.0.3 <0.0.4
 *	- tilde:	~1.2 → >=1.2.0 <2.0.0, ~1.2.3 → >=1.2.3 <1.3.0
 *	- wildcard:	*, 1.*, 1.2.* (also x/X)
 *	- hyphen:	1.0 - 2.0 → >=1.0.0 <2.1.0, 1.0.0 - 2.1.0 → >=1.0.0 <=2.1.0
 *	Exclusive upper bounds without pre-release (like <2.0.0) exclude pre-releases
 *	of the bound version as well (2.0.0-beta does not satisfy ^1.0).
 */
class Hymn_Tool_SemVer_Constraint_Range_Parser
{
	/**
	 *	@param		string		$constraint
	 *	@return		Hymn_Tool_SemVer_Constraint_Range
	 *	@throws		InvalidArgumentException	if constraint is not supported
	 */
	public static function parse( string $constraint ): Hymn_Tool_SemVer_Constraint_Range
	{
		$constraint	= trim( $constraint );
		$range		= new Hymn_Tool_SemVer_Constraint_Range();
		$matches	= [];

		if( '' === $constraint || in_array( $constraint, ['*', 'x', 'X'], TRUE ) )			//  any version
			return $range;

		$numericVersion	= 'v?\d+(?:\.\d+){0,2}';
		if( 1 === preg_match( '/^(\S+)\s+-\s+(\S+)$/', $constraint, $matches )			//  hyphen range, Composer style
			|| 1 === preg_match( '/^('.$numericVersion.')-('.$numericVersion.')$/', $constraint, $matches ) ){	//  hyphen range without spaces (legacy)
			$range->setAtLeast( self::createVersion( $matches[1], $constraint ) );
			$upper	= self::createVersion( $matches[2], $constraint );
			$parts	= self::countParts( $matches[2] );
			if( 3 === $parts || '' !== $upper->getPreRelease() )
				$range->setAtMost( $upper );
			else
				$range->setLowerThan( self::createUpperBound( $upper, $parts - 1 ) );
			return $range;
		}

		if( 1 === preg_match( '/^v?(\d+)(?:\.(\d+))?\.[*xX]$/', $constraint, $matches ) ){	//  wildcard, like 1.* or 1.2.*
			$hasMinor	= '' !== ( $matches[2] ?? '' );
			$lower		= new Hymn_Tool_SemVer_Version();
			$lower->setMajor( (int) $matches[1] );
			$lower->setMinor( (int) ( $matches[2] ?? 0 ) );
			$range->setAtLeast( $lower );
			$range->setLowerThan( self::createUpperBound( $lower, $hasMinor ? 1 : 0 ) );
			return $range;
		}

		if( 1 !== preg_match( '/^(>=|<=|<>|!=|==|>|<|=|\^|~)?\s*(.+)$/', $constraint, $matches ) )
			throw new InvalidArgumentException( 'Unsupported version constraint: '.$constraint );
		$operator		= $matches[1];
		$versionString	= $matches[2];
		$version		= self::createVersion( $versionString, $constraint );
		$parts			= self::countParts( $versionString );

		switch( $operator ){
			case '':
			case '=':
			case '==':
				$range->setAtLeast( $version );
				$range->setAtMost( $version );
				break;
			case '!=':
			case '<>':
				$range->setNotEqualTo( $version );
				break;
			case '>=':
				$range->setAtLeast( $version );
				break;
			case '<=':
				$range->setAtMost( $version );
				break;
			case '>':
				$range->setGreaterThan( $version );
				break;
			case '<':
				$bound	= clone $version;
				if( '' === $bound->getPreRelease() )
					$bound->setPreRelease( '0' );												//  exclude pre-releases of bound
				$range->setLowerThan( $bound );
				break;
			case '^':
				$values	= array_slice( [$version->getMajor(), $version->getMinor(), $version->getPatch()], 0, $parts );
				$index	= $parts - 1;															//  all given parts are zero: bump last given part
				foreach( $values as $nr => $value ){
					if( $value > 0 ){															//  bump first non-zero part
						$index	= $nr;
						break;
					}
				}
				$range->setAtLeast( $version );
				$range->setLowerThan( self::createUpperBound( $version, $index ) );
				break;
			case '~':
				$range->setAtLeast( $version );
				$range->setLowerThan( self::createUpperBound( $version, max( 0, $parts - 2 ) ) );
				break;
		}
		return $range;
	}

	/**
	 *	Returns number of version parts given (1 for "1", 2 for "1.2", 3 for "1.2.3-beta").
	 */
	protected static function countParts( string $versionString ): int
	{
		$numbers	= (string) preg_replace( '/[-+].*$/', '', ltrim( trim( $versionString ), 'vV' ) );
		return substr_count( $numbers, '.' ) + 1;
	}

	/**
	 *	Returns exclusive upper bound by incrementing major (0), minor (1) or patch (2).
	 *	Pre-release is set to lowest possible value to exclude pre-releases of upper bound.
	 */
	protected static function createUpperBound( Hymn_Tool_SemVer_Version $version, int $index ): Hymn_Tool_SemVer_Version
	{
		$bound	= clone $version;
		match( $index ){
			0		=> $bound->incrementMajor(),
			1		=> $bound->incrementMinor(),
			default	=> $bound->incrementPatch(),
		};
		return $bound->setPreRelease( '0' );
	}

	protected static function createVersion( string $versionString, string $constraint ): Hymn_Tool_SemVer_Version
	{
		if( !Hymn_Tool_SemVer_Version_Parser::isValid( $versionString ) )
			throw new InvalidArgumentException( 'Unsupported version constraint: '.$constraint );
		return Hymn_Tool_SemVer_Version_Parser::parse( $versionString );
	}
}
