<?php
declare(strict_types=1);

class Hymn_Tool_SemVer_Version_Renderer
{
	public static function render( Hymn_Tool_SemVer_Version $version ): string
	{
		$string	= vsprintf( '%d.%d.%d', [
			$version->getMajor(),
			$version->getMinor(),
			$version->getPatch()
		] );
		if( '' !== $version->getPreRelease() )
			$string	.= '-'.$version->getPreRelease();
		if( '' !== $version->getBuild() )
			$string	.= '+'.$version->getBuild();
		return $string;
	}
}
