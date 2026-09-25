<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\AvailabilityStatusEnum;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * La même exception posée sur une liste de dates, ou sur chaque jour d'une période (congés), d'un an au plus.
 */
class StoreAvailabilitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dates' => ['required_without:from', 'prohibits:from,to', 'array', 'min:1', 'max:'.ListAvailabilitiesRequest::MAX_DAYS],
            'dates.*' => ['distinct', 'date_format:Y-m-d'],
            'from' => ['required_without:dates', 'date_format:Y-m-d'],
            'to' => ['required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['required', Rule::enum(AvailabilityStatusEnum::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->filled('from') && Carbon::parse($this->input('from'))->diffInDays($this->input('to')) >= ListAvailabilitiesRequest::MAX_DAYS) {
                    $validator->errors()->add('to', __('activity.range_too_long', ['days' => ListAvailabilitiesRequest::MAX_DAYS]));
                }
            },
        ];
    }

    /**
     * Les dates concernées, au format Y-m-d.
     *
     * @return list<string>
     */
    public function dates(): array
    {
        if ($this->filled('dates')) {
            return array_values($this->validated('dates'));
        }

        return array_map(
            fn (CarbonInterface $date): string => $date->toDateString(),
            CarbonPeriod::create($this->validated('from'), $this->validated('to'))->toArray(),
        );
    }
}
