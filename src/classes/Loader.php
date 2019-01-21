<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Loader{

	protected $path			= 'phar://hymn.phar/';

	public function __construct(){
		$this->loadClassesFromFolder( '' );
	}

	//  method for recursive class loading
	protected function loadClassesFromFolder( $folder ){
		foreach( new DirectoryIterator( $this->path.$folder ) as $entry ){		//  iterate folder in path
			$nodeName	= $entry->getFilename();								//  shortcut filename
			if( !$entry->isDot() && $entry->isDir() ){							//  found a folder node
				if( preg_match( "/^[a-z]+$/i", $nodeName ) ){					//  is a valid nested folder
					$this->loadClassesFromFolder( $folder.$nodeName.'/' );		//  load classes in this folder
				}
			}
			else if( $entry->isFile() ){										//  found a file node
				if( preg_match( "/\.php$/", $nodeName ) ){						//  is a PHP file
					require_once $folder.$nodeName;								//  load classes in file
				}
			}
		}
	}
}
