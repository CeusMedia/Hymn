<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_LatestFile
{
	protected $acceptedFileNames	= [];
	protected $fileNamePattern;
	protected $path;
	protected $client;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function find( ?string $path = NULL, ?string $fileNamePattern = NULL, ?array $acceptedFileNames = [] ): ?string
	{
		$path		= $path ? $path : $this->path;
		$pattern	= $fileNamePattern ? $fileNamePattern : $this->fileNamePattern;
		$accepted	= $acceptedFileNames ? $acceptedFileNames : $this->acceptedFileNames;
		if( !$path )
			throw new RuntimeException( 'No path set or given' );
		if( !$pattern && !$accepted )
			throw new RuntimeException( 'Neither file pattern nor accepted files set or given' );
		$list	= [];
		$index	= new DirectoryIterator( $path );
		foreach( $index as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			if( $accepted && in_array( $entry->getFilename(), $accepted ) )
				return $path.$entry->getFilename();
			if( !$pattern || !preg_match( $pattern, $entry->getFilename() ) )
				continue;
			$key		= str_replace( ['_', '-'], '_', $entry->getFilename() );
			$list[$key]	= $entry->getFilename();
		}
		krsort( $list );
		if( $list )
			return $path.array_shift( $list );
		return NULL;
	}

	public function setAcceptedFileNames( array $fileNames = [] ): self
	{
		$this->acceptedFileNames	= $fileNames;
		return $this;
	}

	public function setFileNamePattern( $fileNamePattern ): self
	{
		$this->fileNamePattern	= $fileNamePattern;
		return $this;
	}

	public function setPath( $path ): self
	{
		$this->path		= $path;
		return $this;
	}
}
