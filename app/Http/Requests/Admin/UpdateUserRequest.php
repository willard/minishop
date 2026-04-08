<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'role' => ['required', 'string', Rule::in(['super-admin', 'admin', 'manager'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'role.in' => 'The selected role is invalid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->role === 'super-admin' && ! $this->user()->hasRole('super-admin')) {
                $validator->errors()->add('role', 'Only super-admins can assign the super-admin role.');
            }

            $targetUser = $this->route('user');
            if ($targetUser && $targetUser->id === $this->user()->id) {
                $currentRole = $targetUser->roles->first()?->name;
                if ($this->role !== $currentRole) {
                    $validator->errors()->add('role', 'You cannot change your own role.');
                }
            }
        });
    }
}
