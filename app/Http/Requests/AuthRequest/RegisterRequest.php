<?php

namespace App\Http\Requests\AuthRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'phone'           => 'required|string|max:20',
            'date_of_birth' => 'required|date|before:today',            'education_level' => 'required|string|max:255',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email'           => 'required|string|email|max:255|unique:users',
            'password'        => 'required|confirmed|string|min:8',

        ];
    }
}
