<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetailedRatingRequest extends FormRequest
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
            'category_scores' => ['bail', 'required', 'array', 'min:1'],
            'category_scores.*.category_id' => ['bail', 'required', 'integer', 'exists:rating_categories,id'],
            'category_scores.*.score' => ['bail', 'required', 'numeric', 'between:0,10'],
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
            'category_scores.required' => 'At least one category score is required for a detailed rating.',
            'category_scores.*.category_id.exists' => 'One or more selected categories do not exist.',
            'category_scores.*.score.between' => 'Each category score must be between 0 and 10.',
        ];
    }
}
