<?php
declare(strict_types=1);


class Hymn_Structure_Source
{
	public string $id;
	public string $sourceId;
	public string $path;
	public string $type;
	public bool $active;
	public bool $isDefault		= FALSE;
	public string $title;
	public ?string $date		= NULL;
	public array $modules		= [];

	public static function fromArray( array $array ): self
	{
		$object	= new Hymn_Structure_Source();
		foreach( $array as $key => $value )
			$object->$key = $value;
		return $object;
	}
}