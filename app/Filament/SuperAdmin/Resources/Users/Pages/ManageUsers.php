<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Model {
                    if (($data['role'] ?? null) === 'super_admin') {
                        $data['opd_id'] = null;
                    }

                    $user = new User();
                    $user->forceFill($data)->save();

                    return $user;
                }),

        ];
    }
}
