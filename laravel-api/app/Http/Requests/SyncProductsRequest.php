<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shop_domain' => [
                'required',
                'string',
                'exists:shops,shop_domain',
                'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_domain.required' => 'Shop domain is required',
            'shop_domain.exists' => 'Shop not found',
            'shop_domain.regex' => 'Invalid shop domain format',
        ];
    }
}
