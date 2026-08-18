<?php

class Hymn_Tool_SemVer_Version_Comparator
{
	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function differs( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'ne' );
	}

	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function equals( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'eq' );
	}

	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function isAtLeast( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'ge' );
	}

	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function isAtMost( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'le' );
	}
	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function isGreater( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'gt' );
	}

	/**
	 *	@param		string		$v1
	 *	@param		string		$v2
	 *	@return		bool
	 */
	public static function isLower( string $v1, string $v2 ): bool
	{
		return version_compare( $v1, $v2, 'lt' );
	}
}
