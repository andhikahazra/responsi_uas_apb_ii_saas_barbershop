<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            /**
             * @example staff@barber.com
             */
            'email' => ['required', 'email'],

            /**
             * @example password
             */
            'password' => ['required'],
        ];
    }
}
