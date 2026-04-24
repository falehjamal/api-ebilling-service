<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->app->environment('testing')) {
            return;
        }

        $name = (string) $this->app['config']->get('database.default');
        $this->app['config']->set(
            'database.connections.legacy',
            $this->app['config']->get("database.connections.{$name}"),
        );
    }
}
