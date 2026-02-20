<?php
class Hymn_IntegrationTest_Case extends PHPUnit\Framework\TestCase
{
	protected ?string $contextKey	= NULL;
	protected ?Hymn_IntegrationTest_Context $currentContext	= NULL;

	protected function runHymn( string $arguments ): Hymn_IntegrationTest_HymnResponse
	{
		$output	= [];
		$command	= __DIR__.'/../hymn.phar '.escapeshellcmd( $arguments );
/*		if( NULL !== $this->currentContext ){
			print( 'Working in: '.getcwd().PHP_EOL );
			print( 'Command: '.$command.PHP_EOL );
			die;
		}*/

		exec( $command, $output, $resultCode );
		return new Hymn_IntegrationTest_HymnResponse( $resultCode, $output );
	}

	protected function getEnvModeFromBuild(): string
	{
		$envMode	= 'prod';
		$envFile	= __DIR__.'/../../build/.mode';
		if( file_exists( $envFile ) )
			$envMode = file_get_contents( $envFile );
		return $envMode;
	}

	protected function createContextFromTemplate( string $templateKey, ?string $contextKey, bool $force = FALSE ): Hymn_IntegrationTest_Context
	{
		$baseTargetPath	= __DIR__.'/../drive/tmp/';
		$pathTemplates	= __DIR__.'/templates/';

		$factory	= new Hymn_IntegrationTest_ContextManager( $baseTargetPath );

		if( NULL !== $contextKey && $factory->has( $contextKey ) && !$force ){
			$context = $factory->get( $contextKey );
		}
		else{
			$factory->setTemplatePath( $pathTemplates );
			$context	= $factory->createFromTemplate( $templateKey, $contextKey );
		}
		$this->contextKey	= $context->getKey();
		$this->currentContext	= $context;
		$context->enter();
		return $context;
	}

	protected function isInContext(): bool
	{
		return NULL !== $this->contextKey;
	}
}
