<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Customer\Facades\Captcha;
use Webkul\Shop\Support\PhoneNumberNormalizer;

class LoginRequest extends FormRequest
{
    /**
     * Define your rules.
     *
     * @var array
     */
    private $rules = [
        'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        'password' => 'required|string|max:255',
    ];

    /**
     * Normalize the account identifier before validation and throttling.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => PhoneNumberNormalizer::normalize($this->input('phone'))]);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return Captcha::getValidations($this->rules);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return Captcha::getValidationMessages();
    }
}
