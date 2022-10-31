<?php
/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021 Ceus Media
 */

/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021 Ceus Media
 */
class Hymn_Tool_SourceIndex_IniReader
{
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
		if( file_exists( $pathSource.$this->fileName ) )
			$this->data	= array_merge( $this->data, parse_ini_file( $pathSource.$this->fileName ) );
	}

	public function get( $key )
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
