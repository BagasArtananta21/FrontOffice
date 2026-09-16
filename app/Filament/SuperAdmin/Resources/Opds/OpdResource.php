<?php

namespace App\Filament\SuperAdmin\Resources\Opds;

use App\Filament\SuperAdmin\Resources\Opds\Pages\CreateOpd;
use App\Filament\SuperAdmin\Resources\Opds\Pages\EditOpd;
use App\Filament\SuperAdmin\Resources\Opds\Pages\ListOpds;
use App\Filament\SuperAdmin\Resources\Opds\Pages\ManageOpds;
use App\Filament\SuperAdmin\Resources\Opds\Schemas\OpdForm;
use App\Filament\SuperAdmin\Resources\Opds\Tables\OpdsTable;
use App\Models\Opd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpdResource extends Resource
{
    protected static ?string $model = Opd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama_opd';

    public static function form(Schema $schema): Schema
    {
        return OpdForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpdsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOpds::route('/'),
        ];
    }
}
