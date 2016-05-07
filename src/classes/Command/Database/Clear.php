<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Clear extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return Hymn_Client::out( "Database can NOT be connected." );

		$force		= $this->client->arguments->getOption( 'force' );
		$verbose	= $this->client->arguments->getOption( 'verbose' );
		$quiet		= $this->client->arguments->getOption( 'quiet' );
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );

		$dbc	= $this->client->getDatabase();
		$result	= $dbc->query( "SHOW TABLES" . ( $prefix ? " LIKE '".$prefix."%'" : "" ) );
		$tables	= $result->fetchAll();
		if( !$tables ){
			if( !$quiet )
				Hymn_Client::out( "Database is empty" );
			return;
		}

		if( !$force ){
			if( $quiet )
				return Hymn_Client::out( "Quiet mode needs force mode (-f|--force)" );
			Hymn_Client::out( "Database tables:" );
			foreach( $tables as $table )
				Hymn_Client::out( "- ".$table[0] );
			$answer	= Hymn_Client::getInput( "Do you really want to drop these tables?", NULL, array("y", "n" ) );
			if( $answer !== "y" )
				return;
		}

		foreach( $tables as $table ){
			if( !$quiet && $verbose )
				Hymn_Client::out( "- Drop table '".$table[0]."'" );
			$dbc->query( "DROP TABLE ".$table[0] );
		}
		if( !$quiet )
			Hymn_Client::out( "Database cleared" );
	}

	static public function getTables( $client ){
		return array();
	}
}
