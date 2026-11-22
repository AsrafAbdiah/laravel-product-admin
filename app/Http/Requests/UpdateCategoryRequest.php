<?php

namespace App\Http\Requests;

use App\Support\TeamRule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * Same rules as creating, but the unique check ignores the category
     * being edited, so saving it without renaming doesn't fail.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', TeamRule::unique('categories', 'name')->ignore($this->route('category'))],
        ];
    }
}
