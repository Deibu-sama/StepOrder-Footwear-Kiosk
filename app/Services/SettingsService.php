<?php

namespace App\Services;

class SettingsService
{
    private ?array $settings = null;

    public function __construct(private readonly FirestoreService $firestore) {}

    public function defaults(): array
    {
        return [
            // Branding
            'brand_name' => 'StepOrder',
            'brand_short_name' => 'StepOrder',
            'brand_tagline' => 'Simple, Flexible Self-Service Ordering',
            'admin_label' => 'Management Console',
            'logo_url' => '',
            'favicon_url' => '',

            // Appearance
            'theme_mode' => 'light',
            'primary_color' => '#bef264',
            'primary_strong_color' => '#65a30d',
            'primary_text_color' => '#111111',
            'reduced_motion' => false,

            // Kiosk start screen
            'kiosk_label' => 'SELF-SERVICE ORDERING KIOSK',
            'start_title' => 'FIND WHAT YOU NEED.',
            'start_description' => 'Browse products, build your order, and proceed to the cashier when you\'re ready to pay.',
            'start_button_text' => 'TAP TO START',
            'start_footer_text' => 'Tap the button to begin your order.',
            'hero_enabled' => true,
            'hero_interval' => 3500,
            'hero_slides' => [
                'https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=1400&q=85',
                'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=85',
                'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=1400&q=85',
                'https://images.unsplash.com/photo-1543168256-418811576931?auto=format&fit=crop&w=1400&q=85',
                'https://images.unsplash.com/photo-1545601445-1f9c70b6ca0f?auto=format&fit=crop&w=1400&q=85',
            ],

            // Kiosk behavior
            'confirmation_seconds' => 10,
            'idle_enabled' => false,
            'idle_seconds' => 120,

            // Catalog / ordering
            'show_out_of_stock' => true,
            'show_top_picks' => true,
            'show_sale_filter' => true,
            'show_price_filter' => true,
            'max_cart_quantity' => 20,
            'currency_symbol' => '₱',
            'currency_code' => 'PHP',

            // Operations
            'order_prefix' => '',
            'order_digits' => 4,
            'low_stock_threshold' => 3,
            'pending_order_warning_hours' => 24,
            'pending_order_expiry_days' => 7,
            'timezone' => 'Asia/Manila',

            // Maintenance
            'maintenance_mode' => false,
            'maintenance_message' => 'The kiosk is temporarily unavailable. Please check back in a moment.',
        ];
    }

    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        try {
            $document = $this->firestore->find('settings', 'app');
            $stored = $document ? $document : [];
            unset($stored['id']);
            $this->settings = array_replace($this->defaults(), $stored);
        } catch (\Throwable $e) {
            $this->settings = $this->defaults();
        }

        return $this->settings;
    }

    public function save(array $settings): array
    {
        $payload = array_replace($this->defaults(), $settings);
        $existing = $this->firestore->find('settings', 'app');

        if ($existing) {
            $saved = $this->firestore->update('settings', 'app', $payload);
        } else {
            $id = $this->firestore->create('settings', $payload, 'app');
            $saved = array_merge(['id' => $id], $payload);
        }

        unset($saved['id']);
        $this->settings = array_replace($this->defaults(), $saved);

        return $this->settings;
    }

    public function reset(): array
    {
        return $this->save($this->defaults());
    }
}
