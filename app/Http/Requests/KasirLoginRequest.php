<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KasirLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            /**
             * @example kasir@barber.com
             */
            'email' => ['required', 'email'],

            /**
             * @example password
             */
            'password' => ['required'],
        ];
    }
}
