<?php
class Hymn_Tool_Database_Source
{
	public string $driver;
	public ?string $host			= NULL;
	public ?int $port					= NULL;
	public ?string $name			= NULL;
	public ?string $prefix			= NULL;
	public ?string $username	= NULL;
	public ?string $password	= NULL;
	public array $modules	= [];

	public function __construct( string $driver )
	{
		$this->driver	= $driver;
	}

	public function setAccess( ?string $username = NULL, string $password = NULL ): self
	{
		$this->username = $username;
		$this->password = $password;
		return $this;
	}

	public function setDatabase( string $name, ?string $prefix = NULL ): self
	{
		$this->name = $name;
		$this->prefix = $prefix;
		return $this;
	}

	public function setResource( string $host, ?int $port = NULL ): self
	{
		$this->host = $host;
		$this->port = $port;
		return $this;
	}

	public static function fromArray( array $data ): self
	{
		$instance = new self( $data['driver'] );
		$instance->setResource( $data['host'], $data['port'] );
		$instance->setAccess( $data['username'], $data['password'] );
		$instance->setDatabase( $data['name'], $data['prefix'] );
		return $instance;
	}
}
