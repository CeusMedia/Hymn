<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	Reader for local module XML files.
 *
 *	Copyright (c) 2012-2024 Christian Würker (ceusmedia.de)
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
 *	along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2012-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */

use Hymn_Structure_Module_Author as AuthorDefinition;
use Hymn_Structure_Module_Company as CompanyDefinition;
use Hymn_Structure_Module_Config as ConfigDefinition;
use Hymn_Structure_Module_Deprecation as DeprecationDefinition;
use Hymn_Structure_Module_File as FileDefinition;
use Hymn_Structure_Module_Hook as HookDefinition;
use Hymn_Structure_Module_Job as JobDefinition;
use Hymn_Structure_Module_License as LicenseDefinition;
use Hymn_Structure_Module_Link as LinkDefinition;
use Hymn_Structure_Module_Relation as RelationDefinition;
use Hymn_Structure_Module_SQL as SqlDefinition;
use Hymn_Structure_Module_Version as VersionDefinition;

/**
 *	Reader for local module XML files.
 *	@category		Library
 *	@package		Hymn.Structure.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2012-2024 Christian Würker (ceusmedia.de)
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
class Hymn_Module_Reader2
{
	/**
	 *	Load module data object from module XML file statically.
	 *	@static
	 *	@access		public
	 *	@param		string		$filePath		File path to module XML file
	 *	@param		string		$id				Module ID
	 *	@return		Hymn_Structure_Module		Module data object
	 *	@throws		RuntimeException			if XML file did not pass validation
	 *	@throws		Exception            		if XML file could not been loaded and parsed
	 *	@throws		Exception            		if SQL update script is missing version
	 */
	public static function load( string $filePath, string $id ): Hymn_Structure_Module
	{
		if( !file_exists( $filePath ) )
			throw new RuntimeException( 'Module file "'.$filePath.'" is not existing' );

		$validator	= new Hymn_Tool_XML_Validator();
		if( !$validator->validateFile( $filePath ) )
			throw new RuntimeException( 'XML file of module "'.$id.'" is invalid: '.$validator->getErrorMessage().' in line '.$validator->getErrorLine() );

		$xml	= new Hymn_Tool_XML_Element( file_get_contents( $filePath ) );
		$module	= new Hymn_Structure_Module( $id, (string) $xml->version, $filePath );
		self::decorateObjectWithBasics( $module, $xml );
		self::decorateObjectWithFrameworks( $module, $xml );
		self::decorateObjectWithLog( $module, $xml );
		self::decorateObjectWithFiles( $module, $xml );
		self::decorateObjectWithAuthors( $module, $xml );
		self::decorateObjectWithCompanies( $module, $xml );
		self::decorateObjectWithLinks( $module, $xml );
		self::decorateObjectWithHooks( $module, $xml );
		self::decorateObjectWithJobs( $module, $xml );
		self::decorateObjectWithDeprecation( $module, $xml );
		self::decorateObjectWithConfig( $module, $xml );
		self::decorateObjectWithRelations( $module, $xml );
		self::decorateObjectWithLicenses( $module, $xml );
		self::decorateObjectWithSql( $module, $xml );
		return $module;
	}

	/**
	 *	Read module XML file and return module data object.
	 *	@access		public
	 *	@param		string		$filePath		File path to module XML file
	 *	@param		string		$id				Module ID
	 *	@return		object						Module data object
	 *	@throws		Exception
	 */
	public function read( string $filePath, string $id ): object
	{
		return self::load( $filePath, $id );
	}

	//  --  PROTECTED  --  //

	/**
	 *	@param		Hymn_Tool_XML_Element	$node
	 *	@param		string			$attribute
	 *	@param		mixed			$default
	 *	@return		array|NULL
	 */
	protected static function castNodeAttributesToArray( Hymn_Tool_XML_Element $node, string $attribute, mixed $default = NULL ): array|NULL
	{
		if( !$node->hasAttribute( $attribute ) )
			return $default ?? [];
		$value	= $node->getAttribute( $attribute );
		return preg_split( '/\s*,\s*/', trim( $value ) ) ?: [];
	}

	/**
	 *	@param		Hymn_Tool_XML_Element	$node
	 *	@param		string			$attribute
	 *	@param		mixed			$default
	 *	@return		bool|NULL
	 */
	protected static function castNodeAttributesToBool( Hymn_Tool_XML_Element $node, string $attribute, mixed $default = NULL ): bool|NULL
	{
		if( !$node->hasAttribute( $attribute ) )
			return $default ?? FALSE;
		$value	= $node->getAttribute( $attribute );
		return in_array( strtolower( $value ), ['true', 'yes', '1', TRUE] );
	}

	/**
	 *	@param		Hymn_Tool_XML_Element		$node
	 *	@param		string			$attribute
	 *	@param		mixed			$default
	 *	@return		int|NULL
	 */
	protected static function castNodeAttributesToInt( Hymn_Tool_XML_Element $node, string $attribute, mixed $default = NULL ): int|NULL
	{
		if( !$node->hasAttribute( $attribute ) )
			return $default ?? 0;
		$value	= $node->getAttribute( $attribute );
		return (int) $value;
	}

	/**
	 *	@param		Hymn_Tool_XML_Element		$node
	 *	@param		string			$attribute
	 *	@param		mixed			$default
	 *	@return		string|NULL
	 */
	protected static function castNodeAttributesToString( Hymn_Tool_XML_Element $node, string $attribute, mixed $default = NULL ): string|NULL
	{
		if( !$node->hasAttribute( $attribute ) )
			return $default ?? '';
		$value	= $node->getAttribute( $attribute );
		return trim( $value );
	}

	/**
	 *	Decorates module object by author information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithAuthors( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->author )																			//  no author nodes existing
			return FALSE;
		foreach( $xml->author as $author ){															//  iterate author nodes
			$module->authors[]	= new AuthorDefinition(
				(string) $author,
				self::castNodeAttributesToString( $author, 'email' ),
				self::castNodeAttributesToString( $author, 'site' )
			);
		}
		return TRUE;
	}

	protected static function decorateObjectWithBasics( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): void
	{
		$module->version			= new VersionDefinition( (string) $xml->version );
		$module->title				= (string) $xml->title;
		$module->category			= (string) $xml->category;
		$module->description		= (string) $xml->description;
		$module->price				= (string) $xml->price;
		if( NULL !== $module->install && isset( $xml->version ) ){
			$module->install->type		= self::castNodeAttributesToString( $xml->version, 'install-type' );
			$module->install->date		= self::castNodeAttributesToString( $xml->version, 'install-date' );
			$module->install->source	= self::castNodeAttributesToString( $xml->version, 'install-source' );
		}
	}

	/**
	 *	Decorates module object by company information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithCompanies( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->company )																		//  no company nodes existing
			return FALSE;
		foreach( $xml->company as $company ){														//  iterate company nodes
			$module->companies[]	= new CompanyDefinition(
				(string) $company,
				self::castNodeAttributesToString( $company, 'email' ),
				self::castNodeAttributesToString( $company, 'site' )
			);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by config information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithConfig( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->config )																			//  no config nodes existing
			return FALSE;
		foreach( $xml->config as $pair ){															//  iterate config nodes
			$title		= self::castNodeAttributesToString( $pair, 'title' );
			$type		= (string) self::castNodeAttributesToString( $pair, 'type' );
			$type		= trim( strtolower( $type ) );
			$key		= (string) self::castNodeAttributesToString( $pair, 'name' );
			$info		= self::castNodeAttributesToString( $pair, 'info' );
			$title		= ( !$title && $info ) ? $info : $title;
			$value		= (string) $pair;
			if( in_array( $type, ['boolean', 'bool'] ) )											//  value is boolean
				$value	= !in_array( strtolower( $value ), ['no', 'false', '0', ''] );				//  value is not negative

			$item				= new ConfigDefinition( trim( $key ), $value, $type , $title );		//  container for config entry

			$item->values		= self::castNodeAttributesToArray( $pair, 'values' );
			$item->mandatory	= (bool) self::castNodeAttributesToBool( $pair, 'mandatory', FALSE );
			/** @var bool|string $protected */
			$protected			= self::castNodeAttributesToString( $pair, 'protected' );
			$item->protected	= $protected;
			$module->config[$key]	= $item;
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by deprecation information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithDeprecation( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->deprecation )																	//  deprecation node is not existing
			return FALSE;
		$module->deprecation	= new DeprecationDefinition(										//  note deprecation object
			(string) $xml->deprecation,																//  ... with message
			self::castNodeAttributesToString( $xml->deprecation, 'url' )					//  ... with follow-up URL, if set
		);
		return TRUE;
	}

	/**
	 *	Decorates module object by file information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 *	@todo		rethink the defined map of paths
	 */
	protected static function decorateObjectWithFiles( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->files )
			return FALSE;
		$map	= [
			'class'		=> 'classes',
			'locale'	=> 'locales',
			'template'	=> 'templates',
			'style'		=> 'styles',
			'script'	=> 'scripts',
			'image'		=> 'images',
			'file'		=> 'files',
		];
		foreach( $map as $source => $target ){														//  iterate files
			foreach( $xml->files->$source as $file ){
				$item	= new FileDefinition( (string) $file );
//				$item	= (object) array( 'file' => (string) $file );
				foreach( $file->getAttributes() as $key => $value )
					$item->$key	= $value;
				$module->files->{$target}[]	= $item;
			}
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by framework support information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithFrameworks( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		/** @var string $frameworks */
		$frameworks	= self::castNodeAttributesToString( $xml, 'frameworks', '' );
		if( '' === trim( $frameworks ) )
			return FALSE;
		/** @var array $list */
		$list		= preg_split( '/\s*(,|\|)\s*/', $frameworks );
		foreach( $list as $listItem ){
			/** @var array $parts */
			$parts	= preg_split( '/\s*(:|@)\s*/', $listItem );
			if( count( $parts ) < 2 )
				$parts[1]	= '*';
			$module->frameworks[(string) $parts[0]]	= (string) $parts[1];
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by hook information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithHooks( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->hook )																			//  hook node is not existing
			return FALSE;
		foreach( $xml->hook as $hook ){																//  iterate hook nodes
			/** @var string $resource */
			$resource	= self::castNodeAttributesToString( $hook, 'resource' );
			/** @var string $event */
			$event		= self::castNodeAttributesToString( $hook, 'event' );
			$content	= trim( (string) $hook, ' ' );
			if( preg_match( '/\n/', $content ) ){
				throw new RuntimeException( vsprintf( 'Hooks with inline function are not supported anymore, but found in module %s', [$module->id] ) );
			}
			$module->hooks[$resource][$event][]	= new HookDefinition( $content, $resource, $event,
				(int) self::castNodeAttributesToInt( $hook, 'level', 5 ),
			);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by job information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithJobs( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->job )																			//  hook node is not existing
			return FALSE;
		foreach( $xml->job as $job ){																//  iterate job nodes
			$callable		= explode( '::', (string) $job, 2 );
			$module->jobs[]	= new JobDefinition(
				(string) self::castNodeAttributesToString( $job, 'id' ),
				$callable[0],
				$callable[1],
				(string) self::castNodeAttributesToString( $job, 'commands' ),
				(string) self::castNodeAttributesToString( $job, 'arguments' ),
				(array) self::castNodeAttributesToArray( $job, 'mode', [] ),
				(string) self::castNodeAttributesToString( $job, 'interval' ),
				(bool) self::castNodeAttributesToBool( $job, 'multiple', FALSE ),
				(string) self::castNodeAttributesToString( $job, 'deprecated' ),
				(string) self::castNodeAttributesToString( $job, 'disabled' )
			);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by license information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithLicenses( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->license )																		//  no license nodes existing
			return FALSE;
		foreach( $xml->license as $license ){														//  iterate license nodes
			$module->licenses[]	= new LicenseDefinition(
				(string) $license,
				self::castNodeAttributesToString( $license, 'title' ),
				self::castNodeAttributesToString( $license, 'source' )
			);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by link information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithLinks( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->link )																			//  no link nodes existing
			return FALSE;
		foreach( $xml->link as $link ){																//  iterate link nodes
			$language	= NULL;
			if( $link->hasAttribute( 'lang', 'xml' ) )
				$language	= $link->getAttribute( 'lang', 'xml' );
			$label		= (string) $link;
			$module->links[]	= new LinkDefinition(
				self::castNodeAttributesToString( $link, 'parent' ),
				self::castNodeAttributesToString( $link, 'access' ),
				$language,
				self::castNodeAttributesToString( $link, 'path', $label ),
				self::castNodeAttributesToString( $link, 'link' ),
				self::castNodeAttributesToInt( $link, 'rank', 10 ),
				$label,
				self::castNodeAttributesToString( $link, 'icon' )
			);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by log information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithLog( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->log )																			//  no log nodes existing
			return FALSE;
		foreach( $xml->log as $entry ){																//  iterate version log entries if available
			if( $entry->hasAttribute( 'version' ) )											//  only if log entry is versioned
				$module->version->log[]	= new Hymn_Structure_Module_Log(							//  append version log entry
					$entry->getAttribute( 'version' )	,										//  extract entry version
					(string) $entry																	//  extract entry note
				);
		}
		return TRUE;
	}

	/**
	 *	Decorates module object by relation information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean							TRUE if data object of module has been decorated
	 */
	protected static function decorateObjectWithRelations( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->relations )																		//  no relation nodes existing
			return FALSE;																			//  do nothing
		if( $xml->relations->needs )																//  if needed modules are defined
			foreach( $xml->relations->needs as $moduleName ){										//  iterate list if needed modules
				$type	= (string) self::castNodeAttributesToString( $moduleName, 'type' );
				$module->relations->needs[(string) $moduleName]		= new RelationDefinition(		//  note relation
					(string) $moduleName,															//  ... with module ID
					match( $type ){																	//  ... with relation type
						'module'	=> RelationDefinition::TYPE_MODULE,
						'package'	=> RelationDefinition::TYPE_PACKAGE,
						default		=> RelationDefinition::TYPE_UNKNOWN,
					},
					(string) self::castNodeAttributesToString( $moduleName, 'source' ),		//  ... with module source, if set
					(string) self::castNodeAttributesToString( $moduleName, 'version' ),	//  ... with version, if set
					'needs'																	//  ... as needed
				);
			}
		if( $xml->relations->supports )																//  if supported modules are defined
			foreach( $xml->relations->supports as $moduleName ){									//  iterate list if supported modules
				$type	= (string) self::castNodeAttributesToString( $moduleName, 'type' );
				$module->relations->supports[(string) $moduleName]	= new RelationDefinition(		//  note relation
					(string) $moduleName,															//  ... with module ID
					match( $type ){																	//  ... with relation type
						'module'	=> RelationDefinition::TYPE_MODULE,
						'package'	=> RelationDefinition::TYPE_PACKAGE,
						default		=> RelationDefinition::TYPE_UNKNOWN,
					},
					(string) self::castNodeAttributesToString( $moduleName, 'source' ),		//  ... with module source, if set
					(string) self::castNodeAttributesToString( $moduleName, 'version' ),	//  ... with version, if set
					'supports'																//  ... as supported
				);
			}
		return TRUE;
	}

	/**
	 *	Decorates module object by SQL information, if set.
	 *	@access		protected
	 *	@param		Hymn_Structure_Module	$module		Data object of module
	 *	@param		Hymn_Tool_XML_Element	$xml		XML tree object of module created by ::load
	 *	@return		boolean					TRUE if data object of module has been decorated
	 *	@throws		Exception				if update script is missing version
	 */
	protected static function decorateObjectWithSql( Hymn_Structure_Module $module, Hymn_Tool_XML_Element $xml ): bool
	{
		if( !$xml->sql )																			//  no sql nodes existing
			return FALSE;
		foreach( $xml->sql as $sql ){																//  iterate sql nodes
			/** @var string $event */
			$event		= self::castNodeAttributesToString( $sql, 'on' );
			/** @var string $to */
			$to			= self::castNodeAttributesToString( $sql, 'version-to' );
			/** @var string $version */
			$version	= self::castNodeAttributesToString( $sql, 'version', $to );	//  @todo: remove fallback
			/** @var string $type */
			$type		= self::castNodeAttributesToString( $sql, 'type', '*' );
			if( $event === 'update' && !$version )
				throw new Exception( 'SQL type "update" needs attribute "version"' );

			foreach( explode( ',', $type ) as $type ){
				$key	= $event.'@'.$type;
				if( $event == "update" )
					$key	= $event.":".$version.'@'.$type;
				$module->sql[$key] = new SqlDefinition(
					$event,
					$version,
					$type,
					(string) $sql
				);
			}
		}
		return TRUE;
	}
}
