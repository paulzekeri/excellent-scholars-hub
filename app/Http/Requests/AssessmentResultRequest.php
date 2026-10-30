<?php

namespace App\Http\Requests;

use App\Models\AssessmentSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The result of an assessment (note 13, phase 3), shared by the school and the
 * tutor who ran the call. "Completed" needs an outcome; a no-show needs nothing
 * beyond the result itself.
 */
class AssessmentResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::in([
                AssessmentSession::STATUS_COMPLETED,
                AssessmentSession::STATUS_NO_SHOW,
            ])],
            'outcome' => [
                'nullable',
                'required_if:result,' . AssessmentSession::STATUS_COMPLETED,
                Rule::in(array_keys(AssessmentSession::OUTCOMES)),
            ],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'focus_areas' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'recommended_class_id' => ['nullable', 'exists:classes,id'],
            'recommended_level' => ['nullable', 'string', 'max:255'],
            'report' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outcome.required_if' => 'Choose an outcome when the assessment was completed.',
        ];
    }
}
