<?php

namespace Tests\Feature\Wording;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SourceWordingTest extends TestCase
{
    public function test_app_and_views_use_the_standard_spelling_of_silakan(): void
    {
        $offenders = [];

        foreach ([app_path(), resource_path('views')] as $directory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->getExtension() === 'php' && stripos(file_get_contents($file->getPathname()), 'silahkan') !== false) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $offenders, 'Tulis "silakan" (bentuk baku), bukan "silahkan".');
    }
}
