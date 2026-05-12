<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * ID Cabang tempat produk dijual
             * @example 1
             */
            'branch_id' => 'required|exists:branches,id',

            /**
             * Nama Produk (misal: Pomade Suavecito)
             * @example Pomade Clay
             */
            'name' => 'required|string|max:255',

            /**
             * Harga jual produk
             * @example 85000
             */
            'price' => 'required|numeric|min:0',

            /**
             * Stok awal produk
             * @example 50
             */
            'stock' => 'required|integer|min:0',
        ];
    }
}
