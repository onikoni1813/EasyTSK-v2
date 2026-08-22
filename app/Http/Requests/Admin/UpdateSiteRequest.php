<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $siteId = $this->route('site') ? $this->route('site')->id : null;

        return [
            'site_type_id' => 'required|exists:site_types,id',
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('sites', 'slug')->ignore($siteId)],
            'subdomain' => ['nullable', 'string', 'max:255', Rule::unique('sites', 'subdomain')->ignore($siteId)],
            'primary_domain' => ['nullable', 'string', 'max:255', Rule::unique('sites', 'primary_domain')->ignore($siteId)],
            'status' => 'required|in:active,inactive,maintenance',
            'theme' => 'nullable|string|max:100',
            'default_language' => 'nullable|string|max:10',
            'analytics_id' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
        ];
    }
}
