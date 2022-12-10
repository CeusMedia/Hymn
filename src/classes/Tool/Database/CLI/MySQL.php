<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool.Database.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Database.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_Database_CLI_MySQL
{
	protected Hymn_Client $client;
	protected Hymn_Tool_Database_CLI_MySQL_OptionsFile $optionsFile;
	protected string $prefixPlaceholder		= '<%?prefix%>';
	protected bool $useTempOptionsFile		= TRUE;

	public function __construct( Hymn_Client $client )
	{
		$this->client		= $client;
		$this->optionsFile	= new Hymn_Tool_Database_CLI_MySQL_OptionsFile( $client );
	}

	public function exportToFile( string $fileName, string $tablePrefix = '', array $tablesToSkip = [] ): object
	{
		$dbc			= $this->client->getDatabase();
		$optionsFile	= NULL;
		$tables			= '';																		//  no table selection by default
		$usePrefix		= strlen( trim( $tablePrefix ) ) !== 0;
		$useSkip		= count( $tablesToSkip ) !== 0;
		if( $usePrefix || $useSkip )																//  table prefix has been set or there are tables to skip
			foreach( $dbc->getTables( $tablePrefix ) as $table )									//  iterate found tables with prefix
				if( !in_array( $table, $tablesToSkip, TRUE ) )										//  table shall not be skipped
					$tables	.= ' '.escapeshellarg( $table );										//  collect table as escaped shell arg
		if( $this->useTempOptionsFile ){
			$optionsFile	= new Hymn_Tool_Database_CLI_MySQL_OptionsFile( $this->client );
			$tempFile		= new Hymn_Tool_TempFile( $optionsFile->getDefaultFileName() );
			$tempFilePath	= $tempFile->create()->getFilePath();
			$optionsFile->create( $tempFilePath, FALSE );
			$line	= vsprintf( '%s %s %s', array(													//  @see https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html#option_mysqldump_compact
				join( ' ', array(
					'--defaults-extra-file='.escapeshellarg( $tempFilePath ),						//  configured host as escaped shell arg
					'--result-file='.escapeshellarg( $fileName ),									//  target file
					'--skip-extended-insert',														//  each row in one insert line
				) ),
				escapeshellarg( $dbc->getConfig( 'name' ) ),										//  configured database name as escaped shell arg
				$tables
			) );
		}
		else {
			$line	= vsprintf( '%s %s %s', array(													//  @see https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html#option_mysqldump_compact
				join( ' ', array(
					'--host='.escapeshellarg( $dbc->getConfig( 'host' ) ),							//  configured host as escaped shell arg
					'--port='.escapeshellarg( $dbc->getConfig( 'port' ) ),							//  configured port as escaped shell arg
					'--user='.escapeshellarg( $dbc->getConfig( 'username' ) ),						//  configured username as escaped shell arg
					'--password='.escapeshellarg( $dbc->getConfig( 'password' ) ),					//  configured pasword as escaped shell arg
					'--result-file='.escapeshellarg( $fileName ),
				) ),
				escapeshellarg( $dbc->getConfig( 'name' ) ),										//  configured database name as escaped shell arg
				$tables,																			//  collected found tables
			) );
		}
		$result	 = $this->execCommandLine( $line, 'mysqldump' );
		if( $this->useTempOptionsFile && $optionsFile )
			$optionsFile->remove();
		return $result;
	}

	public function exportToFileWithPrefix( string $fileName, ?string $prefix = NULL, array $tablesToSkip = [] ): object
	{
		$result	= $this->exportToFile( $fileName, $prefix, $tablesToSkip );
		$this->insertPrefixInFile( $fileName, $prefix );
		return $result;
	}

	public function importFile( string $fileName ): object
	{
		$optionsFile	= NULL;
		$dbc		= $this->client->getDatabase();
		$cores		= (int) shell_exec( 'cat /proc/cpuinfo | grep processor | wc -l' );				//  get number of CPU cores
		if( $this->useTempOptionsFile ){
			$optionsFile	= new Hymn_Tool_Database_CLI_MySQL_OptionsFile( $this->client );
			$tempFile		= new Hymn_Tool_TempFile( $optionsFile->getDefaultFileName() );
			$tempFilePath	= $tempFile->create()->getFilePath();
			$optionsFile->create( $tempFilePath, FALSE );
			$line		= vsprintf( '%s %s < %s', array(
				join( ' ', array(
					'--defaults-extra-file='.escapeshellarg( $tempFilePath ),							//  configured host as escaped shell arg
					'--force',																			//  continue if error eccoured
	//					'--use-threads='.( max( 1, $cores - 1 ) ),										//  how many threads to use (number of cores - 1)
	//					'--replace',																	//  replace if already existing
				) ),
				escapeshellarg( $dbc->getConfig( 'name' ) ),										//  configured database name as escaped shell arg
				escapeshellarg( $fileName ),
			) );
		}
		else {
			$line		= vsprintf( '%s %s < %s', array(
				join( ' ', array(
					'--host='.escapeshellarg( $dbc->getConfig( 'host' ) ),							//  configured host as escaped shell arg
					'--port='.escapeshellarg( $dbc->getConfig( 'port' ) ),							//  configured port as escaped shell arg
					'--user='.escapeshellarg( $dbc->getConfig( 'username' ) ),						//  configured username as escaped shell arg
					'--password='.escapeshellarg( $dbc->getConfig( 'password' ) ),					//  configured pasword as escaped shell arg
					'--force',																		//  continue if error eccoured
	//					'--use-threads='.( max( 1, $cores - 1 ) ),										//  how many threads to use (number of cores - 1)
	//					'--replace',																	//  replace if already existing
				) ),
				escapeshellarg( $dbc->getConfig( 'name' ) ),										//  configured database name as escaped shell arg
				escapeshellarg( $fileName ),														//  temp file name as escaped shell arg
			) );
		}
		$result	= $this->execCommandLine( $line );
		if( $this->useTempOptionsFile && $optionsFile )
			$optionsFile->remove();
		return $result;
	}

	public function importFileWithPrefix( string $fileName, ?string $prefix = NULL ): object
	{
		$dbc		= $this->client->getDatabase();
		$prefix		= $prefix ? $prefix : $dbc->getConfig( 'prefix' );								//  get table prefix from config as fallback
		$importFile	= $this->getTempFileWithAppliedTablePrefix( $fileName, $prefix );				//  get file with applied table prefix
		$result		= $this->importFile( $importFile );
		@unlink( $importFile );
		return $result;
	}

	public function insertPrefixInFile( string $fileName, string $prefix )
	{
		$quotedPrefix	= preg_quote( $prefix, '@' );
		$regExp		= "@(EXISTS|FROM|INTO|TABLE|TABLES|for table)( `)(".$quotedPrefix.")(.+)(`)@U";	//  build regular expression
		$callback	= [$this, '_callbackReplacePrefix'];										//  create replace callback

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

	public function setPrefixPlaceholder( string $prefixPlaceholder ): self
	{
		$this->prefixPlaceholder	= $prefixPlaceholder;
		return $this;
	}

	public function setUseTempOptionsFile( bool $use ): self
	{
		$this->useTempOptionsFile	= (bool) $use;
		return $this;
	}

	/*  --  PROTECTED  --  */

	protected function _callbackReplacePrefix( array $matches ): string
	{
		if( $matches[1] === 'for table' )
			return $matches[1].$matches[2].$matches[4].$matches[5];
		return $matches[1].$matches[2].$this->prefixPlaceholder.$matches[4].$matches[5];
	}

	protected function execCommandLine( string $line, string $command = 'mysql' ): object
	{
		$resultCode		= 0;
		$resultOutput	= [];
		exec( escapeshellarg( $command ).' '.$line, $resultOutput, $resultCode );
		return (object) ['code' => $resultCode, 'output' => $resultOutput];
	}

	protected function getTempFileWithAppliedTablePrefix( string $sourceFile, string $prefix ): string
	{
		$this->client->outVerbose( 'Applying table prefix to import file ...' );
//		$this->client->outVerbose( 'Applying table prefix ...' );
		$tempName	= $sourceFile.'.tmp';
		$fpIn		= fopen( $sourceFile, 'r' );												//  open source file
		$fpOut		= fopen( $tempName, 'a' );													//  prepare empty target file
		while( !feof( $fpIn ) ){																//  read input file until end
			$line	= fgets( $fpIn );															//  read line buffer
			$line	= str_replace( '<%?prefix%>', $prefix, $line );								//  replace table prefix placeholder
			fwrite( $fpOut, $line );															//  write buffer to target file
		}
		fclose( $fpOut );																		//  close target file
		fclose( $fpIn );																		//  close source file
		return $tempName;
	}
}
