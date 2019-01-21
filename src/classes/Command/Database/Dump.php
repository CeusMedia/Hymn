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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Database_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $prefixPlaceholder		= '<%?prefix%>';

	/**
	 *	Execute this command.
	 *	Implements flags: database-no
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return $this->client->out( "Database can NOT be connected." );

		$dbc		= $this->client->getDatabase();
		$arguments	= $this->client->arguments;
		$pathConfig	= $this->client->getConfigPath();

		$fileName	= $arguments->getArgument( 0 );
		if( !preg_match( "/[a-z0-9]/i", $fileName ) )												//  arguments has not valid value
			$fileName	= $pathConfig.'sql/';														//  set default path
		if( substr( $fileName, -1 ) == "/" )														//  given argument is a path
			$fileName	= $fileName."dump_".date( "Y-m-d_H:i:s" ).".sql";							//  generate stamped file name
		if( dirname( $fileName) )																	//  path is not existing
			exec( "mkdir -p ".dirname( $fileName ) );												//  create path

		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );
		if( $this->prefix	= $arguments->getOption( 'prefix' ) )
			$this->prefixPlaceholder	= $arguments->getOption( 'prefix' );

		$tables		= '';																			//  no table selection by default
		if( $prefix ){																				//  prefix has been set
			$tables		= array();																	//  prepare list of tables matching prefix
			foreach( $dbc->query( "SHOW TABLES LIKE '".$prefix."%'" ) as $table )					//  iterate found tables with prefix
				$tables[]	= escapeshellarg( $table[0] );											//  collect table as escaped shell arg
			$tables	= join( ' ', $tables );															//  reduce tables list to tables arg
		}

		$host		= $this->client->getDatabaseConfiguration( 'host' );							//  get host name from config
		$port		= $this->client->getDatabaseConfiguration( 'port' );							//  get port from config
		$username	= $this->client->getDatabaseConfiguration( 'username' );						//  get username from config
		$password	= $this->client->getDatabaseConfiguration( 'password' );						//  get password from config
		$name		= $this->client->getDatabaseConfiguration( 'name' );							//  get database name from config
		$command	= vsprintf( "mysqldump %s %s %s", array(										//  @see https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html#option_mysqldump_compact
			join( ' ', array(
				'--host='.escapeshellarg( $host ),													//  configured host name as escaped shell arg
				'--port='.escapeshellarg( $port ),													//  configured port as escaped shell arg
				'--user='.escapeshellarg( $username ),												//  configured username as escaped shell arg
				'--password='.escapeshellarg( $password ),											//  configured password as escaped shell arg
				'--result-file='.escapeshellarg( $fileName ),
			) ),
			escapeshellarg( $name ),																//  configured database name as escaped shell arg
			$tables,																				//  collected found tables
		) );

		$this->client->outVerbose( "DB Server:    ".$host."@".$port );
		$this->client->outVerbose( "Database:     ".$name );
		$this->client->outVerbose( "Table Prefix: ".( $prefix ? $prefix : "- (none)" ) );
		$this->client->outVerbose( "Access as:    ".$username );

		$resultCode		= 0;
		$resultOutput	= array();
		exec( $command, $resultOutput, $resultCode );
		if( $resultCode !== 0 )
			return $this->client->out( "Database dump failed." );
		if( $this->flags->dry ){
			unlink( $fileName );
			return $this->client->out( "Simulated database dump has been successful." );
		}
		$this->insertPrefixInFile( $fileName, $prefix );
		return $this->client->out( "Database dumped to ".$fileName );
	}

	protected function insertPrefixInFile( $fileName, $prefix ){
		$regExp		= "@(EXISTS|FROM|INTO|TABLE|TABLES|for table)( `)(".$prefix.")(.+)(`)@U";		//  build regular expression
		$callback	= array( $this, '_callbackReplacePrefix' );										//  create replace callback

		rename( $fileName, $fileName."_" );															//  move dump file to source file
		$fpIn		= fopen( $fileName."_", "r" );													//  open source file
		$fpOut		= fopen( $fileName, "a" );														//  prepare empty target file
		while( !feof( $fpIn ) ){																	//  read input file until end
			$line	= fgets( $fpIn );																//  read line buffer
			$line	= preg_replace_callback( $regExp, $callback, $line );							//  perform replace in buffer
//			$buffer	= fread( $fpIn, 4096 );															//  read 4K buffer
//			$buffer	= preg_replace_callback( $regExp, $callback, $buffer );							//  perform replace in buffer
			fwrite( $fpOut, $line );																//  write buffer to target file
		}
		fclose( $fpOut );																			//  close target file
		fclose( $fpIn );																			//  close source file
		unlink( $fileName."_" );																	//  remove source file
	}

	protected function _callbackReplacePrefix( $matches ){
		if( $matches[1] === 'for table' )
			return $matches[1].$matches[2].$matches[4].$matches[5];
		return $matches[1].$matches[2].$this->prefixPlaceholder.$matches[4].$matches[5];
	}
}
