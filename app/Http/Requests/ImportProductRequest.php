<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportProductRequest extends FormRequest
{
    public function authorize(): bool|Response
    {
        return Gate::inspect('import', Product::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // CSV files are often detected as text/plain, so allow "txt" as a
            // content type but still require a .xlsx or .csv file name.
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'extensions:xlsx,csv', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload an Excel (.xlsx) or CSV file.',
            'file.extensions' => 'Upload an Excel (.xlsx) or CSV file.',
        ];
    }
}
