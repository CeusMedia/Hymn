<?php
/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021 Ceus Media
 */

/**
 *	@author		Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright	2021 Ceus Media
 */
class Hymn_Tool_SourceIndex_JsonRenderer
{
	/**	@var	array		$modules */
	protected array $modules	= [];

	/**	@var	Hymn_Tool_SourceIndex_IniReader|NULL	$settings */
	protected ?Hymn_Tool_SourceIndex_IniReader $settings;

	/**	@var	boolean		$printPretty */
	protected bool $printPretty	= FALSE;

	/**
	 *	@access		public
	 *	@return		string
	 *	@throws		RuntimeException		if not settings are set
	 */
	public function render(): string
	{
		if( $this->settings === NULL )
			throw new RuntimeException( 'No settings set' );

		$data	= [
			'id'			=> $this->settings->get( 'id' ),
			'title'			=> $this->settings->get( 'title' ),
//			'version'		=> $this->settings->get( 'version' ),
			'url'			=> $this->settings->get( 'url' ),
			'description'	=> $this->settings->get( 'description' ),
			'date'			=> date( 'Y-m-d' ),
			'modules'		=> $this->modules,
		];
		$options	= 0;
		if( $this->printPretty )
			$options	|= JSON_PRETTY_PRINT;
		return (string) json_encode( $data, $options );
	}

	/**
	 *	@access		public
	 *	@param		boolean		$printPretty		Flag: use pretty print on JSON encode
	 *	@return		self
	 */
	public function setPrettyPrint( bool $printPretty ): self
	{
		$this->printPretty	= $printPretty;
		return $this;
	}

	/**
	 *	@access		public
	 *	@param		array		$modules		...
	 *	@return		self
	 */
	public function setModules( array $modules ): self
	{
		$this->modules	= $modules;
		return $this;
	}

	/**
	 *	@access		public
	 *	@param		Hymn_Tool_SourceIndex_IniReader	$settings		...
	 *	@return		self
	 */
	public function setSettings( Hymn_Tool_SourceIndex_IniReader $settings ): self
	{
		$this->settings	= $settings;
		return $this;
	}
}
