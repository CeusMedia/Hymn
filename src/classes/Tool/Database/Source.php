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
 *	@package		CeusMedia.Hymn.Tool.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_Database_Source
{
	public string $driver;
	public ?string $host		= NULL;
	public ?int $port			= NULL;
	public ?string $name		= NULL;
	public ?string $prefix		= NULL;
	public ?string $username	= NULL;
	public ?string $password	= NULL;
	public array $modules		= [];

	public static function fromArray( array $data ): self
	{
		$instance = new self( $data['driver'] );
		$instance->setResource( $data['host'], $data['port'] );
		$instance->setAccess( $data['username'], $data['password'] );
		$instance->setDatabase( $data['name'], $data['prefix'] );
		return $instance;
	}

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
}
