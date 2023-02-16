<?php

namespace StellarWP\Models;

use StellarWP\Models\Tests\BadInvalidArgumentException;
use StellarWP\Models\Tests\GoodInvalidArgumentException;
use StellarWP\Models\Tests\ModelsTestCase;

class ConfigTest extends ModelsTestCase {
	/**
	 * @test
	 */
	public function should_set_hook_prefix() {
		$this->assertEquals( 'test_', Config::getHookPrefix() );
	}

	/**
	 * @test
	 */
	public function should_set_exception_when_exception_is_valid() {
		Config::setInvalidArgumentException( GoodInvalidArgumentException::class );

		$this->assertEquals( GoodInvalidArgumentException::class, Config::getInvalidArgumentException() );
	}

	/**
	 * @test
	 */
	public function should_not_set_exception_when_exception_is_invalid() {

		try {
			Config::setInvalidArgumentException( BadInvalidArgumentException::class );
		} catch ( \Exception $e ) {
			$this->assertEquals( \InvalidArgumentException::class, get_class( $e ) );
		}

		$this->assertEquals( \InvalidArgumentException::class, Config::getInvalidArgumentException() );
	}
}
