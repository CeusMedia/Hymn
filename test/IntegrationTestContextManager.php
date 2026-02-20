<?php
class Hymn_IntegrationTest_ContextManager
{
	protected string $basePath	= '';
	protected string $absolutePath	= '';
	protected array $contextKeys	= [];
	protected string $templateKey	= '';
	protected string $pathContexts	= '';
	protected string $pathTemplates	= '';

	public function __construct( string $pathContexts )
	{
		$this->setContextPath( $pathContexts );
	}

	public function createFromTemplate( string $templateKey, ?string $contextKey )
	{
		$contextKey		= $contextKey ?? $this->generateContextKey();

		$templatePath	= $this->pathTemplates.$templateKey;
		if( !file_exists( $templatePath ) )
			throw new RuntimeException( 'Template not found in '.$templatePath );

		if( !file_exists( $this->pathContexts ) )
			mkdir( $this->pathContexts );

		$targetPath	= $this->pathContexts.$contextKey.'/';

		$context	= new Hymn_IntegrationTest_Context( $targetPath );
		$context->setKey( $contextKey );
		$context->setTemplateKey( $templateKey );

		exec( 'cp -Rp '.escapeshellcmd( $templatePath ).' '.$targetPath, $output, $resultCode );
		if( 0 !== $resultCode )
			throw new RuntimeException( 'Copying template failed' );
		$context->setStatus( Hymn_IntegrationTest_Context::STATUS_COPIED );
		return $context;
	}

	public function get( string $contextKey ) : ?Hymn_IntegrationTest_Context
	{
		if( !$this->has( $contextKey ) )
			return NULL;
		return $this->contextKeys[$contextKey];
	}

	public function has( string $contextKey ): bool
	{
		return array_key_exists( $contextKey, $this->contextKeys );
	}

	public function remove( Hymn_IntegrationTest_Context $context ): void
	{
		exec( 'rm -r '.$context->getAbsolutePath() );
		unset( $this->contextKeys[$context->getKey()] );
	}

	public function set( string $contextKey, Hymn_IntegrationTest_Context $context ): self
	{
		if( !$this->has( $contextKey ) )
			$this->contextKeys[$contextKey]	= $context;
		return $this;
	}

	public function setContextPath( string $pathContexts ): self
	{
		$this->pathContexts	= rtrim( $pathContexts, '/' ).'/';
		return $this;
	}

	public function setTemplatePath( string $pathTemplates ): self
	{
		$this->pathTemplates	= $pathTemplates;
		return $this;
	}

	protected function generateContextKey(): string
	{
		return substr( md5( uniqid( mt_rand(), true ) ), 0, 8 );
	}
}