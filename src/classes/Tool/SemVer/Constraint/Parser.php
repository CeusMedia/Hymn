<?php
declare(strict_types=1);

/**
 *	Parses a version constraint with OR (| or ||) and AND (space, comma or &&) combinations.
 *	AND binds stronger than OR, like in Composer: ">=1.0 <1.5 || ^2.0".
 */
class Hymn_Tool_SemVer_Constraint_Parser
{
	/**
	 *	@param		string		$constraint
	 *	@return		Hymn_Tool_SemVer_Constraint
	 *	@throws		InvalidArgumentException	if constraint or a part of it is not supported
	 */
	public static function parse( string $constraint ): Hymn_Tool_SemVer_Constraint
	{
		$object		= new Hymn_Tool_SemVer_Constraint();
		$constraint	= trim( $constraint );

		$orParts	= (array) preg_split( '/\s*\|\|?\s*/', $constraint );
		if( count( $orParts ) > 1 ){
			foreach( $orParts as $orPart )
				$object->ors[]	= new Hymn_Tool_SemVer_Constraint( self::requireNonEmpty( (string) $orPart, $constraint ) );
			return $object;
		}

		if( 1 === preg_match( '/^\S+\s+-\s+\S+$/', $constraint ) ){						//  hyphen range, like "1.0 - 2.0"
			Hymn_Tool_SemVer_Constraint_Range_Parser::parse( $constraint );					//  validate
			$object->constraint	= $constraint;
			return $object;
		}

		$normalized	= (string) preg_replace( '/(>=|<=|<>|!=|==|[<>=^~])\s+/', '$1', $constraint );	//  remove space after operators
		$andParts	= (array) preg_split( '/\s*(?:&&|,)\s*|\s+/', $normalized );
		if( count( $andParts ) > 1 ){
			foreach( $andParts as $andPart )
				$object->ands[]	= new Hymn_Tool_SemVer_Constraint( self::requireNonEmpty( (string) $andPart, $constraint ) );
			return $object;
		}

		Hymn_Tool_SemVer_Constraint_Range_Parser::parse( $normalized );					//  validate
		$object->constraint	= $normalized;
		return $object;
	}

	protected static function requireNonEmpty( string $part, string $constraint ): string
	{
		if( '' === trim( $part ) )
			throw new InvalidArgumentException( 'Invalid version constraint (empty part): '.$constraint );
		return $part;
	}
}
