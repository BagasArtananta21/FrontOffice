<?php

namespace Tests\Feature\Numbering;

use App\Filament\Admin\Resources\NomorSurats\NomorSuratResource;
use App\Filament\SuperAdmin\Resources\FormatNomorSurats\FormatNomorSuratResource;
use Tests\TestCase;

class LetterModuleHiddenTest extends TestCase
{
    public function test_the_letter_numbering_pages_are_hidden_from_navigation(): void
    {
        $this->assertFalse(NomorSuratResource::shouldRegisterNavigation());
        $this->assertFalse(FormatNomorSuratResource::shouldRegisterNavigation());
    }
}