<?php
class Hymn_Structure_Config_Source
{
	public bool $active;
	public string $title	= '';
	public string $type		= 'folder';
	public string $path		= '';
	public bool $isDefault		= FALSE;

	public function __construct()
	{
	}

	public static function fromArray( array $settings ): self
	{
		$instance	= new self();
		foreach( $settings as $key => $value )
			$instance->$key	= $value;
		return $instance;
	}
}