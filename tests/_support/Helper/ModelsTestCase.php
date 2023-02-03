<?php

namespace StellarWP\Models\Tests;

use StellarWP\Models\Config;

class ModelsTestCase extends \Codeception\Test\Unit {
	protected $backupGlobals = false;

	/**
	 * @inheritDoc
	 */
	public function setUp() {
		// before
		parent::setUp();

		Config::setHookPrefix( 'test_' );
	}

	/**
	 * @inheritDoc
	 */
	public function tearDown() {
		Config::reset();

		parent::tearDown();
	}
}
