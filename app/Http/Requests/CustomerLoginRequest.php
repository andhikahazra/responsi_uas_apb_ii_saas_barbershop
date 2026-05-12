<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            /**
             * @example test@user.com
             */
            'email' => ['required', 'email'],

            /**
             * @example password
             */
            'password' => ['required'],
        ];
    }
}
