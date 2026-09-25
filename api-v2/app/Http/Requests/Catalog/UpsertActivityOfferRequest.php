<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use App\Enums\BookingModeEnum;
use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Seule une activité en séjour propose des options de séjour. Une prestation incluse dans le prix du séjour
 * est forcément proposée en option de séjour.
 */
class UpsertActivityOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity $activity */
        $activity = $this->route('activity');
        $isStay = $activity->profession()->firstOrFail()->booking_mode === BookingModeEnum::STAY;

        return [
            'offered_as' => ['sometimes', Rule::enum(ServiceOfferEnum::class)->only(
                $isStay ? ServiceOfferEnum::cases() : [ServiceOfferEnum::STANDALONE],
            )],
            'adjustment_percent' => ['sometimes', 'numeric', 'decimal:0,2', 'between:-100,100'],
            'is_included' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'offered_as.enum' => __('catalog.stay_option_requires_stay'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $offeredAs = ServiceOfferEnum::tryFrom((string) $this->input('offered_as', ServiceOfferEnum::STANDALONE->value));

                if ($this->boolean('is_included') && $offeredAs?->isStayOption() !== true) {
                    $validator->errors()->add('is_included', __('catalog.included_requires_stay_option'));
                }
            },
        ];
    }
}
