<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 *	@todo			extract MySQL code to Hymn_Tool_Database_CLI_MySQL
 */
class Hymn_Command_Database_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected $argumentOptions	= array(
		'prefix'		=> array(
			'pattern'	=> '/^--prefix=(\S*)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		),
		'path'		=> array(
			'pattern'	=> '/^--path=(\S*)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		),
	);

	/**
	 *	Execute this command.
	 *	Implements flags: database-no
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		if( !Hymn_Command_Database_Test::test( $this->client ) )
			return $this->client->out( 'Database can NOT be connected.' );

		$dbc			= $this->client->getDatabase();
		$arguments		= $this->client->arguments;

		$path			= $arguments->getOption( 'path', $this->client->getConfigPath().'sql/' );	//  get path from option or default

		$fileName		= $arguments->getArgument( 0 );
		if( !preg_match( '/[a-z0-9]/i', $fileName ) )												//  arguments has not valid value
			$fileName	= $path;																	//  set path from option or default
		if( substr( $fileName, -1 ) == '/' )														//  given argument is a path
			$fileName	= $fileName.'dump_'.date( 'Y-m-d_H:i:s' ).'.sql';							//  generate stamped file name
		if( !dirname( $fileName) )																	//  path is not existing
			exec( 'mkdir -p '.dirname( $fileName ) );												//  create path

		$mysql		= new Hymn_Tool_Database_CLI_MySQL( $this->client );							//  get CLI handler for MySQL
		$prefix		= trim( $arguments->getOption( 'prefix', $dbc->getConfig( 'prefix' ) ) );
		if( strlen( $prefix ) )
			$mysql->setPrefixPlaceholder( $prefix );

		$tablesToSkip	= $this->getTablesToSkip();

		if( $this->flags->verbose ){
			$this->client->out( array(
				'Export file:  '.$fileName,															//  show export file name
				'DB Server:    '.$dbc->getConfig( 'host' ).'@'.$dbc->getConfig( 'port' ),			//  show server host and port from config
				'Database:     '.$dbc->getConfig( 'name' ),											//  show database name from config
				'Table prefix: '.( $prefix ? $prefix : '(none)' ),									//  show table prefix from config
				'Access as:    '.$dbc->getConfig( 'username' ),										//  show username from config
			) );
			if( $tablesToSkip )
				$this->client->out( 'Skip tables:  '.join( ', ', $tablesToSkip ) );
			$this->client->out( 'Dumping export file ...' );
		}

		$result	= $mysql->exportToFileWithPrefix( $fileName, $prefix, $tablesToSkip );
		if( $result->code !== 0 )
			return $this->client->out( 'Dumping export failed: '.join( PHP_EOL, $result->output ) );
		if( $this->flags->dry ){
			unlink( $fileName );
			return $this->client->out( '-> Dry Mode: Database dump has been successful. Export file deleted' );
		}
		else {
			$fileSize	= Hymn_Tool_FileSize::get( $fileName );										//  format file size
		}
		return $this->client->out( 'Dumped export file to '.$fileName.' ('.$fileSize.')' );
	}

	protected function getTablesToSkip(): array
	{
		$list		= [];
		$library	= $this->getLibrary();
		foreach( $library->listInstalledModules() as $module ){
			if( isset( $module->config['onDatabaseDumpSkipTables'] ) ){
				$tables = $module->config['onDatabaseDumpSkipTables']->value;
				foreach( preg_split( '/\s*,\s*/', $tables ) as $table )
					$list[]	= $table;
			}
		}
		return $list;
	}
}
