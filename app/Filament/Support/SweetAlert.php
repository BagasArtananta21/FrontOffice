<?php

namespace App\Filament\Support;

use Livewire\Component;

class SweetAlert
{
    public static function success(Component $livewire, string $title, string $text = ''): void
    {
        self::fire($livewire, 'success', $title, $text);
    }

    public static function error(Component $livewire, string $title, string $text = ''): void
    {
        self::fire($livewire, 'error', $title, $text);
    }

    public static function warning(Component $livewire, string $title, string $text = ''): void
    {
        self::fire($livewire, 'warning', $title, $text);
    }

    public static function info(Component $livewire, string $title, string $text = ''): void
    {
        self::fire($livewire, 'info', $title, $text);
    }

    private static function fire(Component $livewire, string $icon, string $title, string $text): void
    {
        $livewire->dispatch('swal', [
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
        ]);
    }
}
