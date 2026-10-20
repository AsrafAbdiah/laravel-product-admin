<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductRequest extends FormRequest
{
    /**
     * Access is already restricted to admins by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:enabled,disabled'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * The validated filters, with the page size defaulted.
     *
     * @return array{status: ?string, category_id: ?int, search: ?string, per_page: int}
     */
    public function filters(): array
    {
        return [
            'status' => $this->validated('status'),
            'category_id' => $this->validated('category_id') ? (int) $this->validated('category_id') : null,
            'search' => $this->validated('search'),
            'per_page' => (int) ($this->validated('per_page') ?? 15),
        ];
    }
}
