<?php

declare(strict_types=1);

namespace App\Http\Requests\Stay;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity|null $activity */
        $activity = $this->route('activity');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'animal_type_ids' => ['sometimes', 'array', 'min:1'],
            ...StoreUnitTypeRequest::unitRules($activity),
        ];
    }
}
