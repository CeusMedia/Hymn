<?php
declare(strict_types=1);

/**
 *	Renders a range as constraint string, which can be parsed again into an equal range.
 */
class Hymn_Tool_SemVer_Constraint_Range_Renderer
{
	public static function render( Hymn_Tool_SemVer_Constraint_Range $range ): string
	{
		$atLeast	= $range->getAtLeast();
		$atMost		= $range->getAtMost();
		if( NULL !== $atLeast && NULL !== $atMost && $atLeast->isEqualTo( $atMost ) )
			return $atLeast->render();

		$parts	= [];
		if( NULL !== $atLeast )
			$parts[]	= '>='.$atLeast->render();
		if( NULL !== $range->getGreaterThan() )
			$parts[]	= '>'.$range->getGreaterThan()->render();
		if( NULL !== $atMost )
			$parts[]	= '<='.$atMost->render();
		if( NULL !== $range->getLowerThan() ){
			$bound	= clone $range->getLowerThan();
			if( '0' === $bound->getPreRelease() )											//  implicit on parsing "<x.y.z"
				$bound->setPreRelease( '' );
			$parts[]	= '<'.$bound->render();
		}
		if( NULL !== $range->getNotEqualTo() )
			$parts[]	= '!='.$range->getNotEqualTo()->render();
		return [] === $parts ? '*' : implode( ' ', $parts );
	}
}
