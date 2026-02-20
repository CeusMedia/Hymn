<?php
class Hymn_IntegrationTest_HymnResponse
{
	protected int $code		= 0;
	protected array $output	= [];

	public function __construct( int $code = 0, array $output = [] )
	{
		$this->code		= $code;
		$this->output	= $output;
	}
	public function countLines(): int
	{
		return count( $this->output );
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function getFirstLine(): string
	{
		return $this->output[0] ?? '';
	}

	public function getLastLine(): string
	{
		return array_reverse( $this->output )[0] ?? '';
	}

	public function getLines(): array
	{
		return $this->output;
	}

	public function hasLines(): bool
	{
		return 0 !== count( $this->output );
	}

	public function isFailure(): bool
	{
		return $this->code !== 0;
	}

	public function isSuccess(): bool
	{
		return !$this->isFailure();
	}
}