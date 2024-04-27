<?php
/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021-2024 Ceus Media
 */

/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021-2024 Ceus Media
 */
class Hymn_Tool_SourceIndex_ModuleFilter
{
	const MODE_FULL		= 0;
	const MODE_MINIMAL	= 1;
	const MODE_REDUCED	= 2;

	const MODES			= [
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
//			$module->path = preg_replace( $regExp, '', $entry->getPath() );

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
