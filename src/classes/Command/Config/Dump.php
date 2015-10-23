<?php
class Hymn_Command_Config_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$fileName	= Hymn_Client::$fileName;
//		Hymn_Client::out();
		if( !file_exists( "config/modules" ) )
			return Hymn_Client::out( "No modules installed" );

		$index	= new DirectoryIterator( "config/modules" );
		$list	= array();
		foreach( $index as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			if( !preg_match( "/\.xml$/", $entry->getFilename() ) )
				continue;
			$id		= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
			$module	= Hymn_Module_Reader::load( $entry->getPathname(), $id );
//			Hymn_Client::out( $id );
			if( $module->config ){
				$list[$id]	= array( 'config' => (object) array() );
				foreach( $module->config as $pair )
					$list[$id]['config']->{$pair->key}	= $pair->value;
			}
		}
		$json			= json_decode( file_get_contents( $fileName ) );
		$json->modules	= $list;
		file_put_contents( $fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
		return Hymn_Client::out( "Configuration dumped to ".$fileName );
	}
}
