<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        if ( request()-> routeIs('user.login') ) {
            return [
                'username'              => 'required|string|max:255',
                'password'              => 'required|min:8',
            ];
        }
        else if ( request()-> routeIs('user.store') ) {
            return [
                'firstname'             => 'required|string|max:255',
                'middlename'            => 'nullable|string|max:255',
                'lastname'              => 'required|string|max:255',
                'birthdate'             => 'required|date',
                'age'                   => 'required|integer',
                'role'                  => 'required|string|max:255',
                'image'                 => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'email'                 => 'required|email|unique:App\Models\User,email|max:255',
                'username'              => 'required|string|unique:App\Models\User,username|max:255',
                'password'              => 'required|string|confirmed|min:8',
                'grade_level'           => 'required|string|max:255',
                'school_name'           => 'required|string|max:255',
                'section'               => 'required|string|max:255',
                'address'               => 'required|string|max:255',
            ];
        }
        else if( request()-> routeIs('user.update') ) {
            return [
                'firstname'             => 'required|string|max:255',
                'middlename'            => 'nullable|string|max:255',
                'lastname'              => 'required|string|max:255',
                'age'                   => 'required|integer|min:1|max:30',
                'image'                 => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'email'                 => 'required|email|unique:App\Models\User,email|max:255',
                'password'              => 'required|string|confirmed|min:8',
                'grade_level'           => 'required|string|max:255',
                'school_name'           => 'required|string|max:255',
                'section'               => 'required|string|max:255',
                'address'               => 'required|string|max:255',
            ];
        }
    }
}
