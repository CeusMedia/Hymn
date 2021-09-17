<?php
class Hymn_Tool_TempFile
{
	protected $prefix;

	protected $filePath;

	public function __construct( string $prefix = '' )
	{
		$this->prefix	= $prefix;
	}

	public function create(): self
	{
		$this->filePath = tempnam( sys_get_temp_dir(), $this->prefix );
		return $this;
	}

	public function destroy(): bool
	{
		unlink( $this->getFilePath() );
		$this->filePath	= NULL;
		return TRUE;
	}

	public function getFilePath(): string
	{
		if( !$this->filePath )
			throw new RuntimeException( 'No temp file created, yet' );
		return $this->filePath;
	}

	public function setPrefix( string $prefix )
	{
		$this->prefix	= $prefix;
	}
}
