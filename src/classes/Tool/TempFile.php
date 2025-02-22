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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_TempFile
{
	protected string $prefix;

	protected ?string $filePath		= NULL;

	public static function getInstance( string $prefix = '' ): self
	{
		$object	= new self( $prefix );
		return $object->create();
	}

	public function __construct( string $prefix = '' )
	{
		$this->prefix	= $prefix;
	}

	public function __destruct()
	{
		$this->destroy();
	}

	public function create(): self
	{
		$filePath	= tempnam( sys_get_temp_dir(), $this->prefix );
		if( FALSE === $filePath )
			throw new RuntimeException( 'Create a temporary file failed' );
		$this->filePath = $filePath;
		return $this;
	}

	public function destroy(): bool
	{
		if( NULL !== $this->filePath ){
			unlink( $this->getFilePath() );
			$this->filePath	= NULL;
			return TRUE;
		}
		return FALSE;
	}

	public function getFilePath(): string
	{
		if( NULL === $this->filePath )
			throw new RuntimeException( 'No temp file created, yet' );
		return $this->filePath;
	}

	public function setPrefix( string $prefix ): self
	{
		$this->prefix	= $prefix;
		return $this;
	}
}
