<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id') ?? auth()->id();

        if ($this->routeIs('user.login')) {
            return [
                'username' => 'required|string|max:255',
                'password' => 'required|string',
            ];
        }
        elseif ($this->routeIs('user.store')) {
            return [
                'firstname'   => 'required|string|max:255',
                'middlename'  => 'nullable|string|max:255',
                'lastname'    => 'required|string|max:255',
                'birthdate'   => 'required|date|before_or_equal:today',
                'age'         => 'required|integer|min:5|max:120',
                'role'        => 'required|string|in:Student,Teacher,Admin',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'email'       => 'required|email|unique:users,email|max:255',
                'username'    => 'required|string|unique:users,username|max:255|alpha_dash',
                'password'    => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                ],
                'grade_level' => 'required|string|in:Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12',
                'school_name' => 'required|string|max:255',
                'section'     => 'required|string|max:50',
                'address'     => 'required|string|max:500',
            ];
        }
        elseif ($this->routeIs('user.email')) {
            return [
                'email' => 'required|email|unique:users,email|max:255',
            ];
        }
        elseif ($this->routeIs('user.update')) {
            return [
                'firstname'   => 'required|string|max:255',
                'middlename'  => 'nullable|string|max:255',
                'lastname'    => 'required|string|max:255',
                'birthdate'   => 'required|date|before_or_equal:today',
                'age'         => 'required|integer|min:5|max:120',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'email'       => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($userId),
                    'max:255',
                ],
                'username'    => [
                    'required',
                    'string',
                    Rule::unique('users')->ignore($userId),
                    'max:255',
                    'alpha_dash'
                ],
                'grade_level' => 'required|string|in:Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12',
                'school_name' => 'required|string|max:255',
                'section'     => 'required|string|max:50',
                'address'     => 'required|string|max:500',
            ];
        }
        elseif ($this->routeIs('user.update-password')) {
            return [
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised()
                ],
            ];
        }
        elseif ($this->routeIs('user.update-profile-picture')) {
            return [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ];
        }

        return [];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'birthdate.before_or_equal' => 'The birthdate must be a date before or equal to today.',
            'grade_level.in' => 'The selected grade level is invalid.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes and underscores.',
            'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'grade_level' => 'grade level',
            'school_name' => 'school name',
        ];
    }
}