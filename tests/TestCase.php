<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Laravel 11+ provides createApplication() on the base TestCase, so the
 * Laravel 8 "CreatesApplication" trait is no longer needed.
 */
abstract class TestCase extends BaseTestCase
{
    //
}
