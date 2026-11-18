<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberUtil;
use Webkul\Customer\Facades\Captcha;
use Webkul\Shop\Support\PhoneNumberNormalizer;

class RegistrationRequest extends FormRequest
{
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
     * Normalize contact data and use the identity returned by Google.
     */
    protected function prepareForValidation(): void
    {
        $socialRegistration = session('social_registration');

        if ($socialRegistration && ($socialRegistration['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget('social_registration');

            $socialRegistration = null;
        }

        if ($socialRegistration) {
            $this->merge([
                'first_name' => $this->input('first_name') ?: $socialRegistration['first_name'],
                'last_name' => $this->input('last_name') ?: $socialRegistration['last_name'],
                'email' => $socialRegistration['email'] ?: $this->input('email'),
            ]);
        }

        $this->merge([
            'last_name' => $this->input('last_name') ?: '',
            'email' => is_string($this->input('email')) ? (trim($this->input('email')) ?: null) : $this->input('email'),
            'phone' => PhoneNumberNormalizer::normalize(
                $this->input('phone_number'),
                is_string($this->input('phone_country')) ? $this->input('phone_country') : null
            ),
            'has_whatsapp' => $this->boolean('has_whatsapp'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $socialRegistration = session('social_registration');

        $rules = [
            'first_name' => 'string|required|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,NULL,id,channel_id,'.core()->getCurrentChannel()->id,
            'phone_country' => ['required', 'string', Rule::in(PhoneNumberUtil::getInstance()->getSupportedRegions())],
            'phone_number' => [
                'required',
                'string',
                'max:32',
                function ($attribute, $value, $fail) {
                    if (! $this->input('phone')) {
                        $fail(trans('shop::app.customers.signup-form.invalid-phone'));
                    }
                },
            ],
            'phone' => ['required', 'string', 'regex:/^\\+[1-9]\\d{7,14}$/', 'unique:customers,phone'],
            'has_whatsapp' => ['sometimes', 'boolean'],
            'password' => $socialRegistration ? 'nullable|string|confirmed|min:8|max:255' : 'required|string|confirmed|min:8|max:255',
        ];

        return Captcha::getValidations($rules);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return array_merge(Captcha::getValidationMessages(), [
            'phone.unique' => trans('shop::app.customers.signup-form.phone-taken'),
        ]);
    }

    /**
     * Describe the normalized phone fields in validation errors.
     */
    public function attributes(): array
    {
        return [
            'phone' => trans('shop::app.customers.signup-form.phone-number'),
            'phone_number' => trans('shop::app.customers.signup-form.phone-number'),
            'phone_country' => trans('shop::app.customers.signup-form.phone-country'),
        ];
    }
}
