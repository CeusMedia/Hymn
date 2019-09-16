<?php
class Hymn_Module_Library_Installed{

	protected $client;

	public function __construct( Hymn_Client $client ){
		$this->client		= $client;
	}

	public function get( $moduleId ){
		$pathModules	= $this->client->getConfigPath().'modules/';
		$filename		= $pathModules.$moduleId.'.xml';
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'Module "'.$moduleId.'" not installed in '.$pathModules );
		$reader			= new Hymn_Module_Reader();
		return $reader->load( $filename, $moduleId );
	}

	public function getAll( $shelfId = NULL ){
//		if( self::$useCache && self::$listModulesInstalled !== NULL )			//  @todo realize shelves in cache
//			return self::$listModulesInstalled;									//  @todo realize shelves in cache
		$list			= array();
		$pathModules	= $this->client->getConfigPath().'modules/';
		if( file_exists( $pathModules ) ){
			$iterator	= new RecursiveDirectoryIterator( realpath( $pathModules ) );
			$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
			foreach( $index as $entry ){
				if( !$entry->isFile() || !preg_match( "/\.xml$/", $entry->getFilename() ) )
					continue;
				$key	= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
				$module	= $this->get( $key );
				if( !$shelfId || $module->installSource === $shelfId )
					$list[$key]	= $module;
			}
		}
		ksort( $list );
//		self::$listModulesInstalled	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	public function has( $moduleId, $shelfId = NULL ){
		$list	= $this->getAll( $shelfId );
		return array_key_exists( $moduleId, $list );
	}

}
