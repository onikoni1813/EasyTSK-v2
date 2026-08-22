<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'site_type_id' => 'required|exists:site_types,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sites,slug',
            'subdomain' => 'nullable|string|max:255|unique:sites,subdomain',
            'primary_domain' => 'nullable|string|max:255|unique:sites,primary_domain',
            'status' => 'required|in:active,inactive,maintenance',
            'theme' => 'nullable|string|max:100',
            'default_language' => 'nullable|string|max:10',
            'analytics_id' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
        ];
    }
}
