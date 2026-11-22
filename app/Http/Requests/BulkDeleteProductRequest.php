<?php

namespace App\Http\Requests;

use App\Support\TeamRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteProductRequest extends FormRequest
{
    /**
     * Access is already restricted to admins by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                // Only products that exist and are not already deleted.
                TeamRule::exists('products')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one product to delete.',
            'ids.*.exists' => 'One or more selected products do not exist.',
        ];
    }
}
