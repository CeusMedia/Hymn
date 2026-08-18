<?php
class Hymn_Tool_SemVer_Constraint_Parser
{
	public static function parse( string $constraint ): Hymn_Tool_SemVer_Constraint
	{
		$object = new Hymn_Tool_SemVer_Constraint();
		$constraint	= (string) preg_replace( '/\s*\|\|?\s*/', '||', $constraint );
		$constraint	= (string) preg_replace( '/\s*&&\s*/', '&&', $constraint );
		$constraint	= (string) preg_replace( '/\s+/', '&&', $constraint );
		if( str_contains( $constraint, '||' ) )
			foreach( explode( '||', $constraint ) as $subConstraint )
				$object->ors[] = new Hymn_Tool_SemVer_Constraint( $subConstraint );
		else if( str_contains( $constraint, '&&' ) )
			foreach( explode( '&&', $constraint ) as $subConstraint )
				$object->ands[] = new Hymn_Tool_SemVer_Constraint( $subConstraint );
		else
			$object->constraint	= $constraint;
		return $object;
	}
}
