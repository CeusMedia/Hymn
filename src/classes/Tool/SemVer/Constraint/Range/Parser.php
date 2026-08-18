<?php
class Hymn_Tool_SemVer_Constraint_Range_Parser
{
	public static function parse( string $constraint ): Hymn_Tool_SemVer_Constraint_Range
	{
		$range		= new Hymn_Tool_SemVer_Constraint_Range();
		$matches	= [];
		if( preg_match( '/^(\D+)(\d.*)$/', $constraint, $matches ) !== 0 ){
			$operator	= $matches[1];
			$version	= $matches[2];
//			print( 'Operator: '.$operator.PHP_EOL );
//			print( 'Version:  '.$version.PHP_EOL );
			switch( $operator ){
				case '^':
					$level	= substr_count( $constraint, '.' ) + 1;
//					print( 'Range->parseConstraint: level -> '.$level.PHP_EOL );
					$range->setAtLeast( new Hymn_Tool_SemVer_Version( $version ) );
					$maxVersion	= new Hymn_Tool_SemVer_Version( $version );
					if( $level === 1 )
						$maxVersion->incrementMajor();
					else if( $level === 2 )
						$maxVersion->incrementMinor();
					else if( $level === 3 )
						$maxVersion->incrementPatch();
					$range->setLowerThan( $maxVersion );
					break;
				case '>=':
					$range->setAtLeast( new Hymn_Tool_SemVer_Version( $version ) );
					break;
				case '<=':
					$range->setAtMost( new Hymn_Tool_SemVer_Version( $version ) );
					break;
				case '>':
					$range->setGreaterThan( new Hymn_Tool_SemVer_Version( $version ) );
					break;
				case '<':
					$range->setLowerThan( new Hymn_Tool_SemVer_Version( $version ) );
					break;
			}
		}
		else if( preg_match( '/^(\d.*)-(\d.*)$/', $constraint, $matches ) !== 0 ){
			$range->setAtLeast( new Hymn_Tool_SemVer_Version( $matches[1] ) );
			$range->setAtMost( new Hymn_Tool_SemVer_Version( $matches[2] ) );
		}
		else{
			$range->setAtLeast( new Hymn_Tool_SemVer_Version( $constraint ) );
			$range->setAtMost( new Hymn_Tool_SemVer_Version( $constraint ) );
		}
		return $range;
	}
}
