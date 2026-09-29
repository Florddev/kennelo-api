<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\DocumentTypeEnum;
use App\Models\Activity;
use App\Models\ProfessionDocumentRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        return [
            'document_type' => ['required', Rule::enum(DocumentTypeEnum::class)],
            'expires_at' => [Rule::requiredIf(fn (): bool => $this->requirement()?->validity_months !== null), 'nullable', 'date_format:Y-m-d', 'after:today'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('document_type') && $this->requirement() === null) {
                    $validator->errors()->add('document_type', __('activity.document_not_required'));
                }
            },
        ];
    }

    private function requirement(): ?ProfessionDocumentRequirement
    {
        return once(function (): ?ProfessionDocumentRequirement {
            /** @var Activity $activity */
            $activity = $this->route('activity');

            return ProfessionDocumentRequirement::query()
                ->where('profession_id', $activity->profession_id)
                ->get()
                ->first(fn (ProfessionDocumentRequirement $requirement): bool => $requirement->document_type->value === $this->input('document_type'));
        });
    }
}
