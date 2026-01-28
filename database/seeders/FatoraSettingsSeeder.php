<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FatoraSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first business_id (you can modify this to use a specific business)
        $business = DB::table('business')->first();
        
        if (!$business) {
            $this->command->error('No business found in database. Please create a business first.');
            return;
        }

        // Check if settings already exist
        $exists = DB::table('settings_fatora')
            ->where('business_id', $business->id)
            ->exists();

        if ($exists) {
            $this->command->info('Fatora settings already exist for this business. Updating...');
            
            DB::table('settings_fatora')
                ->where('business_id', $business->id)
                ->update([
                    'client_id' => '4312198e-eaa6-4d2e-9bb2-9a7b1431f9c1',
                    'secret_key' => 'Gj5nS9wyYHRadaVffz5VKB4v4wlVWyPhcJvrTD4NHtM0YfHMwojMwFtc9m9hOHS3H2k22OnEP5UEnyeZsaKhyu96hFU+l1ugYmCM5vaBANRXx4gr81NsXVaix88eh6hKcm5PFhvrwfFx6nuOjoPkSSImO7l/N9PrGGxQXwN1OCycSZFBbofkhvgpxOu4ON6O+cA9D7yG4Di/diVq4Mbjt6Ep/19fSuO+RdPPEVdsrb1ytPLycvT9x96nyN4VZWlwlSn4EII5Z+nXLLG7YpUX8g==',
                    'supplier_income_source' => '18745024',
                    'tin' => null, // يجب إضافته يدوياً
                    'registration_name' => null, // يجب إضافته يدوياً
                    'is_active' => true,
                    'updated_at' => now()
                ]);
        } else {
            $this->command->info('Creating new Fatora settings...');
            
            DB::table('settings_fatora')->insert([
                'business_id' => $business->id,
                'client_id' => '4312198e-eaa6-4d2e-9bb2-9a7b1431f9c1',
                'secret_key' => 'Gj5nS9wyYHRadaVffz5VKB4v4wlVWyPhcJvrTD4NHtM0YfHMwojMwFtc9m9hOHS3H2k22OnEP5UEnyeZsaKhyu96hFU+l1ugYmCM5vaBANRXx4gr81NsXVaix88eh6hKcm5PFhvrwfFx6nuOjoPkSSImO7l/N9PrGGxQXwN1OCycSZFBbofkhvgpxOu4ON6O+cA9D7yG4Di/diVq4Mbjt6Ep/19fSuO+RdPPEVdsrb1ytPLycvT9x96nyN4VZWlwlSn4EII5Z+nXLLG7YpUX8g==',
                'supplier_income_source' => '18745024',
                'tin' => null, // يجب إضافته يدوياً
                'registration_name' => null, // يجب إضافته يدوياً
                'crn' => null,
                'street_name' => null,
                'building_number' => null,
                'city_name' => null,
                'city_code' => null,
                'county' => null,
                'postal_code' => null,
                'plot_al_zone' => null,
                'vat' => null,
                'csr' => null,
                'is_active' => true,
               
            ]);
        }

        $this->command->info('✅ Fatora settings have been seeded successfully!');
        $this->command->warn('⚠️  Please update TIN and Registration Name manually from the settings page.');
    }
}
