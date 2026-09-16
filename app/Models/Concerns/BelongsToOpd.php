<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 */
trait BelongsToOpd
{
    protected static function bootBelongsToOpd(): void
    {
        static::addGlobalScope('opd', function (Builder $query) {
            $opdId = static::currentOpdId();

            if ($opdId !== null) {
                $query->where($query->getModel()->getTable().'.opd_id', $opdId);
            } elseif (! static::asSuperAdmin()) {
                $query->whereNull($query->getModel()->getTable().'.opd_id');
            }
        });

        static::creating(function (Model $model) {
            $opdId = static::currentOpdId();

            if ($opdId !== null) {
                if ($model->opd_id !== null && (string) $model->opd_id !== $opdId) {
                    throw new AuthorizationException('OPD pada data tidak sesuai dengan konteks aktif.');
                }

                $model->opd_id = $opdId;
                return;
            }

            if (! static::asSuperAdmin() || empty($model->opd_id)) {
                throw new AuthorizationException('Konteks OPD wajib tersedia saat membuat data.');
            }
        });
    }

    protected static function currentOpdId(): ?string
    {
        if (app()->bound('current_opd_id')) {
            $opdId = app('current_opd_id');

            return $opdId !== null ? (string) $opdId : null;
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->isSuperAdmin() ? null : $user->opd_id;
    }

    protected static function asSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    public function scopeAcrossOpd(Builder $query): Builder
    {
        if (! static::asSuperAdmin()) {
            throw new AuthorizationException('Hanya super admin yang dapat mengakses data lintas OPD.');
        }

        return $query->withoutGlobalScope('opd');
    }
}