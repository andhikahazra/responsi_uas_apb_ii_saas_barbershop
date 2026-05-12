<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            /**
             * @example 6
             */
            'customer_id' => 'nullable|exists:users,id',

            /**
             * @example cash
             */
            'payment_method' => 'required|string',

            /**
             * Daftar item (layanan/produk)
             * @example [{"type": "service", "id": 1, "staff_id": 4}, {"type": "product", "id": 1}]
             */
            'items' => 'required|array|min:1',

            'items.*.type' => 'required|in:service,product',
            'items.*.id' => 'required|integer',
            'items.*.staff_id' => 'required_if:items.*.type,service|nullable|exists:users,id',
        ];
    }
}
