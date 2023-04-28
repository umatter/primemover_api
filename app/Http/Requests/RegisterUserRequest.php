<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $email
 * @property string $name
 * @property string $password
 */
class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:55'],
            'email' => ['email', 'required', 'unique:users'],
            'password' => ['required', 'confirmed'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'password' => bcrypt($this->password),
        ]);
    }
}
