<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeveloperLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            /**
             * @example dev@admin.com
             */
            'email' => ['required', 'email'],

            /**
             * @example password
             */
            'password' => ['required'],
        ];
    }
}
