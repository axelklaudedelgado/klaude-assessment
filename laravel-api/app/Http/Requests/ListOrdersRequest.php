<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListOrdersRequest extends FormRequest
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
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'financial_status' => 'nullable|string',
            'date_from' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from|before_or_equal:today',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->page ?? 1,
            'pageSize' => $this->pageSize ?? 50,
        ]);
    }
}
