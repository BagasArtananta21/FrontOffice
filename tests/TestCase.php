<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $database = config('database.connections.'.config('database.default').'.database');

        if ($database !== 'projectmagang_test') {
            throw new RuntimeException("Tes menolak jalan: database aktif [{$database}], bukan projectmagang_test.");
        }
    }
}
