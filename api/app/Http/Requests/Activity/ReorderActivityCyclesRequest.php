<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderActivityCyclesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activity = $this->route('activity');
        $activityId = $activity instanceof Activity ? $activity->id : null;

        return [
            'cycles' => ['required', 'array', 'min:1'],
            'cycles.*' => [
                'required',
                'uuid',
                Rule::exists('activities_cycles', 'id')->where('activity_id', $activityId),
            ],
        ];
    }
}
