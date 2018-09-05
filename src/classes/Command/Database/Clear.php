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
class Hymn_Command_Database_Clear extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return $this->client->out( "Database can NOT be connected." );

		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );

		$dbc	= $this->client->getDatabase();
		$result	= $dbc->query( "SHOW TABLES" . ( $prefix ? " LIKE '".$prefix."%'" : "" ) );
		$tables	= $result->fetchAll();
		if( !$tables ){
			if( !$this->flags->quiet )
				$this->client->out( "Database is empty" );
			return;
		}
		if( !( $this->flags->force ) ){
			if( $this->flags->quiet )
				return $this->client->out( "Quiet mode needs force mode (-f|--force)" );
			$this->client->out( "Database tables:" );
			foreach( $tables as $table )
				$this->client->out( "- ".$table[0] );
			$answer	= $this->client->getInput( "Do you really want to drop these tables?", 'boolean', 'no' );
			if( $answer !== TRUE )
				return;
		}

		foreach( $tables as $table ){
			if( !$this->flags->quiet && $this->flags->verbose )
				$this->client->out( "- Drop table '".$table[0]."'" );
			$dbc->query( "DROP TABLE ".$table[0] );
		}
		if( !$this->flags->quiet )
			$this->client->out( "Database cleared" );
	}

	static public function getTables( $client ){
		return array();
	}
}
