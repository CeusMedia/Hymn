<?php
declare(strict_types=1);

class Hymn_Tool_SemVer_Version_Parser
{
	/** @var	string */
	protected static string $regExp	= '/^v?(\d+)(?:\.(\d+))?(?:\.(\d+))?(?:-([0-9A-Za-z.-]+))?(?:\+([0-9A-Za-z.-]+))?$/';

	/**
	 *	@param		string		$versionString
	 *	@return		Hymn_Tool_SemVer_Version
	 *	@throws		InvalidArgumentException	if given string is not a valid version
	 */
	public static function parse( string $versionString ): Hymn_Tool_SemVer_Version
	{
		$matches	= [];
		if( 1 !== preg_match( static::$regExp, trim( $versionString ), $matches ) )
			throw new InvalidArgumentException( 'Given string is not a valid SemVer version: '.$versionString );

		$version	= new Hymn_Tool_SemVer_Version();
		$version->setMajor( (int) $matches[1] );
		$version->setMinor( (int) ( $matches[2] ?? 0 ) );
		$version->setPatch( (int) ( $matches[3] ?? 0 ) );
		$version->setPreRelease( $matches[4] ?? '' );
		$version->setBuild( $matches[5] ?? '' );
		return $version;
	}

	public static function isValid( string $versionString ): bool
	{
		return 1 === preg_match( static::$regExp, trim( $versionString ) );
	}
}
