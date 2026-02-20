<?php
class Hymn_IntegrationTest_Context
{
	public const STATUS_UNKNOWN	= 0;
	public const STATUS_KNOWN	= 1;
	public const STATUS_COPIED	= 2;

	protected string $basePath	= '';
	protected string $absolutePath	= '';
	protected string $key			= '';
	protected string $templateKey	= '';
	protected int $status		= self::STATUS_UNKNOWN;

	public function __construct( string $absolutePath )
	{
		$this->absolutePath	= $absolutePath;
	}

	public function enter(): bool
	{
		return chdir( $this->absolutePath );
	}

	public function exists(): bool
	{
		return file_exists( $this->absolutePath );
	}

	public function getKey() : string
	{
		return $this->key;
	}

	public function getAbsolutePath() : string
	{
		return $this->absolutePath;
	}

	public function setStatus( int $status ): self
	{
		$this->status	= $status;
		return $this;
	}

	public function setKey( string $key ): self
	{
		$this->key	= $key;
		return $this;
	}

	public function setTemplateKey( string $templateKey ): self
	{
		$this->templateKey	= $templateKey;
		return $this;
	}
}