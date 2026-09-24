<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Kunjungan;

class StoreKunjunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $noHp = preg_replace('/[^0-9+]/', '', (string) $this->input('no_hp'));

        $this->merge([
            'no_hp' => $noHp === '' ? null : preg_replace('/^(\+62|62)/', '0', $noHp),
        ]);
    }

    public function rules(): array{
        return [
            'nama_tamu' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Kunjungan::JENIS_KELAMIN))],
            'no_hp' => ['nullable', 'regex:/^0[0-9]{8,13}$/'],
            'instansi_asal' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'bidang_id' => [
                'nullable',
                Rule::exists('bidang', 'id')
                    ->where('opd_id', app('current_opd_id'))
                    ->where('aktif', true),
            ],
            'pegawai_id' => [
                'nullable',
                Rule::exists('pegawai', 'id')
                    ->where('opd_id', app('current_opd_id'))
                    ->where('aktif', true),
            ],
            'keperluan' => ['required', 'string', 'max:1000'],
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
            'pegawai_id' => 'Pegawai yang Dituju',
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
