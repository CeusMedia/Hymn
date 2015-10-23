<?php
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
