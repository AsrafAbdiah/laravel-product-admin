<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Support\TeamRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', TeamRule::unique('categories', 'name')],
        ];
    }
}
