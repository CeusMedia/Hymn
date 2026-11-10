<?php
declare(strict_types=1);

/**
 *	Generates the command reference of Hymn as Markdown pages for MkDocs.
 *
 *	Reads the command overview of the English help text (src/locales/en/command/help.txt):
 *	sections, synopsis and description of each command, explanation blocks and global options.
 *	Reads the command classes (src/classes/Command/) by reflection: command specific options.
 *	Writes one page per command into doc/Manual/commands/, an overview page (doc/Manual/commands/index.md)
 *	and replaces the command part of the navigation in tool/mkdocs.yml (between marker comments).
 *	Details written by hand in tool/docs/commands/<command>.md are included in the command page.
 *
 *	Commands having a class but no entry in help text (and vice versa) are reported as warnings.
 *
 *	Usage: php tool/docs/generate-commands.php
 */

$rootPath		= dirname( __DIR__, 2 ).'/';
$helpFile		= $rootPath.'src/locales/en/command/help.txt';
$classPath		= $rootPath.'src/classes/';
$targetPath		= $rootPath.'doc/Manual/commands/';
$detailsPath	= __DIR__.'/commands/';
$configFile		= $rootPath.'tool/mkdocs.yml';
$sourceUrl		= 'https://github.com/CeusMedia/Hymn/blob/1.0.x/';

spl_autoload_register( static function( string $className ) use ( $classPath ): void {
	if( !str_starts_with( $className, 'Hymn_' ) )
		return;
	$file	= $classPath.str_replace( '_', '/', substr( $className, 5 ) ).'.php';
	if( file_exists( $file ) )
		require_once $file;
} );

final class CommandDocGenerator
{
	/** @var array<string,string> Explanation blocks of help text, related to commands by pattern on command name */
	protected const BLOCK_COMMANDS	= [
		'Keep Rules'	=> '/^database-keep$/',
		'Type'			=> '/stamp-/',
		'Source'		=> '/stamp-/',
		'Clear Types'	=> '/^app-clear$/',
	];

	/** @var list<string> Blocks of help text not to be shown in command pages */
	protected const IGNORED_BLOCKS	= ['Keywords explained', 'Commands'];

	/** @var list<string> Commands which can be called without Hymn file (see Hymn_Client::$commandWithoutConfig) */
	protected array $commandsWithoutConfig	= [];

	/** @var array<string,array{id: string, title: string, groups: array<string,array{id: string, title: string, intro: string, commands: list<string>}>}> */
	protected array $sections		= [];

	/** @var array<string,array{name: string, synopsis: string, description: string, section: string, group: string, class: ?ReflectionClass<object>}> */
	protected array $commands		= [];

	/** @var array<string,list<string>> Explanation blocks, keyed by title */
	protected array $blocks			= [];

	/** @var array<string,array{syntax: string, description: string, global: bool}> Options, keyed by syntax */
	protected array $options		= [];

	/** @var list<string> */
	protected array $warnings		= [];

	public function __construct(
		protected string $helpFile,
		protected string $classPath,
		protected string $targetPath,
		protected string $detailsPath,
		protected string $sourceUrl,
	)
	{
	}

	public function run( string $configFile ): void
	{
		$this->readCommandsWithoutConfig();
		$this->parseHelp();
		$this->collectClasses();
		$this->clearTarget();
		if( !is_dir( $this->targetPath ) )
			mkdir( $this->targetPath, 0775, TRUE );
		foreach( $this->commands as $name => $command )
			file_put_contents( $this->targetPath.$name.'.md', $this->renderCommand( $command ) );
		file_put_contents( $this->targetPath.'index.md', $this->renderIndex() );
		$this->updateNavigation( $configFile );
		foreach( $this->warnings as $warning )
			fwrite( STDERR, 'Warning: '.$warning.PHP_EOL );
		printf( 'Generated command reference of %d commands in %s'.PHP_EOL, count( $this->commands ), $this->targetPath );
	}

	//  --  PROTECTED  --  //

	protected function clearTarget(): void
	{
		if( !is_dir( $this->targetPath ) )
			return;
		foreach( new DirectoryIterator( $this->targetPath ) as $entry )
			if( $entry->isFile() && 'md' === $entry->getExtension() )
				unlink( $entry->getPathname() );
	}

	/**
	 *	Adds command classes without entry in help text and relates classes to commands.
	 */
	protected function collectClasses(): void
	{
		$iterator	= new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->classPath.'Command/', FilesystemIterator::SKIP_DOTS ) );
		$names		= [];
		foreach( $iterator as $file ){
			/** @var SplFileInfo $file */
			if( 'php' !== $file->getExtension() )
				continue;
			$relative	= substr( $file->getPathname(), strlen( $this->classPath.'Command/' ), -4 );
			$className	= 'Hymn_Command_'.str_replace( '/', '_', $relative );
			if( !class_exists( $className ) )
				continue;
			$class		= new ReflectionClass( $className );
			if( $class->isAbstract() || $class->isInterface() || !$class->implementsInterface( 'Hymn_Command_Interface' ) )
				continue;
			$name		= strtolower( str_replace( '/', '-', $relative ) );
			if( 'default' === $name )
				continue;
			$names[]	= $name;
			if( !isset( $this->commands[$name] ) ){
				$this->warnings[]	= sprintf( 'Command "%s" (%s) is missing in help text', $name, $className );
				$this->addCommand( $name, $name, $this->getClassSummary( $class ), 'Z', 'Z1', 'Further commands' );
				$this->sections['Z']['groups']['Z1']['intro']	= 'Commands which exist, but are not listed by `hymn help` (yet).';
			}
			$this->commands[$name]['class']	= $class;
		}
		foreach( array_keys( $this->commands ) as $name )
			if( !in_array( $name, $names, TRUE ) )
				$this->warnings[]	= sprintf( 'Command "%s" is listed in help text, but not implemented', $name );
		ksort( $this->sections );
	}

	protected function addCommand( string $name, string $synopsis, string $description, string $sectionId, string $groupId, string $groupTitle ): void
	{
		if( !isset( $this->sections[$sectionId] ) )
			$this->sections[$sectionId]	= ['id' => $sectionId, 'title' => $groupTitle, 'groups' => []];
		if( !isset( $this->sections[$sectionId]['groups'][$groupId] ) )
			$this->sections[$sectionId]['groups'][$groupId]	= ['id' => $groupId, 'title' => $groupTitle, 'intro' => '', 'commands' => []];
		$this->sections[$sectionId]['groups'][$groupId]['commands'][]	= $name;
		$this->commands[$name]	= [
			'name'			=> $name,
			'synopsis'		=> $synopsis,
			'description'	=> $description,
			'section'		=> $sectionId,
			'group'			=> $groupId,
			'class'			=> NULL,
		];
	}

	protected function getClassSummary( ReflectionClass $class ): string
	{
		$docComment	= (string) $class->getDocComment();
		foreach( explode( "\n", $docComment ) as $line ){
			$line	= trim( preg_replace( '@^\s*(/\*\*|\*/|\*)@', '', $line ) ?? '' );
			if( '' !== $line && '...' !== $line && !str_starts_with( $line, '@' ) )
				return $line;
		}
		return '';
	}

	/**
	 *	Returns command specific options, defined by command class.
	 *	@return		list<array{name: string, syntax: string, default: string, values: string}>
	 */
	protected function getCommandOptions( ReflectionClass $class ): array
	{
		$defaults	= $class->getDefaultProperties();
		$list		= [];
		foreach( $defaults['argumentOptions'] ?? [] as $name => $option ){
			$list[]	= [
				'name'		=> (string) $name,
				'syntax'	=> $this->renderOptionSyntax( (string) $name, (string) ( $option['pattern'] ?? '' ) ),
				'default'	=> $this->renderValue( $option['default'] ?? NULL ),
				'values'	=> implode( ', ', array_map( static fn( $value ) => '`'.$value.'`', (array) ( $option['values'] ?? [] ) ) ),
			];
		}
		return $list;
	}

	/**
	 *	Parses help text: sections (A., B.), groups (A1., A2.), commands, explanation blocks and options.
	 */
	protected function parseHelp(): void
	{
		$lines		= explode( "\n", str_replace( "\r", '', (string) file_get_contents( $this->helpFile ) ) );
		$sectionId	= NULL;
		$groupId	= NULL;
		$block		= NULL;
		$inOptions	= FALSE;
		$isGlobal	= TRUE;
		foreach( $lines as $nr => $line ){
			$next	= $lines[$nr + 1] ?? '';
			$trim	= trim( $line );
			if( 1 === preg_match( '/^([A-Z])\. (.+)$/', $trim, $matches ) && str_starts_with( $next, '====' ) ){
				$sectionId	= $matches[1];
				$groupId	= NULL;
				$block		= NULL;
				$this->sections[$sectionId]	= ['id' => $sectionId, 'title' => $matches[2], 'groups' => []];
				continue;
			}
			if( 1 === preg_match( '/^([A-Z]\d+)\. (.+)$/', $trim, $matches ) && str_starts_with( $next, '----' ) && NULL !== $sectionId ){
				$groupId	= $matches[1];
				$block		= NULL;
				$this->sections[$sectionId]['groups'][$groupId]	= ['id' => $groupId, 'title' => $matches[2], 'intro' => '', 'commands' => []];
				continue;
			}
			if( 'Options:' === $trim ){
				$inOptions	= TRUE;
				continue;
			}
			if( $inOptions ){
				if( 'Options of single commands:' === $trim )										//  following options are command specific
					$isGlobal	= FALSE;
				if( 1 === preg_match( '/^(-\S+(?: \| -\S+)?)\s{2,}(.+)$/', $trim, $matches ) )
					$this->options[$matches[1]]	= ['syntax' => $matches[1], 'description' => $matches[2], 'global' => $isGlobal];
				continue;
			}
			if( 1 === preg_match( '/^- ([a-z][a-z-]*)(.*)$/', $line, $matches ) && NULL !== $groupId && NULL !== $sectionId ){
				$block			= NULL;
				$arguments		= [];
				$description	= [];
				foreach( preg_split( '/\s+/', trim( $matches[2] ) ) ?: [] as $token ){
					if( [] === $description && 1 !== preg_match( '/^[A-Z][a-z]/', $token ) )
						$arguments[]	= $token;
					else
						$description[]	= $token;
				}
				$synopsis	= trim( $matches[1].' '.implode( ' ', array_filter( $arguments ) ) );
				$this->addCommand( $matches[1], $synopsis, implode( ' ', $description ), $sectionId, $groupId, '' );
				continue;
			}
			if( 1 === preg_match( '/^([A-Z][A-Za-z ]+):$/', $trim, $matches ) ){
				if( in_array( $matches[1], self::IGNORED_BLOCKS, TRUE ) ){
					$block	= NULL;
					continue;
				}
				$block	= $matches[1];
				$this->blocks[$block]	= [];
				continue;
			}
			if( '' === $trim || str_starts_with( $trim, '---' ) || str_starts_with( $trim, '===' ) ){
				if( '' === $trim )
					$block	= NULL;
				continue;
			}
			if( NULL !== $block )
				$this->blocks[$block][]	= $trim;
			else if( NULL !== $groupId && NULL !== $sectionId && [] === $this->sections[$sectionId]['groups'][$groupId]['commands'] )
				$this->sections[$sectionId]['groups'][$groupId]['intro']	.= $trim.' ';
		}
		foreach( array_keys( $this->blocks ) as $title )
			if( !isset( self::BLOCK_COMMANDS[$title] ) )
				$this->warnings[]	= sprintf( 'Explanation block "%s" of help text is not related to any command', $title );
	}

	protected function readCommandsWithoutConfig(): void
	{
		$class	= new ReflectionClass( 'Hymn_Client' );
		$this->commandsWithoutConfig	= $class->getStaticPropertyValue( 'commandWithoutConfig' );
	}

	/**
	 *	Renders code within table cell: as HTML, since pipes would be escaped visibly within backticks.
	 */
	protected function renderCodeCell( string $text ): string
	{
		return '<code>'.str_replace( '|', '&#124;', htmlspecialchars( $text ) ).'</code>';
	}

	protected function escapeTable( string $text ): string
	{
		return str_replace( '|', '\\|', $text );
	}

	/**
	 *	Renders explanation block lines ("* | all    All types") as table.
	 *	@param		list<string>	$lines
	 */
	protected function renderBlock( string $title, array $lines ): string
	{
		$rows	= [];
		foreach( $lines as $line ){
			$line	= ltrim( $line, '- ' );
			if( 1 === preg_match( '/^(\S.*?)\s{2,}(.+)$/', $line, $matches ) )
				$rows[]	= '| '.$this->renderCodeCell( $matches[1] ).' | '.$this->escapeTable( $matches[2] ).' |';
			else
				$rows[]	= '| '.$this->renderCodeCell( $line ).' | |';
		}
		return '### '.$title."\n\n| Value | Meaning |\n|-------|---------|\n".implode( "\n", $rows )."\n";
	}

	/**
	 *	@param		array{name: string, synopsis: string, description: string, section: string, group: string, class: ?ReflectionClass<object>}	$command
	 */
	protected function renderCommand( array $command ): string
	{
		$name		= $command['name'];
		$section	= $this->sections[$command['section']];
		$group		= $section['groups'][$command['group']];
		$lines		= [];
		$lines[]	= '---';
		$lines[]	= 'title: '.$name;
		$lines[]	= '---';
		$lines[]	= '';
		$lines[]	= '<!-- Generated by tool/docs/generate-commands.php - do not edit, see tool/docs/commands/'.$name.'.md -->';
		$lines[]	= '';
		$lines[]	= '# '.$name;
		$lines[]	= '';
		$lines[]	= '<p class="cmd-group">'.htmlspecialchars( trim( $section['title'].' › '.$group['title'], ' ›' ) ).'</p>';
		$lines[]	= '';
		if( '' !== $command['description'] )
			$lines[]	= $command['description'].( str_ends_with( $command['description'], '.' ) ? '' : '.' );
		else
			$lines[]	= '<span class="cmd-muted">No description in help text.</span>';
		$lines[]	= '';
		$lines[]	= '```sh';
		$lines[]	= 'hymn '.$command['synopsis'];
		$lines[]	= '```';
		$lines[]	= '';

		if( NULL === $command['class'] ){
			$lines[]	= '!!! warning "Not implemented"';
			$lines[]	= '    This command is listed in the help text, but there is no command class yet.';
			$lines[]	= '';
		}
		else if( in_array( $name, $this->commandsWithoutConfig, TRUE ) ){
			$lines[]	= '!!! note "No Hymn file needed"';
			$lines[]	= '    This command can be run outside of a project, without a Hymn file.';
			$lines[]	= '';
		}

		$flags	= $this->renderFlags( $command['synopsis'] );
		$specific	= NULL !== $command['class'] ? $this->getCommandOptions( $command['class'] ) : [];
		if( '' !== $flags || [] !== $specific ){
			$lines[]	= '## Options';
			$lines[]	= '';
			if( '' !== $flags ){
				$lines[]	= $flags;
				$lines[]	= '';
			}
			if( [] !== $specific ){
				$lines[]	= 'Options of this command:';
				$lines[]	= '';
				$lines[]	= '| Option | Default | Values |';
				$lines[]	= '|--------|---------|--------|';
				foreach( $specific as $option )
					$lines[]	= '| '.$this->renderCodeCell( $option['syntax'] ).' | '.$option['default'].' | '.$option['values'].' |';
				$lines[]	= '';
			}
			$lines[]	= 'See [global options](index.md#global-options) for all options.';
			$lines[]	= '';
		}

		$blocks	= [];
		foreach( self::BLOCK_COMMANDS as $title => $pattern )
			if( isset( $this->blocks[$title] ) && 1 === preg_match( $pattern, $name ) )
				$blocks[]	= $this->renderBlock( $title, $this->blocks[$title] );
		if( [] !== $blocks ){
			$lines[]	= '## Arguments';
			$lines[]	= '';
			$lines[]	= implode( "\n", $blocks );
		}

		$detailsFile	= $this->detailsPath.$name.'.md';
		if( file_exists( $detailsFile ) ){
			$lines[]	= trim( (string) file_get_contents( $detailsFile ) );
			$lines[]	= '';
		}

		if( NULL !== $command['class'] ){
			$file		= substr( (string) $command['class']->getFileName(), strlen( dirname( $this->classPath, 2 ) ) + 1 );
			$lines[]	= '---';
			$lines[]	= '';
			$lines[]	= ':material-file-code-outline: Implemented in [`'.$command['class']->getName().'`]('.$this->sourceUrl.$file.')';
			$lines[]	= '';
		}
		return implode( "\n", $lines );
	}

	/**
	 *	Renders short flags of synopsis, like [-dqv], as list with descriptions of global options.
	 */
	protected function renderFlags( string $synopsis ): string
	{
		if( 1 !== preg_match( '/\[-([a-z]+)\]/', $synopsis, $matches ) )
			return '';
		$rows	= [];
		foreach( str_split( $matches[1] ) as $letter ){
			$description	= '';
			$syntax			= '-'.$letter;
			foreach( $this->options as $option ){
				if( str_starts_with( $option['syntax'], '-'.$letter.' ' ) ){
					$syntax			= $option['syntax'];
					$description	= $option['description'];
				}
			}
			$rows[]	= '| '.$this->renderCodeCell( $syntax ).' | '.$this->escapeTable( $description ).' |';
		}
		return "| Flag | Meaning |\n|------|---------|\n".implode( "\n", $rows );
	}

	protected function renderIndex(): string
	{
		$lines		= [];
		$lines[]	= '---';
		$lines[]	= 'title: Commands';
		$lines[]	= '---';
		$lines[]	= '';
		$lines[]	= '<!-- Generated by tool/docs/generate-commands.php - do not edit -->';
		$lines[]	= '';
		$lines[]	= '# Command reference';
		$lines[]	= '';
		$lines[]	= 'All commands of Hymn, as listed by `hymn help`. Commands are called within the project folder,';
		$lines[]	= 'which holds the Hymn file `.hymn` – except those marked as working without Hymn file.';
		$lines[]	= '';
		$lines[]	= '```sh';
		$lines[]	= 'hymn COMMAND [OPTION]* [ARGUMENT]*';
		$lines[]	= '```';
		$lines[]	= '';
		foreach( $this->sections as $section ){
			$lines[]	= '## '.$section['title'];
			$lines[]	= '';
			foreach( $section['groups'] as $group ){
				if( $group['title'] !== $section['title'] ){
					$lines[]	= '### '.$group['title'];
					$lines[]	= '';
				}
				if( '' !== trim( $group['intro'] ) ){
					$lines[]	= trim( $group['intro'] );
					$lines[]	= '';
				}
				$lines[]	= '| Command | Description |';
				$lines[]	= '|---------|-------------|';
				foreach( $group['commands'] as $name ){
					$command	= $this->commands[$name];
					$badge		= NULL === $command['class'] ? ' <span class="cmd-badge cmd-badge--missing">not implemented</span>' : '';
					$lines[]	= '| ['.$this->renderCodeCell( $command['synopsis'] ).']('.$name.'.md)'.$badge.' | '.$this->escapeTable( $command['description'] ).' |';
				}
				$lines[]	= '';
			}
		}
		$lines[]	= '## Global options';
		$lines[]	= '';
		$lines[]	= 'Options can be given anywhere after the command. Short flags can be combined, eg. `-dv` for `-d -v`.';
		$lines[]	= '';
		$lines[]	= '| Option | Meaning |';
		$lines[]	= '|--------|---------|';
		foreach( $this->options as $option )
			if( $option['global'] )
				$lines[]	= '| '.$this->renderCodeCell( $option['syntax'] ).' | '.$this->escapeTable( $option['description'] ).' |';
		$lines[]	= '';
		$extra	= trim( (string) @file_get_contents( $this->detailsPath.'index.md' ) );
		if( '' !== $extra ){
			$lines[]	= $extra;
			$lines[]	= '';
		}
		return implode( "\n", $lines );
	}

	protected function renderOptionSyntax( string $name, string $pattern ): string
	{
		$syntax	= trim( $pattern, '/' );
		$syntax	= (string) preg_replace( '/^\^|\$$/', '', $syntax );
		$syntax	= (string) preg_replace( '/\(\\\\S[*+]\)/', strtoupper( $name ), $syntax );
		$syntax	= str_replace( ['^', '$'], '', $syntax );
		if( 1 === preg_match( '/^\((.+)\)$/', $syntax, $matches ) )									//  remove group around alternatives
			$syntax	= $matches[1];
		return '' !== $syntax ? str_replace( '|', ', ', $syntax ) : '--'.$name;
	}

	protected function renderValue( mixed $value ): string
	{
		return match( TRUE ){
			NULL === $value		=> '–',
			TRUE === $value		=> '`yes`',
			FALSE === $value	=> '`no`',
			default				=> $this->renderCodeCell( (string) $value ),
		};
	}

	protected function updateNavigation( string $configFile ): void
	{
		$nav	= ['    - commands/index.md'];
		foreach( $this->sections as $section ){
			foreach( $section['groups'] as $group ){
				$nav[]	= '    - "'.$group['title'].'":';
				foreach( $group['commands'] as $name )
					$nav[]	= '      - "'.$name.'": commands/'.$name.'.md';
			}
		}
		$config	= (string) file_get_contents( $configFile );
		$config	= (string) preg_replace(
			'/(    # commands-nav:start\n).*?(    # commands-nav:end)/s',
			'${1}'.implode( "\n", $nav )."\n".'${2}',
			$config
		);
		file_put_contents( $configFile, $config );
	}
}

( new CommandDocGenerator( $helpFile, $classPath, $targetPath, $detailsPath, $sourceUrl ) )->run( $configFile );
