<?php
/**
 *	XML element based on SimpleXMLElement with improved attribute and content handling.
 *
 *	Copyright (c) 2007-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool.XML
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	XML element based on SimpleXMLElement with improved attribute Handling.
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.XML
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			namespace handling: implement detection "Prefix or URI?", see http://www.w3.org/TR/REC-xml/#NT-Name
 */
class Hymn_Tool_XML_Element extends SimpleXMLElement
{
	/**
	 *	Adds an attributes.
	 *	@access		public
	 *	@param		mixed		$name		Name of attribute
	 *	@param		mixed		$value		Value of attribute
	 *	@param		mixed	$nsPrefix	Namespace prefix of attribute
	 *	@param		string|NULL	$nsURI		Namespace URI of attribute
	 *	@return		void
	 *	@throws		RuntimeException		if attribute is already set
	 *	@throws		RuntimeException		if namespace prefix is neither registered nor given
	 */
	public function addAttribute( $name, $value = NULL, $nsPrefix = NULL, ?string $nsURI = NULL ): void
	{
		$key	= $nsPrefix ? $nsPrefix.':'.$name : $name;
		if( $nsPrefix ){
			$namespaces	= $this->getDocNamespaces();
			$key		= $nsPrefix ? $nsPrefix.':'.$name : $name;
			if( $this->hasAttribute( $name, $nsPrefix ) )
				throw new RuntimeException( 'Attribute "'.$key.'" is already set' );
			if( array_key_exists( $nsPrefix, $namespaces ) ){
				parent::addAttribute( $key, $value, $namespaces[$nsPrefix] );
				return;
			}
			if( $nsURI ){
				parent::addAttribute( $key, $value, $nsURI );
				return;
			}
			throw new RuntimeException( 'Namespace prefix is not registered and namespace URI is missing' );
		}
		if( $this->hasAttribute( $name ) )
			throw new RuntimeException( 'Attribute "'.$name.'" is already set' );
		parent::addAttribute( $name, $value );
	}

	/**
	 *	Adds a child element. Sets node content as CDATA section if necessary.
	 *	@access		public
	 *	@param		mixed		$name		Name of child element
	 *	@param		mixed		$value		Value of child element
	 *	@param		mixed		$nsPrefix	Namespace prefix of child element
	 *	@param		string|NULL	$nsURI		Namespace URI of child element
	 *	@return		SimpleXMLElement
	 *	@throws		RuntimeException		if namespace prefix is neither registered nor given
	 */
	public function addChild( $name, $value = NULL, $nsPrefix = NULL, ?string $nsURI = NULL ): SimpleXMLElement
	{
		$child		= NULL;
		if( $nsPrefix ){
			$namespaces	= $this->getDocNamespaces();
			$key		= $nsPrefix ? $nsPrefix.':'.$name : $name;
			if( array_key_exists( $nsPrefix, $namespaces ) )
				$child	= parent::addChild( $name, NULL, $namespaces[$nsPrefix] );
			else if( $nsURI )
				$child	= parent::addChild( $key, NULL, $nsURI );
			else
				throw new RuntimeException( 'Namespace prefix is not registered and namespace URI is missing' );
		}
		else
			$child	= parent::addChild( $name );
		if( $value !== NULL )
			$child->setValue( $value );
		return $child;
	}

	/**
	 *	Create a child element with CDATA value
	 *	@param		string			$name			The name of the child element to add.
	 *	@param		string			$text			The CDATA value of the child element.
	 *	@param		string|NULL		$nsPrefix		Namespace prefix of child element
	 *	@param		string|NULL		$nsURI			Namespace URI of child element
	 *	@return		SimpleXMLElement
	 *	@reprecated	use addChild instead
	 */
	public function addChildCData( string $name, string $text, ?string $nsPrefix = NULL, ?string $nsURI = NULL ): SimpleXMLElement
	{
		$child	= $this->addChild( $name, NULL, $nsPrefix, $nsURI );
		$child->addCData( $text );
		return $child;
	}

	/**
	 *	Returns count of attributes.
	 *	@access		public
	 *	@param		string		$nsPrefix		Namespace prefix of attributes
	 *	@return		int
	 */
	public function countAttributes( ?string $nsPrefix = NULL ): int
	{
		return count( $this->getAttributeNames( $nsPrefix ) );
	}

	/**
	 *	Returns count of children.
	 *	@access		public
	 *	@return		int
	 */
	public function countChildren( ?string $nsPrefix = NULL ): int
	{
		$i = 0;
		foreach( $this->children( $nsPrefix, TRUE ) as $node )
			$i++;
		return $i;
	}

	/**
	 *	Returns the value of an attribute by it's name.
	 *	@access		public
	 *	@param		string		$name		Name of attribute
	 *	@param		string		$nsPrefix	Namespace prefix of attribute
	 *	@return		string
	 *	@throws		RuntimeException		if attribute is not set
	 */
	public function getAttribute( string $name, ?string $nsPrefix = NULL ): string
	{
		$data	= $nsPrefix ? $this->attributes( $nsPrefix, TRUE ) : $this->attributes();
		if( !isset( $data[$name] ) )
			throw new RuntimeException( 'Attribute "'.( $nsPrefix ? $nsPrefix.':'.$name : $name ).'" is not set' );
		return (string) $data[$name];
	}

	/**
	 *	Returns List of attribute names.
	 *	@access		public
	 *	@param		string		$nsPrefix	Namespace prefix of attribute
	 *	@return		array
	 */
	public function getAttributeNames( ?string $nsPrefix = NULL ): array
	{
		$list	= [];
		$data	= $nsPrefix ? $this->attributes( $nsPrefix, TRUE ) : $this->attributes();
		foreach( $data as $name => $value )
			$list[] = $name;
		return $list;
	}

	/**
	 *	Returns map of attributes.
	 *	@access		public
	 *	@param		string		$nsPrefix	Namespace prefix of attributes
	 *	@return		array
	 */
	public function getAttributes( ?string $nsPrefix = NULL ): array
	{
		$list	= [];
		foreach( $this->attributes( $nsPrefix, TRUE ) as $name => $value )
			$list[$name]	= (string) $value;
		return $list;
	}

	/**
	 *	Returns Text Value.
	 *	@access		public
	 *	@return		string
	 */
	public function getValue(): string
	{
		return (string) $this;
	}

	/**
	 *	Indicates whether an attribute is existing by it's name.
	 *	@access		public
	 *	@param		string		$name		Name of attribute
	 *	@param		string		$nsPrefix	Namespace prefix of attribute
	 *	@return		bool
	 */
	public function hasAttribute( string $name, ?string $nsPrefix = NULL ): bool
	{
		$names	= $this->getAttributeNames( $nsPrefix );
		return in_array( $name, $names );
	}

	/**
	 *	Removes an attribute by it's name.
	 *	@access		public
	 *	@param		string		$name		Name of attribute
	 *	@param		string		$nsPrefix	Namespace prefix of attribute
	 *	@return		boolean
	 */
	public function removeAttribute( string $name, ?string $nsPrefix = NULL ): bool
	{
		$data	= $nsPrefix ? $this->attributes( $nsPrefix, TRUE ) : $this->attributes();
		foreach( $data as $key => $attributeNode ){
			if( $key == $name ){
				unset( $data[$key] );
				return TRUE;
			}
		}
		return FALSE;
	}

	public function remove(): void
	{
		$dom	= dom_import_simplexml( $this );
		$dom->parentNode->removeChild( $dom );
	}

	public function removeChild( string $name, ?int $number = NULL ): int
	{
		$nr		= 0;
		foreach( $this->children() as $nodeName => $child ){
			if( $nodeName == $name ){
				if( $number === NULL || $nr === (int) $number ){
					$dom	= dom_import_simplexml( $child );
					$dom->parentNode->removeChild( $dom );
				}
				$nr++;
			}
		}
		return $nr;
	}

	/**
	 *	Sets an attribute from by it's name.
	 *	Adds attribute if not existing.
	 *	Removes attribute if value is NULL.
	 *	@access		public
	 *	@param		string		$name		Name of attribute
	 *	@param		string		$value		Value of attribute, NULL means removal
	 *	@param		string|NULL	$nsPrefix	Namespace prefix of attribute
	 *	@param		string|NULL	$nsURI		Namespace URI of attribute
	 *	@return		void
	 */
	public function setAttribute( string $name, string $value, ?string $nsPrefix = NULL, ?string $nsURI = NULL )
	{
		if( $value !== NULL ){
			if( !$this->hasAttribute( $name, $nsPrefix ) ){
				$this->addAttribute( $name, $value, $nsPrefix, $nsURI );
				return;
			}
			$this->removeAttribute( $name, $nsPrefix );
			$this->addAttribute( $name, $value, $nsPrefix, $nsURI );
		}
		else if( $this->hasAttribute( $name, $nsPrefix ) ){
			$this->removeAttribute( $name, $nsPrefix );
		}
	}

	/**
	 *	Returns Text Value.
	 *	@access		public
	 *	@return		void
	 */
	public function setValue( string $value, bool $cdata = FALSE ): void
	{
		if( !is_string( $value ) && $value !== NULL )
			throw new InvalidArgumentException( 'Value must be a string or NULL - '.gettype( $value ).' given' );

		$value	= preg_replace( "/(.*)<!\[CDATA\[(.*)\]\]>(.*)/iU", "\\1\\2\\3", $value );
		if( $cdata || preg_match( '/&|</', $value ) ){												//  string is known or detected to be CDATA
			$dom	= dom_import_simplexml( $this );												//  import node in DOM
			$cdata	= $dom->ownerDocument->createCDATASection( $value );							//  create a new CDATA section
			$dom->nodeValue	= "";																	//  clear node content
			$dom->appendChild( $cdata );															//  add CDATA section
		}
		else{ 																						//  normal node content
			dom_import_simplexml( $this )->nodeValue	= $value;									//  set node content
		}
	}

	/**
	 *	Add CDATA text in a node
	 *	@param		string		$text		The CDATA value to add
	 *	@return		void
	 */
	private function addCData( string $text ): void
	{
		$node		= dom_import_simplexml( $this );
		$document	= $node->ownerDocument;
		$node->appendChild( $document->createCDATASection( $text ) );
	}
}
