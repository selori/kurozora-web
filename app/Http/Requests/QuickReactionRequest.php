<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickReactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reaction' => ['bail', 'required', 'integer', 'in:-1,0,1'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'reaction.in' => 'The reaction must be -1 (negative), 0 (neutral), or 1 (positive).',
        ];
    }
}
