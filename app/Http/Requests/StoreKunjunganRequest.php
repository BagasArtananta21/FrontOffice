<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Kunjungan;
use App\Rules\SignatureImage;
use Closure;

class StoreKunjunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_hp' => Kunjungan::normalizePhone($this->input('no_hp')),
        ]);
    }

    public function rules(): array{
        return [
            'nama_tamu' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Kunjungan::JENIS_KELAMIN))],
            'no_hp' => ['nullable', 'regex:'.Kunjungan::NO_HP_PATTERN],
            'instansi_asal' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'bidang_id' => [
                'nullable',
                Rule::exists('bidang', 'id')
                    ->where('opd_id', app('current_opd_id'))
                    ->where('aktif', true),
            ],
            'pegawai_tujuan' => ['nullable', 'string', 'max:255'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'tanda_tangan' => ['required', 'string', 'max:500000', new SignatureImage()]
        ];
    }

    public function attributes(): array {
        return [
            'nama_tamu' => 'Nama',
            'jenis_kelamin' => 'Jenis Kelamin',
            'no_hp' => 'Nomor HP',
            'instansi_asal' => 'Instansi Asal',
            'alamat' => 'Alamat',
            'bidang_id' => 'Bidang yang Dituju',
            'pegawai_tujuan' => 'Pegawai yang Dituju',
            'keperluan' => 'Keperluan Kunjungan',
        ];
    }

    public function messages(): array {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh terlalu panjang.',
            'in' => ':attribute tidak valid.',
            'regex' => 'Format :attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan.',
            'integer' => ':attribute harus berupa angka.',
        ];
    }
}
