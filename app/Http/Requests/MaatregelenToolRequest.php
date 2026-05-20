<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaatregelenToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $commaFields = ['budget', 'verhard_m2', 'bergingsnorm_m3_per_m2', 'beschikbaar_gebied_m2', 'beschikbaar_dak_m2'];
        $merged = [];
        foreach ($commaFields as $f) {
            if ($this->has($f) && is_string($this->input($f))) {
                $merged[$f] = str_replace(',', '.', trim($this->input($f)));
            }
        }
        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'budget' => ['nullable', 'numeric', 'min:0'],
            'aantal_toepasbare_stuks' => ['nullable', 'integer', 'min:1'],
            'niveau_gebied' => ['nullable'],
            'niveau_gebouw' => ['nullable'],
            'risicos' => ['nullable', 'array'],
            'risicos.*' => ['string', 'in:hittestress,wateroverlast,droogte,overstromingsgevaar'],
            'verhard_m2' => ['nullable', 'numeric', 'min:0'],
            'bergingsnorm_m3_per_m2' => ['nullable', 'numeric', 'min:0'],
            'beschikbaar_gebied_m2' => ['nullable', 'numeric', 'min:0'],
            'beschikbaar_dak_m2' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->boolean('niveau_gebied') && ! $this->boolean('niveau_gebouw')) {
                $validator->errors()->add('niveau_gebied', 'Kies minimaal één schaalniveau (gebied en/of gebouw).');
            }
        });
    }
}
