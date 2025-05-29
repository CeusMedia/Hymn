<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 *	@todo			extract MySQL code to Hymn_Tool_Database_CLI_MySQL
 */
class Hymn_Command_Database_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected array $argumentOptions	= [
		'prefix'		=> [
			'pattern'	=> '/^--prefix=(\S*)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		],
		'path'		=> [
			'pattern'	=> '/^--path=(\S*)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		],
	];

	/**
	 *	Execute this command.
	 *	Implements flags: database-no
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;

		if( !Hymn_Command_Database_Test::test( $this->client ) )
			$this->outError( 'Database can NOT be connected.', Hymn_Client::EXIT_ON_SETUP );

		$dbc			= $this->client->getDatabase();
		$arguments		= $this->client->arguments;

//		$path			= $arguments->getOption( 'path', $this->client->getConfigPath().'sql/' );	//  get path from option or default
		$defaultPath	= $this->client->getConfigPath().'sql/';
		$path			= $arguments->getOption( 'path' );										//  get path from option
		$path			= $path ?: $defaultPath;													//  ... or default

		$fileName		= (string) $arguments->getArgument( 0 );

		if( !preg_match( '/[a-z0-9]/i', $fileName ) )										//  arguments has not valid value
			$fileName	= $path;																	//  set path from option or default
		if( str_ends_with( $fileName, '/' ) )														//  given argument is a path
			$fileName	= $fileName.'dump_'.date( 'Y-m-d_H:i:s' ).'.sql';					//  generate stamped file name
		if( !dirname( $fileName) )																	//  path is not existing
			exec( 'mkdir -p '.dirname( $fileName ) );										//  create path

		$mysql	= new Hymn_Tool_Database_CLI_MySQL( $this->client );								//  get CLI handler for MySQL
		$prefix	= $arguments->getOption( 'prefix' ) ?? $dbc->getConfigValue( 'prefix' );
		if( '' !== trim( $prefix ) )
			$mysql->setPrefixPlaceholder( trim( $prefix ) );

		$tablesToSkip	= $this->getTablesToSkip();

		if( $this->flags->verbose ){
			$dba	= $dbc->getConfig();
			$this->out( [
				'Export file:  '.$fileName,															//  show export file name
				'DB Server:    '.$dba->host.'@'.$dba->port,											//  show server host and port from config
				'Database:     '.$dba->name,														//  show database name from config
				'Table prefix: '.( $prefix ?: '(none)' ),											//  show table prefix from config
				'Access as:    '.( $dba->username ?? '-' ),											//  show username from config
			] );
			if( $tablesToSkip )
				$this->out( 'Skip tables:  '.join( ', ', $tablesToSkip ) );
			$this->out( 'Dumping export file ...' );
		}

		$result	= $mysql->exportToFileWithPrefix( $fileName, $prefix, $tablesToSkip );
		if( $result->code !== 0 )
			$this->outError( 'Dumping export failed: '.join( PHP_EOL, $result->output ), Hymn_Client::EXIT_ON_EXEC );
		if( $this->flags->dry ){
			unlink( $fileName );
			$this->out( '-> Dry Mode: Database dump has been successful. Export file deleted' );
			return;
		}
		else {
			$fileSize	= Hymn_Tool_FileSize::get( $fileName );										//  format file size
		}
		$this->out( 'Dumped export file to '.$fileName.' ('.$fileSize.')' );
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
