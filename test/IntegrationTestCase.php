<?php
class Hymn_IntegrationTest_Case extends PHPUnit\Framework\TestCase
{
	protected ?string $contextKey	= NULL;
	protected function runHymn( string $arguments ): Hymn_IntegrationTest_HymnResponse
	{
		$output	= [];
		exec( __DIR__.'/../hymn.phar '.escapeshellcmd( $arguments ), $output, $resultCode );
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

	protected function createContextFromTemplate( string $templateKey, ?string $contextKey ): Hymn_IntegrationTest_Context
	{
		$baseTargetPath	= __DIR__.'/../drive/tmp/';
		$pathTemplates	= __DIR__.'/templates/';

		$factory	= new Hymn_IntegrationTest_ContextManager( $baseTargetPath );

		if( NULL !== $contextKey && $factory->has( $contextKey ) ){
			$context = $factory->get( $contextKey );
		}
		else{
			$factory->setTemplatePath( $pathTemplates );
			$context	= $factory->createFromTemplate( $templateKey, $contextKey );
		}
		$this->contextKey	= $context->getKey();
		$context->enter();
		return $context;
	}

	protected function isInContext(): bool
	{
		return NULL !== $this->contextKey;
	}
}
