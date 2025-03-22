<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestItemsRequest extends FormRequest
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
            'test_item_title'       => 'string|required|max:255',
            'test_item_content'     => 'string|nullable|max:65535',
        ];
    }
}
