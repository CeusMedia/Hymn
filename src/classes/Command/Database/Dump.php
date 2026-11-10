<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
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
			$this->outError( 'Database can NOT be connected: '.Hymn_Command_Database_Test::$lastError, Hymn_Client::EXIT_ON_SETUP );

		$dbc			= $this->client->getDatabase();
		$arguments		= $this->client->arguments;

		$fileName		= self::resolveFileName(
			$arguments->getArgument( 0 ),
			$arguments->getOption( 'path' ),
			$this->client->getConfigPath().'sql/'
		);
		$folder			= dirname( $fileName );
		if( !is_dir( $folder ) && !mkdir( $folder, 0775, TRUE ) && !is_dir( $folder ) )			//  create folder, if missing
			$this->outError( 'Creating folder '.$folder.' failed', Hymn_Client::EXIT_ON_EXEC );

		$mysql	= new Hymn_Tool_Database_CLI_MySQL( $this->client );								//  get CLI handler for MySQL
		$prefix	= (string) $dbc->getConfigValue( 'prefix' );										//  tables with configured prefix are dumped
		$prefixInDump	= $arguments->getOption( 'prefix' );										//  replacement of prefix in dump, default: placeholder
		if( NULL !== $prefixInDump )
			$mysql->setPrefixPlaceholder( trim( (string) $prefixInDump ) );

		$tablesToSkip	= $this->getTablesToSkip();

		if( $this->flags->verbose ){
			$dba	= $dbc->getConfig();
			$this->out( [
				'Export file:  '.$fileName,															//  show export file name
				'DB Server:    '.$dba->host.'@'.$dba->port,											//  show server host and port from config
				'Database:     '.$dba->name,														//  show database name from config
				'Table prefix: '.( $prefix ?: '(none)' ),											//  show table prefix from config
				'In dump as:   '.( $prefixInDump ?? '<%?prefix%>' ),									//  show replacement of table prefix in dump
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

	/**
	 *	Returns file name of dump: given file, file in given folder or file in path option or default path.
	 *	Folders end with a slash or exist; generated file names contain date and time.
	 *	@param		string|NULL		$argument		File or folder given as argument
	 *	@param		string|NULL		$pathOption		Folder given by option --path
	 *	@param		string			$defaultPath	Default folder, eg. config/sql/
	 *	@return		string
	 */
	public static function resolveFileName( ?string $argument, ?string $pathOption, string $defaultPath ): string
	{
		$fileName	= trim( $argument ?? '' );
		if( 1 !== preg_match( '/[a-z0-9]/i', $fileName ) )											//  no valid file or folder given
			$fileName	= '' !== trim( $pathOption ?? '' ) ? trim( $pathOption ?? '' ) : $defaultPath;
		if( is_dir( $fileName ) && !str_ends_with( $fileName, '/' ) )								//  existing folder without slash
			$fileName	.= '/';
		if( str_ends_with( $fileName, '/' ) )														//  folder: generate stamped file name
			$fileName	.= 'dump_'.date( 'Y-m-d_H:i:s' ).'.sql';
		return $fileName;
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
