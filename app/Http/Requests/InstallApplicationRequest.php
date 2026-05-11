<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'install_fresh' => ['nullable', 'boolean'],
            'project_title' => ['required', 'string', 'max:255'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'tenancy_base_domain' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'default_company_name' => ['required', 'string', 'max:255'],
        ];
    }
}