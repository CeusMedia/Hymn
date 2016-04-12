<?php
class Hymn_Tool_BaseConfigEditor{

	/**	@var		array			$added					Added Properties */
	protected $added				= array();
	/**	@var		array			$comments				List of collected Comments */
	protected $comments				= array();
	/**	@var		array			$deleted				Deleted Properties */
	protected $deleted				= array();
	/**	@var		array			$disabled				List of disabled Properties */
	protected $disabled				= array();
	/**	@var		string			$fileName				URI of Ini File */
	protected $fileName;
	/**	@var		array			$lines					List of collected Lines */
	protected $lines				= array();
	/**	@var		array			$properties				List of collected Properties */
	protected $properties			= array();
	/**	@var		array			$renamed				Renamed Properties */
	protected $renamed				= array();
	/**	@var		boolean			$reservedWords			Flag: use reserved words */
	protected $reservedWords		= TRUE;
	/**	@var		string			$signDisabled			Sign( string) of disabled Properties */
	protected $signDisabled			= ';';
	/**	@var		string			$patternDisabled		Pattern( regex) of disabled Properties */
	protected $patternDisabled 		= '/^;/';
	/**	@var		string			$patternProperty		Pattern( regex) of Properties */
	protected $patternProperty		= '/^(;|[a-z0-9-])+([a-z0-9#.:@\/\\|_-]*[ |\t]*=)/i';
	/**	@var		string			$patternDescription		Pattern( regex) of Descriptions */
	protected $patternDescription	= '/^[;|#|:|\/|=]{1,2}/';
	/**	@var		string			$patternLineComment		Pattern( regex) of Line Comments */
	protected $patternLineComment	= '/([\t| ]+([\/]{2}|[;])+[\t| ]*)/';

	/**
	 *	Constructor, reads Property File.
	 *	@access		public
	 *	@param		string		$fileName		File Name of Property File, absolute or relative URI
	 *	@param		bool		$reservedWords	Flag: interprete reserved Words like yes,no,true,false,null
	 *	@return		void
	 */
	public function __construct( $fileName, $reservedWords = TRUE ){
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
	public function activateProperty( $key ){
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
	 *	@param		string		$key			Key of new Property
	 *	@param		string		$value			Value of new Property
	 *	@param		string		$comment		Comment of new Property
	 *	@param		bool		$state			Activity state of new Property
	 *	@return		bool
	 */
	public function addProperty( $key, $value, $comment = '', $state = TRUE ){
		$key = ( $state ? "" : $this->signDisabled ).$key;
		$this->added[] = array(
			"key"		=> $key,
			"value"		=> $value,
			"comment"	=> $comment,
		);
		return is_int( $this->write() );
	}

	/**
	 *	Returns a build Property line.
	 *	@access		private
	 *	@param		string		$key			Key of  Property
	 *	@param		string		$value			Value of Property
	 *	@param		string		$comment		Comment of Property
	 *	@return		string
	 */
	private function buildLine( $key, $value, $comment ){
		$content	= '"'.addslashes( $value ).'"';
		if( $this->reservedWords && is_bool( $value ) )
			$content	= $value ? "yes" : "no";
		$breaksKey		= 4 - floor( strlen( $key ) / 8 );
		$breaksValue	= 4 - floor( strlen( $content ) / 8 );
		if( $breaksKey < 1 )
			$breaksKey = 1;
		if( $breaksValue < 1 )
			$breaksValue = 1;
		$line	= $key.str_repeat( "\t", $breaksKey ).'= '.$content;
		if( $comment )
			$line	.= str_repeat( "\t", $breaksValue ).'; '.$comment;
		return $line;
	}

	protected function checkFile( $fileName ){
		if( !is_string( $fileName ) )
			throw new InvalidArgumentException( 'File name must a string' );
		if( !file_exists( $fileName ) )
			throw new RuntimeException( 'File "'.addslashes( $fileName ).'" is not existing' );
		if( !is_file( $fileName ) )
			throw new RuntimeException( 'Give file name  "'.$fileName.'" is not a file' );
		if( !is_readable( $fileName ) )
			throw new RuntimeException( 'File "'.$fileName.'" is not readable' );
		if( !is_writable( $fileName ) )
			throw new RuntimeException( 'File "'.$fileName.'" is not writable' );
	}

	/**
	 *	Deactivates a Property.
	 *	@access		public
	 *	@param		string		$key			Key of  Property
	 *	@return		bool
	 */
	public function deactivateProperty( $key ){
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
	 *	@return		string
	 */
	public function getComment( $key ){
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
	public function getCommentedProperties( $activeOnly = TRUE ){
		$list = array();
		foreach( $this->properties as $key => $value ){
			if( $activeOnly && !$this->isActiveProperty( $key ) )
				continue;
			$property = array(
				"key"		=>	$key,
				"value"		=>	$value,
				"comment"	=>	$this->getComment( $key ),
				"active"	=> 	(bool) $this->isActiveProperty( $key )
				);
			$list[] = $property;
		}
		return $list;
	}

	/**
	 *	Returns all Comments.
	 *	@access		public
	 *	@return		array
	 */
	public function getComments(){
		return $this->comments;
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getProperties( $activeOnly = TRUE ){
		$properties	= array();
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
	 *	@return		string
	 */
	public function getProperty( $key, $activeOnly = TRUE ){
		if( $activeOnly && !$this->isActiveProperty( $key ) )
			throw new InvalidArgumentException( 'Property "'.$key.'" is not set or inactive' );
		return $this->properties[$key];
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getPropertyList( $activeOnly = TRUE ){
		$properties = array();
		foreach( $this->properties as $key => $value ){
			if( $activeOnly && !$this->isActiveProperty( $key ) )
				continue;
			$properties[] = $key;
		}
		return $properties;
	}

	/**
	 *	Indicates wheter a Property is existing.
	 *	@access		public
	 *	@param		string		$key		Key of Property
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		bool
	 */
	public function hasProperty( $key, $activeOnly = TRUE ){
		if( $activeOnly )
			return isset( $this->properties[$key] );
		if( $this->hasProperty( $key, TRUE ) )
			return TRUE;
		return isset( $this->disabled[$key] );
	}

	/**
	 *	Indicates wheter a Property is active.
	 *	@access		public
	 *	@param		string		$key		Key of Property
	 *	@return		bool
	 */
	public function isActiveProperty( $key ){
		if( isset( $this->disabled ) )
			if( is_array( $this->disabled ) )
				if( in_array( $key, $this->disabled ) )
					return FALSE;
		return $this->hasProperty( $key );
	}

	/**
	 *	Reads the entire Property File and divides Properties and Comments.
	 *	@access		protected
	 *	@return		void
	 */
	protected function read(){
		$this->comments		= array();
		$this->disabled		= array();
		$this->lines		= array();
		$this->properties	= array();
		$this->lines		= array();
		$this->comments		= array();
		$commentOpen		= 0;
		$lines				= preg_split( '/\r?\n/', file_get_contents( $this->fileName ) );
		foreach( $lines as $line ){
			$line			= trim( $line );
			$this->lines[]	= $line;

			$commentOpen	+= preg_match( "@^/\*@", trim( $line ) );
			$commentOpen	-= preg_match( "@\*/$@", trim( $line ) );

			if( $commentOpen )
				continue;

			if( preg_match( $this->patternProperty, $line ) ){
				$pos	= strpos( $line, "=" );
				$key	= trim( substr( $line, 0, $pos ) );
				$value	= trim( substr( $line, ++$pos ) );

				if( preg_match( $this->patternDisabled, $key ) ){
					$key = preg_replace( $this->patternDisabled, "", $key );
					$this->disabled[] = $key;
				}

				//  --  EXTRACT COMMENT  --  //
				if( preg_match( $this->patternLineComment, $value ) ){
					$newValue		= preg_split( $this->patternLineComment, $value, 2 );
					$value			= trim( $newValue[0] );
					$inlineComment	= trim( $newValue[1] );
					$this->comments[$key] = $inlineComment;
				}

				//  --  CONVERT PROTECTED VALUES  --  //
				if( $this->reservedWords ){
					if( in_array( strtolower( $value ), array( 'yes', 'true' ) ) )
						$value	= TRUE;
					else if( in_array( strtolower( $value ), array( 'no', 'false' ) ) )
						$value	= FALSE;
					else if( strtolower( $value ) === "null" )
						$value	= NULL;
				}
				if( preg_match( '@^".*"$@', $value ) )
					$value	= substr( stripslashes( $value ), 1, -1 );
				$this->properties[$key] = $value;
			}
		}
	}

	/**
	 *	Sets the Comment of a Property.
	 *	@access		public
	 *	@param		string		$key			Key of Property
	 *	@param		string		$value			Value of Property
	 *	@return		bool
	 */
	public function setProperty( $key, $value ){
		if( $this->hasProperty( $key ) )
			$this->properties[$key] = $value;
		else
			$this->addProperty( $key, $value, FALSE, TRUE );
		return is_int( $this->write() );
	}

	/**
	 *	Writes manipulated Content to File.
	 *	@access		protected
	 *	@return		int			Number of written bytes
	 */
	protected function write(){
		$newLines	= array();
		foreach( $this->lines as $line ){
			if( preg_match( $this->patternProperty, $line ) ){
				$pos		= strpos( $line, "=" );
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
					$comment	= isset( $this->comments[$newKey] ) ? $this->comments[$newKey] : "";
					$line = $this->buildLine( $key, $this->properties[$newKey], $comment );
				}
				else{
					if( $this->isActiveProperty( $pureKey ) && preg_match( $this->patternDisabled, $key ) )
						$key = substr( $key, 1 );
					else if( !$this->isActiveProperty( $pureKey ) && !preg_match( $this->patternDisabled, $key ) )
						$key = $this->signDisabled.$key;
					$comment	= isset( $this->comments[$pureKey] ) ? $this->comments[$pureKey] : "";
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

		$this->added	= array();
		$this->deleted	= array();
		$this->renamed	= array();
		$this->read();
		return $result;
	}
}
?>
