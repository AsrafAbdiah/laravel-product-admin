<?php

namespace App\Http\Requests;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * Same rules as creating, but every field is optional so the
     * endpoint supports partial updates (PATCH) as well as PUT.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => in_array('sometimes', $rules, true) ? $rules : ['sometimes', ...$rules])
            ->all();
    }
}
