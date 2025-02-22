<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool.SourceIndex
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.SourceIndex
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_SourceIndex_ModuleFilter
{
	const int MODE_FULL		= 0;
	const int MODE_MINIMAL	= 1;
	const int MODE_REDUCED	= 2;

	const array MODES		= [
		self::MODE_FULL,
		self::MODE_MINIMAL,
		self::MODE_REDUCED,
	];

	/**	@var	integer		$mode			Index mode */
	protected int $mode		= self::MODE_REDUCED;

	/**	@var	string		$pathSource		Path to module source root */
	protected string $pathSource;

	/**
	 *	@access		public
	 *	@param		string		$pathSource		Path to module source root
	 *	@return		void
	 */
	public function __construct( string $pathSource )
	{
		$this->pathSource	= $pathSource;
	}

	/**
	 *	@access		public
	 *	@param		array<Hymn_Structure_Module>	$modules		...
	 *	@param		integer|NULL	$mode			Index mode to set, see constants MODES
	 *	@return		array
	 */
	public function filter( array $modules, ?int $mode = NULL ): array
	{
		$mode	= $mode ?? $this->mode;
		$list	= [];
//		$regExp	= '@^'.preg_quote( $this->pathSource, '@' ).'@';
		foreach( $modules as $module ){
//			/** @var string $modulePath */
//			$module->install->path = preg_replace( $regExp, '', $entry->getPath() );

			unset( $module->isInstalled );
			unset( $module->version->installed );
			unset( $module->version->available );
			unset( $module->file );
			unset( $module->uri );
			switch( $mode ){
				case self::MODE_MINIMAL:
					$module	= (object) [
						'title'			=> $module->title,
						'description'	=> $module->description,
						'version'		=> $module->version,
					];
					break;
				case self::MODE_REDUCED:
					unset( $module->config );
					unset( $module->files );
					unset( $module->hooks );
					unset( $module->links );
					unset( $module->install );
					unset( $module->sql );
					unset( $module->jobs );
					break;
			}
			$list[$module->id]	= $module;
		}
		ksort( $list );
		return $list;
	}

	/**
	 *	@access		public
	 *	@param		integer		$mode		Index mode to set, see constants MODES
	 *	@return		self
	 *	@throws		RangeException			if an invalid mode is given
	 */
	public function setMode( int $mode ): self
	{
		if( !in_array( $mode, self::MODES, TRUE ) )
			throw new RangeException( 'Invalid module index mode' );
		$this->mode	= $mode;
		return $this;
	}
}
