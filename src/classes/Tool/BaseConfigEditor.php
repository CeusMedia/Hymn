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
	/**	@var		array			$sections				List of collected Sections */
	protected $sections				= array();
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
	/**	@var		string			$patternSection			Pattern( regex) of Sections */
	protected $patternSection		= '/^\s*\[([a-z0-9_=.,:;#@-]+)\]\s*$/i';
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
	 *	@param		string		$section		Section of Property
	 *	@param		string		$key			Key of  Property
	 *	@return		bool
	 */
	public function activateProperty( $section, $key ){
		$this->checkSection( $section );
		if( !$this->hasProperty( $section, $key ) )
			throw new InvalidArgumentException( 'Key "'.$key.'" is not existing in section "'.$section.'"' );
		if( $this->isActiveProperty( $key, $section ) )
			throw new LogicException( 'Key "'.$key.'" is already active' );
		unset( $this->disabled[$section][array_search( $key, $this->disabled[$section] )] );
		return is_int( $this->write() );
	}

	/**
	 *	Adds a new Property with Comment.
	 *	@access		public
	 *	@param		string		$section		Section to add Property to
	 *	@param		string		$key			Key of new Property
	 *	@param		string		$value			Value of new Property
	 *	@param		string		$comment		Comment of new Property
	 *	@param		bool		$state			Activity state of new Property
	 *	@return		bool
	 */
	public function addProperty( $section, $key, $value, $comment = '', $state = TRUE ){
		if( !in_array( $section, $this->sections ) )
			$this->addSection( $section );
		$key = ( $state ? "" : $this->signDisabled ).$key;
		$this->added[] = array(
			"key"		=> $key,
			"value"		=> $value,
			"comment"	=> $comment,
			"section"	=> $section,
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

	protected function checkSection( $section ){
		if( !$this->hasSection( $section ) )
			throw new InvalidArgumentException( 'Section "'.$section.'" is not existing' );
	}

	/**
	 *	Deactivates a Property.
	 *	@access		public
	 *	@param		string		$section		Section of Property
	 *	@param		string		$key			Key of  Property
	 *	@return		bool
	 */
	public function deactivateProperty( $section, $key ){
		if( !$this->hasProperty( $section, $key ) )
			throw new InvalidArgumentException( 'Key "'.$key.'" is not existing in section "'.$section.'"' );
		if( !$this->isActiveProperty( $section, $key ) )
			throw new LogicException( 'Key "'.$key.'" is already inactive' );
		$this->disabled[$section][] = $key;
		return is_int( $this->write() );
	}

	/**
	 *	Returns the Comment of a Property.
	 *	@access		public
	 *	@param		string		$section		Section of Property
	 *	@param		string		$key			Key of Property
	 *	@return		string
	 */
	public function getComment( $section, $key ){
		$this->checkSection( $section );
		if( !empty( $this->comments[$section][$key] ) )
			return $this->comments[$section][$key];
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
		foreach( $this->sections as $section ){
			foreach( $this->properties[$section] as $key => $value ){
				if( $activeOnly && !$this->isActiveProperty( $key, $section ) )
					continue;
				$property = array(
					"key"		=>	$key,
					"value"		=>	$value,
					"comment"	=>	$this->getComment( $key, $section ),
					"active"	=> 	(bool) $this->isActiveProperty( $key, $section )
					);
				$list[$section][] = $property;
			}
		}
		return $list;
	}

	/**
	 *	Returns all Comments or all Comments of a Section.
	 *	@access		public
	 *	@param		string		$section		Key of Section
	 *	@return		array
	 */
	public function getComments( $section = NULL ){
		if( $section ){
			$this->checkSection( $section );
			return $this->comments[$section];
		}
		return $this->comments;
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		string		$section		Only Section with given Section Name
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getProperties( $section = NULL, $activeOnly = TRUE ){
		$properties = array();
		if( $section ){
			$this->checkSection( $section );
			foreach( $this->properties[$section]  as $key => $value ){
				if( $activeOnly && !$this->isActiveProperty( $key, $section ) )
					continue;
				$properties[$key] = $value;
			}
		}
		else{
			foreach( $this->sections as $section){
				$properties[$section]	= array();
				foreach( $this->properties[$section] as $key => $value ){
					if( $activeOnly && !$this->isActiveProperty( $key, $section ) )
						continue;
					$properties[$section][$key] = $value;
				}
			}
		}
		return $properties;
	}

	/**
	 *	Returns the Value of a Property by its Key.
	 *	@access		public
	 *	@param		string		$section		Key of Section
	 *	@param		string		$key			Key of Property
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		string
	 */
	public function getProperty( $section, $key, $activeOnly = TRUE ){
		$this->checkSection( $section );
		if( $activeOnly && !$this->isActiveProperty( $key, $section ) )
			throw new InvalidArgumentException( 'Property "'.$key.'" is not set or inactive' );
		return $this->properties[$section][$key];
	}

	/**
	 *	Returns an Array with all or active only Properties.
	 *	@access		public
	 *	@param		bool		$activeOnly		Flag: return only active Properties
	 *	@return		array
	 */
	public function getPropertyList( $activeOnly = TRUE ){
		$properties = array();
		foreach( $this->sections as $sectionName ){
			foreach( $this->properties[$sectionName]  as $key => $value ){
				if( $activeOnly && !$this->isActiveProperty( $sectionName, $key ) )
					continue;
				$properties[$sectionName][] = $key;
			}
		}
		return $properties;
	}

	/**
	 *	Returns an array of all Section Keys.
	 *	@access		public
	 *	@return		array
	 */
	public function getSections(){
		return $this->sections;
	}

	/**
	 *	Indicates wheter a Property is existing.
	 *	@access		public
	 *	@param		string		$section	Key of Section
	 *	@param		string		$key		Key of Property
	 *	@return		bool
	 */
	public function hasProperty( $section, $key ){
		$this->checkSection( $section );
		return isset( $this->properties[$section][$key] );
	}

	/**
	 *	Indicates wheter a Property is existing.
	 *	@access		public
	 *	@param		string		$section	Key of Section
	 *	@return		bool
	 */
	public function hasSection( $section ){
		return in_array( $section, $this->sections );
	}


	/**
	 *	Indicates wheter a Property is active.
	 *	@access		public
	 *	@param		string		$sections	Key of Section
	 *	@param		string		$key		Key of Property
	 *	@return		bool
	 */
	public function isActiveProperty( $section, $key ){
		if( isset( $this->disabled[$section] ) )
			if( is_array( $this->disabled[$section] ) )
				if( in_array( $key, $this->disabled[$section] ) )
					return FALSE;
		return $this->hasProperty( $section, $key );
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
		$this->sections		= array();
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

			if( preg_match( $this->patternSection, $line ) ){
				$currentSection		= preg_replace( $this->patternSection, '\\1', $line );
				$this->sections[]	= $currentSection;
				$this->disabled[$currentSection]	= array();
				$this->properties[$currentSection]	= array();
				$this->comments[$currentSection]	= array();
			}
			else if( preg_match( $this->patternProperty, $line ) ){
				$pos	= strpos( $line, "=" );
				$key	= trim( substr( $line, 0, $pos ) );
				$value	= trim( substr( $line, ++$pos ) );

				if( preg_match( $this->patternDisabled, $key ) ){
					$key = preg_replace( $this->patternDisabled, "", $key );
					$this->disabled[$currentSection][] = $key;
				}

				//  --  EXTRACT COMMENT  --  //
				if( preg_match( $this->patternLineComment, $value ) ){
					$newValue		= preg_split( $this->patternLineComment, $value, 2 );
					$value			= trim( $newValue[0] );
					$inlineComment	= trim( $newValue[1] );
					$this->comments[$currentSection][$key] = $inlineComment;
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
				$this->properties[$currentSection][$key] = $value;
			}
		}
	}

	/**
	 *	Sets the Comment of a Property.
	 *	@access		public
	 *	@param		string		$section		Key of Section
	 *	@param		string		$key			Key of Property
	 *	@param		string		$value			Value of Property
	 *	@return		bool
	 */
	public function setProperty( $section, $key, $value ){
		if( !in_array( $section, $this->sections ) )
			$this->addSection( $section );
		if( $this->hasProperty( $section, $key ) )
			$this->properties[$section][$key] = $value;
		else
			$this->addProperty( $section, $key, $value, FALSE, TRUE,  );
		return is_int( $this->write() );
	}

	/**
	 *	Writes manipulated Content to File.
	 *	@access		protected
	 *	@return		int			Number of written bytes
	 */
	protected function write(){
!!		$file		= new FS_File_Writer( $this->fileName );
		$newLines	= array();
		$currentSection	= "";
		foreach( $this->lines as $line ){
			if( preg_match( $this->patternSection, $line ) ){
				$lastSection = $currentSection;
#				$newAdded = array();
				if( $lastSection ){
					foreach( $this->added as $nr => $property ){
						if( $property['section'] == $lastSection ){
							if( !trim( $newLines[count($newLines)-1] ) )
								array_pop( $newLines );
							$newLines[]	= $this->buildLine( $property['key'], $property['value'], $property['comment'] );
							$newLines[]	= "";
							unset( $this->added[$nr] );
						}
#						else $newAdded[] = $property;
					}
				}
				$currentSection =  substr( trim( $line ), 1, -1 );
				if( !in_array( $currentSection, $this->sections ) )
					continue;
			}
			else if( preg_match( $this->patternProperty, $line ) ){
				$pos		= strpos( $line, "=" );
				$key		= trim( substr( $line, 0, $pos ) );
				$pureKey	= preg_replace( $this->patternDisabled, "", $key );
				$parts		= explode(  "//", trim( substr( $line, $pos+1 ) ) );
				if( count( $parts ) > 1 )
					$comment = trim( $parts[1] );
				if( in_array( $currentSection, $this->sections ) ){
					if( isset( $this->deleted[$currentSection] ) && in_array( $pureKey, $this->deleted[$currentSection] ) )
						unset( $line );
					else if( isset( $this->renamed[$currentSection] ) && in_array( $pureKey, array_keys( $this->renamed[$currentSection] ) ) ){
						$newKey	= $key	= $this->renamed[$currentSection][$pureKey];
						if( !$this->isActiveProperty( $newKey, $currentSection) )
							$key = $this->signDisabled.$key;
						$comment	= isset( $this->comments[$currentSection][$newKey] ) ? $this->comments[$currentSection][$newKey] : "";
						$line = $this->buildLine( $key, $this->properties[$currentSection][$newKey], $comment );
					}
					else{
						if( $this->isActiveProperty( $pureKey, $currentSection ) && preg_match( $this->patternDisabled, $key ) )
							$key = substr( $key, 1 );
						else if( !$this->isActiveProperty( $pureKey, $currentSection ) && !preg_match( $this->patternDisabled, $key ) )
							$key = $this->signDisabled.$key;
						$comment	= isset( $this->comments[$currentSection][$pureKey] ) ? $this->comments[$currentSection][$pureKey] : "";
						$line = $this->buildLine( $key, $this->properties[$currentSection][$pureKey], $comment );
					}
				}
				else
					unset( $line );
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
