<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $prefixPlaceholder		= '<%?prefix%>';

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$arguments	= $this->client->arguments;
		$fileName	= $arguments->getArgument( 0 );
		if( !preg_match( "/[a-z0-9]/i", $fileName ) )												//  arguments has not valid value
			$fileName	= 'config/sql/';															//  set default path
		if( substr( $fileName, -1 ) == "/" )														//  given argument is a path
			$fileName	= $fileName."dump_".date( "Y-m-d_H:i:s" ).".sql";							//  generate stamped file name
		if( dirname( $fileName) )																	//  path is not existing
			exec( "mkdir -p ".dirname( $fileName ) );												//  create path

		$dbc		= $this->client->getDatabase();
		$username	= $this->client->getDatabaseConfiguration( 'username' );
		$password	= $this->client->getDatabaseConfiguration( 'password' );
		$name		= $this->client->getDatabaseConfiguration( 'name' );
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );

		if( $this->prefix	= $arguments->hasOption( 'prefix' ) )
			$this->prefixPlaceholder	= $arguments->getOption( 'prefix' );

		$tables		= array();
		if( $prefix )
			foreach( $dbc->query( "SHOW TABLES LIKE '".$prefix."%'" ) as $table )
				$tables[]	= $table[0];
		$tables	= join( ' ', $tables );

		$command	= "mysqldump -u%s -p%s %s %s > %s";
		$command	= sprintf( $command, $username, $password, $name, $tables, $fileName );
		exec( $command );

		/*  --  REPLACE PREFIX  --  */
		$regExp		= "@(EXISTS|FROM|INTO|TABLE|TABLES|for table)( `)(".$prefix.")(.+)(`)@U";		//  build regular expression
		$callback	= array( $this, '_callbackReplacePrefix' );										//  create replace callback
		$contents	= explode( "\n", file_get_contents( $fileName ) );								//  read raw dump file
		foreach( $contents as $nr => $content )														//  iterate lines
			$contents[$nr] = preg_replace_callback( $regExp, $callback, $content );					//  replace prefix by placeholder
		file_put_contents( $fileName, implode( "\n", $contents ) );									//  save final dump file

		return Hymn_Client::out( "Database dumped to ".$fileName );
	}

	protected function _callbackReplacePrefix( $matches ){
		if( $matches[1] === 'for table' )
			return $matches[1].$matches[2].$matches[4].$matches[5];
		return $matches[1].$matches[2].$this->prefixPlaceholder.$matches[4].$matches[5];
	}

	static public function test( $client ){
		$config		= $client->getConfig();
		$client->setupDatabaseConnection();
		$dbc		= $client->getDatabase();
		if( $dbc ){
			$result	= $dbc->query( "SHOW TABLES" );
			if( is_object( $result ) && is_array( $result->fetchAll() ) )
				return TRUE;
		}
		return FALSE;
	}
}
