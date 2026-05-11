<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallDatabaseTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }
}