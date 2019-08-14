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
class Hymn_Command_Database_Load extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $defaultPath;

	protected function __onInit(){
		$this->defaultPath		= $this->client->getConfigPath().'sql/';
	}

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
			return $this->client->out( 'Database can NOT be connected.' );

		$pathName		= $this->client->arguments->getArgument( 0 );
		if( $pathName && file_exists( $pathName ) ){
			if( is_dir( $pathName ) )
				$fileName	= $this->getLatestDump( $pathName, TRUE );
			else{
				$fileName	= $pathName;
			}
		}
		else if( file_exists( $this->defaultPath ) )
			$fileName		= $this->getLatestDump( NULL, TRUE );
		else
			$this->client->outError( 'No loadable database file or folder found.', Hymn_Client::EXIT_ON_RUN );

//		$this->client->outVerbose( 'File: '.$fileName );
		if( !( $fileName && file_exists( $fileName ) ) )
			return $this->client->out( 'No loadable database file found.' );

		if( !is_readable( $fileName ) )
			$this->client->outError( 'Missing read access to SQL script: '.$fileName );
		try{
			$dbc		= $this->client->getDatabase();
			$host		= $dbc->getConfig( 'host' );												//  get server host from config
			$port		= $dbc->getConfig( 'port' );												//  get server port from config
			$username	= $dbc->getConfig( 'username' );											//  get username from config
			$name		= $dbc->getConfig( 'name' );												//  get database name from config
			$prefix		= $dbc->getConfig( 'prefix' );												//  get table prefix from config
			$importFile	= $this->getTempFileWithAppliedTablePrefix( $fileName, $prefix );			//  get file with applied table prefix
			$fileSize	= Hymn_Tool_FileSize::get( $importFile );									//  format file size

			$cores		= (int) shell_exec( 'cat /proc/cpuinfo | grep processor | wc -l' );			//  get number of CPU cores
			$command	= vsprintf( 'mysql %s %s < %s', array(
				join( ' ', array(
					'--host='.escapeshellarg( $host ),												//  configured host as escaped shell arg
					'--port='.escapeshellarg( $port ),												//  configured port as escaped shell arg
					'--user='.escapeshellarg( $username ),											//  configured username as escaped shell arg
					'--password='.escapeshellarg( $dbc->getConfig( 'password' ) ),					//  configured pasword as escaped shell arg
//					'--use-threads='.( max( 1, $cores - 1 ) ),										//  how many threads to use (number of cores - 1)
					'--force',																		//  continue if error eccoured
//					'--replace',																	//  replace if already existing
				) ),
				escapeshellarg( $name ),															//  configured database name as escaped shell arg
				escapeshellarg( $importFile ),														//  temp file name as escaped shell arg
			) );

			$this->client->outVerbose( 'Import file:  '.$fileName );
			$this->client->outVerbose( 'File size:    '.$fileSize );
			$this->client->outVerbose( 'DB Server:    '.$host.'@'.$port );
			$this->client->outVerbose( 'Database:     '.$name );
			$this->client->outVerbose( 'Table prefix: '.( $prefix ? $prefix : '- (none)' ) );
			$this->client->outVerbose( 'Access as:    '.$username );
			if( $this->flags->dry ){
				return $this->client->out( 'Database setup okay - import itself not executed.' );
			}
			else {
				$this->client->outVerbose( 'Command:      '.$command );
				$this->client->out( 'Importing '.$fileName.' ('.$fileSize.') ...' );
				exec( $command );
			}
			@unlink( $importFile );
		}
		catch( Exception $e ){
			$this->client->out( 'Importing '.$fileName.' failed: '.$e->getMessage() );
		}
	}

	protected function getLatestDump( $path = NULL ){
		$pathConfig	= $this->client->getConfigPath();
		$path		= $path ? rtrim( $path, '/' ).'/' : $pathConfig.'sql/';
		if( !file_exists( $path ) )
			throw new RuntimeException( 'Path is not existing: '.$path );
		$this->client->outVerbose( 'Scanning folder '.$path.'...' );
		$list	= array();
		$index	= new DirectoryIterator( $path );
		foreach( $index as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			$this->client->outVerbose( 'Found: '.$entry->getFilename() );
			if( !preg_match( '/^dump_[0-9:_-]+\.sql$/', $entry->getFilename() ) )
				continue;
			$key		= str_replace( array( '_', '-' ), '_', $entry->getFilename() );
			$list[$key]	= $entry->getFilename();
		}
		krsort( $list );
		if( $list ){
			return $path.array_shift( $list );
		}
		return NULL;
	}

	protected function getTempFileWithAppliedTablePrefix( $sourceFile, $prefix ){
		$this->client->outVerbose( 'Applying table prefix to import file ...' );
//		$this->client->outVerbose( 'Applying table prefix ...' );
		$tempName	= $sourceFile.'.tmp';
		$fpIn		= fopen( $sourceFile, 'r' );													//  open source file
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
