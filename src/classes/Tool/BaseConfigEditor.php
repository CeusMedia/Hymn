<?php
declare(strict_types=1);

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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Tool_BaseConfigEditor
{
	/**	@var		array			$added					Added Properties */
	protected array $added				= [];

	/**	@var		array			$comments				List of collected Comments */
	protected array $comments				= [];

	/**	@var		array			$deleted				Deleted Properties */
	protected array $deleted				= [];

	/**	@var		array			$disabled				List of disabled Properties */
	protected array $disabled				= [];

	/**	@var		string			$fileName				URI of Ini File */
	protected string $fileName;

	/**	@var		array			$lines					List of collected Lines */
	protected array $lines				= [];

	/**	@var		array			$properties				List of collected Properties */
	protected array $properties			= [];

	/**	@var		array			$renamed				Renamed Properties */
	protected array $renamed				= [];

	/**	@var		boolean			$reservedWords			Flag: use reserved words */
	protected bool $reservedWords		= TRUE;

	/**	@var		string			$signDisabled			Sign( string) of disabled Properties */
	protected string $signDisabled			= ';';

	/**	@var		string			$patternDisabled		Pattern( regex) of disabled Properties */
	protected string $patternDisabled 		= '/^;/';

	/**	@var		string			$patternProperty		Pattern( regex) of Properties */
	protected string $patternProperty		= '/^(;|[a-z0-9-])+([a-z0-9#.:@\/\\|_-]*[ |\t]*=)/i';

	/**	@var		string			$patternDescription		Pattern( regex) of Descriptions */
	protected string $patternDescription	= '/^[;|#|:|\/|=]{1,2}/';

	/**	@var		string			$patternLineComment		Pattern( regex) of Line Comments */
	protected string $patternLineComment	= '/([\t| ]+([\/]{2}|[;])+[\t| ]*)/';

	/**
	 *	Constructor, reads Property File.
	 *	@access		public
	 *	@param		string		$fileName		File Name of Property File, absolute or relative URI
	 *	@param		bool		$reservedWords	Flag: interpret reserved Words like yes,no,true,false,null
	 *	@return		void
	 */
	public function __construct( string $fileName, bool $reservedWords = TRUE )
	{
		$this->createFileIfNotExists( $fileName );
		$this->checkFile( $fileName );
		$this->fileName				= $fileName;
		$this->reservedWords		= $reservedWords;
		$this->read();
	}

	/**
	 *	Activates a Property.
	 *	@access		public
	 *	@param		string		$key			Key of  Property
	 *	@return		bool
	 */
	public function activateProperty( string $key ): bool
	{
		if( !$this->hasProperty( $key ) )
			throw new InvalidArgumentException( 'Key "'.$key.'" is not existing' );
		if( $this->isActiveProperty( $key ) )
			throw new LogicException( 'Key "'.$key.'" is already active' );
		unset( $this->disabled[array_search( $key, $this->disabled )] );
		return is_int( $this->write() );
	}

	/**
	 *	Adds a new Property with Comment.
	 *	@access		public
	 *	@param		string			$key			Key of new Property
	 *	@param		string|bool		$value			Value of new Property
	 *	@param		string			$comment		Comment of new Property
	 *	@param		bool		$state			Activity state of new Property
	 *	@return		bool
	 */
	public function addProperty( string $key, string|bool $value, string $comment = '', bool $state = TRUE ): bool
	{
		$key	= ( $state ? "" : $this->signDisabled ).$key;
		$this->added[] = [
			'key'		=> $key,
			'value'		=> $value,
			'comment'	=> $comment,
		];
		return is_int( $this->write() );
	}

	/**
	 *	Deactivates a Property.
	 *	@access		public
	 *	@param		string		$key			Key of  Property
	 *	@return		bool
	 */
	public function deactivateProperty( string $key ): bool
	{
		if( !$this->hasProperty( $key ) )
			throw new InvalidArgumentException( 'Key "'.$key.'" is not existing' );
		if( !$this->isActiveProperty( $key ) )
			throw new LogicException( 'Key "'.$key.'" is already inactive' );
		$this->disabled[] = $key;
		return is_int( $this->write() );
	}

	/**
	 *	Returns the Comment of a Property.
	 *	@access		public
	 *	@param		string		$key			Key of Property
	 *	@return		string|NULL
	 */
	public function getComment( string $key ): ?string
	{
		if( !empty( $this->comments[$key] ) )
			return $this->comments[$key];
		return NULL;
	}

	/**
	 *	Returns a List of Property Arrays with Key, Value, Comment and Activity of every Property.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getCommentedProperties( bool $activeOnly = TRUE ): array
	{
		$list = [];
		foreach( $this->properties as $key => $value ){
			if( $activeOnly && !$this->isActiveProperty( $key ) )
				continue;
			$list[]	= [
				'key'		=>	$key,
				'value'		=>	$value,
				'comment'	=>	$this->getComment( $key ),
				'active'	=> 	$this->isActiveProperty( $key )
			];
		}
		return $list;
	}

	/**
	 *	Returns all Comments.
	 *	@access		public
	 *	@return		array
	 */
	public function getComments(): array
	{
		return $this->comments;
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getProperties( bool $activeOnly = TRUE ): array
	{
		$properties	= [];
		foreach( $this->properties as $key => $value ){
			if( $activeOnly && !$this->isActiveProperty( $key ) )
				continue;
			$properties[$key] = $value;
		}
		return $properties;
	}

	/**
	 *	Returns the Value of a Property by its Key.
	 *	@access		public
	 *	@param		string		$key			Key of Property
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		string|NULL
	 */
	public function getProperty( string $key, bool $activeOnly = TRUE ): ?string
	{
		if( $activeOnly && !$this->isActiveProperty( $key ) )
			throw new InvalidArgumentException( 'Property "'.$key.'" is not set or inactive' );
		if( isset( $this->properties[$key] ) )
			return $this->properties[$key];
		return $this->disabled[$key] ?? NULL;
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getPropertyList( bool $activeOnly = TRUE ): array
	{
		$list = [];
		foreach( array_keys( $this->properties ) as $key ){
			if( $activeOnly && !$this->isActiveProperty( $key ) )
				continue;
			$list[] = $key;
		}
		return $list;
	}

	/**
	 *	Indicates whether a Property is existing.
	 *	@access		public
	 *	@param		string		$key		Key of Property
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		bool
	 */
	public function hasProperty( string $key, bool $activeOnly = TRUE ): bool
	{
		if( $activeOnly )
			return isset( $this->properties[$key] );
		if( isset( $this->properties[$key] ) )
			return TRUE;
		return isset( $this->disabled[$key] );
	}

	/**
	 *	Indicates whether a Property is active.
	 *	@access		public
	 *	@param		string		$key		Key of Property
	 *	@return		bool
	 */
	public function isActiveProperty( string $key ): bool
	{
		if( 0 !== count( $this->disabled ) )
			if( in_array( $key, $this->disabled ) )
				return FALSE;
		return $this->hasProperty( $key );
	}

	/**
	 *	Sets the Comment of a Property.
	 *	@access		public
	 *	@param		string			$key			Key of Property
	 *	@param		string|bool		$value			Value of Property
	 *	@return		bool
	 */
	public function setProperty( string $key, string|bool $value ): bool
	{
		if( $this->hasProperty( $key ) )
			$this->properties[$key] = $value;
		else
			$this->addProperty( $key, $value );
		return is_int( $this->write() );
	}

	protected function checkFile( string $fileName ): void
	{
		if( !file_exists( $fileName ) )
			throw new RuntimeException( 'File "'.addslashes( $fileName ).'" is not existing' );
		if( !is_file( $fileName ) )
			throw new RuntimeException( 'Give file name  "'.$fileName.'" is not a file' );
		if( !is_readable( $fileName ) )
			throw new RuntimeException( 'File "'.$fileName.'" is not readable' );
		if( !is_writable( $fileName ) )
			throw new RuntimeException( 'File "'.$fileName.'" is not writable' );
	}

	protected function createFileIfNotExists( string $fileName ): void
	{
		if( !file_exists( $fileName ) )
			touch( $fileName );
	}

	/**
	 *	Reads the entire Property File and divides Properties and Comments.
	 *	@access		protected
	 *	@return		void
	 */
	protected function read(): void
	{
		$this->disabled		= [];
		$this->properties	= [];
		$this->lines		= [];
		$this->comments		= [];
		$commentOpen		= 0;
		$content			= file_get_contents( $this->fileName );
		$lines				= preg_split( '/\r?\n/', $content ?: '' ) ?: [];
		foreach( $lines as $line ){
			$line			= trim( $line );
			$this->lines[]	= $line;

			$commentOpen	+= preg_match( "@^/\*@", trim( $line ) );
			$commentOpen	-= preg_match( "@\*/$@", trim( $line ) );

			if( $commentOpen )
				continue;

			if( preg_match( $this->patternProperty, $line ) ){
				$pos	= (int) strpos( $line, '=' );
				$key	= trim( substr( $line, 0, $pos ) );
				$value	= trim( substr( $line, ++$pos ) );

				if( preg_match( $this->patternDisabled, $key ) ){
					$key = preg_replace( $this->patternDisabled, '', $key );
					$this->disabled[] = $key;
				}

				//  --  EXTRACT COMMENT  --  //
				if( preg_match( $this->patternLineComment, $value ) ){
					$newValue		= preg_split( $this->patternLineComment, $value, 2 ) ?: [];
					$value			= trim( $newValue[0] ?? '' );
					$inlineComment	= trim( $newValue[1] ?? NULL );
					$this->comments[$key] = $inlineComment;
				}

				//  --  CONVERT PROTECTED VALUES  --  //
				if( $this->reservedWords ){
					if( in_array( strtoupper( $value ), ['YES', 'TRUE'] ) )
						$value	= TRUE;
					else if( in_array( strtoupper( $value ), ['NO', 'FALSE'] ) )
						$value	= FALSE;
					else if( 'NULL' === strtoupper( $value ) )
						$value	= NULL;
				}
				if( preg_match( '@^".*"$@', (string) $value ) )
					$value	= substr( stripslashes( (string) $value ), 1, -1 );
				$this->properties[$key] = $value;
			}
		}
	}

	/**
	 *	Writes manipulated Content to File.
	 *	@access		protected
	 *	@return		int			Number of written bytes
	 */
	protected function write(): int
	{
		$newLines	= [];
		foreach( $this->lines as $line ){
			if( preg_match( $this->patternProperty, $line ) ){
				$pos		= (int) strpos( $line, "=" );
				$key		= trim( substr( $line, 0, $pos ) );
				$pureKey	= preg_replace( $this->patternDisabled, "", $key );
				$parts		= explode(  "//", trim( substr( $line, $pos+1 ) ) );
				if( count( $parts ) > 1 )
					$comment = trim( $parts[1] );
				if( in_array( $pureKey, $this->deleted ) )
					unset( $line );
				else if( in_array( $pureKey, array_keys( $this->renamed ) ) ){
					$newKey	= $key	= $this->renamed[$pureKey];
					if( !$this->isActiveProperty( $newKey ) )
						$key = $this->signDisabled.$key;
					$comment	= $this->comments[$newKey] ?? NULL;
					$line = $this->buildLine( $key, $this->properties[$newKey], $comment );
				}
				else{
					if( $this->isActiveProperty( $pureKey ) && preg_match( $this->patternDisabled, $key ) )
						$key = substr( $key, 1 );
					else if( !$this->isActiveProperty( $pureKey ) && !preg_match( $this->patternDisabled, $key ) )
						$key = $this->signDisabled.$key;
					$comment	= $this->comments[$pureKey] ?? NULL;
					$line = $this->buildLine( $key, $this->properties[$pureKey], $comment );
				}
			}
			if( isset( $line ) )
				$newLines[] = $line;
		}
		foreach( $this->added as $property ){
			$newLine	= $this->buildLine( $property['key'], $property['value'], $property['comment'] );
			$newLines[]	= $newLine;
		}
		$string			= implode( PHP_EOL, $newLines );
		$result			= file_put_contents( $this->fileName, $string );
		if( $result === FALSE )
			throw new RuntimeException( 'File "'.$this->fileName.'" could not been written' );

		$this->added	= [];
		$this->deleted	= [];
		$this->renamed	= [];
		$this->read();
		return $result;
	}

	/**
	 *	Returns a build Property line.
	 *	@access		private
	 *	@param		string			$key			Key of  Property
	 *	@param		string|bool		$value			Value of Property
	 *	@param		string|NULL		$comment		Comment of Property
	 *	@return		string
	 */
	private function buildLine( string $key, string|bool $value, ?string $comment = NULL ): string
	{
		if( $this->reservedWords && is_bool( $value ) )
			$content	= $value ? 'yes' : 'no';
		else
			$content	= '"'.addslashes( (string) $value ).'"';

		$breaksKey		= 4 - (int) floor( strlen( $key ) / 8 );
		$breaksValue	= 4 - (int) floor( strlen( $content ) / 8 );
		if( $breaksKey < 1 )
			$breaksKey = 1;
		if( $breaksValue < 1 )
			$breaksValue = 1;
		$line	= $key.str_repeat( "\t", $breaksKey ).'= '.$content;
		if( '' !== ( $comment ?? '' ) )
			$line	.= str_repeat( "\t", $breaksValue ).'; '.$comment;
		return $line;
	}
}
