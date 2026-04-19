<?php

namespace App\Http\Requests\Admin;

use App\Enums\MenuLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'menu_location' => ['required', Rule::enum(MenuLocation::class)],
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:500'],
            'target' => ['nullable', 'in:_self,_blank'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
        ];
    }
}
