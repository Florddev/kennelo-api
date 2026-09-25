<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\DocumentTypeEnum;
use App\Models\Activity;
use App\Models\ProfessionDocumentRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Seuls les justificatifs prévus par le métier de l'activité se déposent. Un justificatif à durée de validité
 * limitée porte sa date d'échéance.
 */
class StoreActivityDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity $activity */
        $activity = $this->route('activity');
        $requirements = ProfessionDocumentRequirement::query()->where('profession_id', $activity->profession_id)->get();
        $requirement = $requirements->first(
            fn (ProfessionDocumentRequirement $requirement): bool => $requirement->document_type->value === $this->input('document_type'),
        );

        return [
            'document_type' => ['required', Rule::in($requirements->map(
                fn (ProfessionDocumentRequirement $requirement): string => $requirement->document_type->value,
            )->all()), Rule::enum(DocumentTypeEnum::class)],
            'expires_at' => [Rule::requiredIf($requirement?->validity_months !== null), 'nullable', 'date_format:Y-m-d', 'after:today'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.in' => __('activity.document_not_required'),
        ];
    }
}
