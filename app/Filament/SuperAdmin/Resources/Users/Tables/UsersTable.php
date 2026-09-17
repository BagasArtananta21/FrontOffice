<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use App\Models\User;
use App\Filament\Support\SweetAlert;
use Illuminate\Database\QueryException;


class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('nip')
                    ->label('NIP')
                    ->placeholder('—'),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'super_admin' ? 'Super Admin' : 'Admin FO')
                    ->color(fn (string $state) => $state === 'super_admin' ? 'secondary' : 'info'),

                TextColumn::make('opd.nama_opd')
                    ->label('OPD')
                    ->placeholder('—'),

                IconColumn::make('aktif')
                    ->label('Aktif'),
                    
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options([
                        'admin_fo' => 'Admin FO',
                        'super_admin' => 'Super Admin',
                    ]),

                TernaryFilter::make('aktif')->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(null)
                    ->using(function (User $record, Array $data, $livewire, EditAction $action){
                        try {
                            $record->forceFill($data)->save();
                            return $record;

                        } catch (QueryException $e){
                            report($e);
                            SweetAlert::error(
                                $livewire,
                                'Gagal memperbarui Pengguna',
                                'Terjadi kesalahan saat memperbarui Pengguna. Silakan coba lagi'
                            );
                            $action->halt();
                        }
                    })
                    ->after(function ($livewire, User $record){
                        if(! $record->wasChanged()){
                            SweetAlert::info(
                                $livewire,
                                'Tidak ada perubahan',
                                'Data Pengguna tetap sama seperti sebelumnya.');
                            return;
                        }

                        if($record->wasChanged('aktif') && ! $record->aktif){
                            SweetAlert::warning(
                                $livewire,
                                'Pengguna dinonaktifkan',
                                "Data Pengguna {$record->nama_opd} telah dinonaktifkan, Pengguna tidak bisa login sampai data diaktifkan kembali."
                            );
                            return;
                        }

                        SweetAlert::success(
                            $livewire,
                            'Perubahan tersimpan',
                            "Data {$record->nama_opd} sudah diperbarui."
                        );
                    })
            ]);
    }
}
