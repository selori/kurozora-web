<?php

namespace App\Http\Requests;

use App\Enums\RatingStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRatingStyleRequest extends FormRequest
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
            'ratingStyle' => [
                'bail',
                'required',
                'integer',
                Rule::in(array_column(RatingStyle::cases(), 'value')),
            ],
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
            'ratingStyle.in' => 'The rating style must be one of: Quick Reaction (0), Standard (1), Advanced (2), or Detailed (3).',
        ];
    }
}
