<?php

declare(strict_types=1);

namespace App\Http\Requests\Hosting;

use App\Models\Pet;
use App\Services\Hosting\HostScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMicrochipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pet = $this->route('pet');

        return $pet instanceof Pet
            && app(HostScanService::class)->isPetInCare($this->user(), $pet);
    }

    public function rules(): array
    {
        return [
            'microchip_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pets', 'microchip_number')->ignore($this->route('pet')->id),
            ],
        ];
    }
}
