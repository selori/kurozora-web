<?php

namespace App\Http\Requests;

use App\Models\MediaRating;
use Illuminate\Foundation\Http\FormRequest;

class AdvancedRatingRequest extends FormRequest
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
            'rating' => ['bail', 'required', 'numeric', 'between:' . MediaRating::MIN_RATING_VALUE . ',' . MediaRating::MAX_RATING_VALUE],
            'description' => ['bail', 'nullable', 'string', 'max:5000'],
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
            'rating.between' => 'The rating must be between 0 and 10.',
        ];
    }
}
