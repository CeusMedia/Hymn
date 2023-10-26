<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2023 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Command_Database_Console extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$this->denyOnProductionMode();

		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;

		$dbc			= $this->client->getDatabase();
		$arguments		= $this->client->arguments;

		$query		= (string) $arguments->getArgument();
		if( preg_match( '/DROP/i', $query ) )
			$this->outError( 'DROP is not allowed', Hymn_Client::EXIT_ON_INPUT );
		if( preg_match( '/DELETE/i', $query ) )
			$this->outError( 'DELETE is not allowed', Hymn_Client::EXIT_ON_INPUT );

		if( !Hymn_Command_Database_Test::test( $this->client ) )
			$this->outError( 'Database can NOT be connected.', Hymn_Client::EXIT_ON_SETUP );

//		if( !$dbc->isConnected() )
//			$dbc->connect( TRUE );												//  force setup of new connection to database

		$result		= $dbc->query( $query );
		$data		= $result->fetchAll( PDO::FETCH_ASSOC );
		if( 0 === count( $data ) ) {
			$this->out( 'Empty result.' );
		} else {
			$keys	= array_keys( reset( $data ) );
			$table	= new Hymn_Tool_CLI_Table( $this->client );
			$table->detectWidth();
			print( $table->render( $data ).PHP_EOL );
		}
	}
}


