<?php
declare(strict_types=1);

/**
 *	Compares versions by SemVer 2.0.0 precedence rules:
 *	major, minor and patch numerically, a pre-release is lower than the release,
 *	pre-release identifiers are compared one by one (numeric < alphanumeric),
 *	build metadata is ignored.
 */
class Hymn_Tool_SemVer_Version_Comparator
{
	/**
	 *	@param		Hymn_Tool_SemVer_Version|string		$v1
	 *	@param		Hymn_Tool_SemVer_Version|string		$v2
	 *	@return		int			-1, 0 or 1
	 */
	public static function compare( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): int
	{
		$v1	= is_string( $v1 ) ? Hymn_Tool_SemVer_Version_Parser::parse( $v1 ) : $v1;
		$v2	= is_string( $v2 ) ? Hymn_Tool_SemVer_Version_Parser::parse( $v2 ) : $v2;

		$result	= [$v1->getMajor(), $v1->getMinor(), $v1->getPatch()]
			<=> [$v2->getMajor(), $v2->getMinor(), $v2->getPatch()];
		if( 0 !== $result )
			return $result;
		return self::comparePreReleases( $v1->getPreRelease(), $v2->getPreRelease() );
	}

	public static function differs( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 !== self::compare( $v1, $v2 );
	}

	public static function equals( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 === self::compare( $v1, $v2 );
	}

	public static function isAtLeast( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 <= self::compare( $v1, $v2 );
	}

	public static function isAtMost( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 >= self::compare( $v1, $v2 );
	}

	public static function isGreater( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 < self::compare( $v1, $v2 );
	}

	public static function isLower( Hymn_Tool_SemVer_Version|string $v1, Hymn_Tool_SemVer_Version|string $v2 ): bool
	{
		return 0 > self::compare( $v1, $v2 );
	}

	protected static function comparePreReleases( string $p1, string $p2 ): int
	{
		if( $p1 === $p2 )
			return 0;
		if( '' === $p1 )																		//  release is greater than any pre-release
			return 1;
		if( '' === $p2 )
			return -1;
		$parts1	= explode( '.', $p1 );
		$parts2	= explode( '.', $p2 );
		$count	= min( count( $parts1 ), count( $parts2 ) );
		for( $i = 0; $i < $count; $i++ ){
			$a	= $parts1[$i];
			$b	= $parts2[$i];
			if( $a === $b )
				continue;
			$aIsNumeric	= 1 === preg_match( '/^\d+$/', $a );
			$bIsNumeric	= 1 === preg_match( '/^\d+$/', $b );
			if( $aIsNumeric && $bIsNumeric )
				return (int) $a <=> (int) $b;
			if( $aIsNumeric )																	//  numeric identifiers are lower than alphanumeric
				return -1;
			if( $bIsNumeric )
				return 1;
			return strcmp( $a, $b ) <=> 0;
		}
		return count( $parts1 ) <=> count( $parts2 );
	}
}
