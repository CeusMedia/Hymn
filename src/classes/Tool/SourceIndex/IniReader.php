<?php
declare(strict_types=1);

/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021-2025 Ceus Media
 */

/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021-2025 Ceus Media
 */
class Hymn_Tool_SourceIndex_IniReader
{
	/**	@var	array		$data */
	protected array $data;

	/**	@var	string		$fileName	Name of settings file */
	protected string $fileName	= '.index.ini';

	protected array $empty	= [
		'id'				=> NULL,
		'title'				=> NULL,
		'url'				=> NULL,
		'description'		=> NULL,
	];

	/**
	 *	@access		public
	 *	@param		string		$pathSource		Path to module source root
	 *	@return		void
	 */
	public function __construct( string $pathSource )
	{
		$this->data	= $this->empty;
		if( file_exists( $pathSource.$this->fileName ) ){
			$data	= parse_ini_file( $pathSource.$this->fileName );
			if( FALSE !== $data )
				$this->data	= array_merge( $this->data, $data );
		}
	}

	public function get( string $key ): int|float|bool|string|NULL
	{
		if( isset( $this->data[$key] ) )
			return $this->data[$key];
		return NULL;
	}

	public function getAll(): array
	{
		return $this->data;
	}
}
