<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules() {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('companies')->where('user_id', auth()->id()),
            ],
            'logo' => 'nullable|image|max:2048',
        ];
    }

    public function messages() {
        return ['name.unique' => 'У вас уже есть компания с таким названием.'];
    }
}
