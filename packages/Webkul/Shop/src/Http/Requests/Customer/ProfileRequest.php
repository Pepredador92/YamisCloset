<?php

namespace Webkul\Shop\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Shop\Support\PhoneNumberNormalizer;

class ProfileRequest extends FormRequest
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
     * Normalize the account contact fields before checking uniqueness.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumberNormalizer::normalize($this->input('phone')),
            'last_name' => $this->input('last_name') ?: '',
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
        $customer = auth()->guard('customer')->user();

        $id = $customer->id;

        return [
            'first_name' => ['required', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'gender' => 'nullable|in:Other,Male,Female',
            'date_of_birth' => 'nullable|date|before:today',
            'email' => 'nullable|max:255|email|unique:customers,email,'.$id.',id,channel_id,'.$customer->channel_id,
            'new_password' => 'nullable|string|confirmed|min:8|max:255|required_with:current_password',
            'new_password_confirmation' => 'required_with:new_password',
            'current_password' => $customer->password ? 'required_with:new_password|string|max:255' : 'nullable|string|max:255',
            'image' => 'array',
            'image.*' => 'mimes:bmp,jpeg,jpg,png,webp',
            'phone' => ['required', 'max:255', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:customers,phone,'.$id],
            'has_whatsapp' => 'boolean',
            'subscribed_to_news_letter' => 'nullable',
        ];
    }

    /**
     * Get the custom attribute names for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'image.*' => trans('shop::app.components.layouts.account.profile-image'),
        ];
    }
}
