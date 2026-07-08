<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelTeacherVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->role ?? session('user')?->role) === 'admin';
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 5 karakter.',
            'alasan.max' => 'Alasan pembatalan maksimal 1000 karakter.',
        ];
    }
}
