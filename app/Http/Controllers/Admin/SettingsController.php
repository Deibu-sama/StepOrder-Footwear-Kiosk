<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index()
    {
        return view('admin.settings.index', [
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:80'],
            'brand_short_name' => ['required', 'string', 'max:40'],
            'brand_tagline' => ['nullable', 'string', 'max:120'],
            'admin_label' => ['required', 'string', 'max:80'],
            'logo_url' => ['nullable', 'url', 'max:1000'],
            'favicon_url' => ['nullable', 'url', 'max:1000'],

            'theme_mode' => ['required', 'in:light,dark,system'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_strong_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'reduced_motion' => ['nullable', 'boolean'],

            'kiosk_label' => ['required', 'string', 'max:100'],
            'start_title' => ['required', 'string', 'max:160'],
            'start_description' => ['required', 'string', 'max:300'],
            'start_button_text' => ['required', 'string', 'max:50'],
            'start_footer_text' => ['nullable', 'string', 'max:160'],
            'hero_enabled' => ['nullable', 'boolean'],
            'hero_interval' => ['required', 'integer', 'min:1500', 'max:15000'],
            'hero_slides' => ['nullable', 'array', 'max:5'],
            'hero_slides.*' => ['nullable', 'url', 'max:1000'],

            'confirmation_seconds' => ['required', 'integer', 'min:5', 'max:60'],
            'idle_enabled' => ['nullable', 'boolean'],
            'idle_seconds' => ['required', 'integer', 'min:30', 'max:900'],

            'max_cart_quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'currency_code' => ['required', 'string', 'max:8'],
            'order_prefix' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9_-]*$/'],
            'order_digits' => ['required', 'integer', 'min:3', 'max:8'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:99'],
            'timezone' => ['required', 'timezone'],

            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['required', 'string', 'max:300'],
        ]);

        $booleanKeys = [
            'reduced_motion',
            'hero_enabled',
            'idle_enabled',
            'maintenance_mode',
        ];

        foreach ($booleanKeys as $key) {
            $data[$key] = $request->boolean($key);
        }

        $data['hero_slides'] = array_values(array_filter(
            $data['hero_slides'] ?? [],
            fn ($url) => filled($url)
        ));

        if (count($data['hero_slides']) === 0) {
            $data['hero_slides'] = $this->settings->defaults()['hero_slides'];
        }

        $this->settings->save($data);

        return back()->with('success', 'Settings saved successfully.');
    }

    public function reset()
    {
        $this->settings->reset();

        return back()->with('success', 'Settings restored to the StepOrder defaults.');
    }
}
