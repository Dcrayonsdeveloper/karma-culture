<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ChatbotSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'       => 'nia_enabled',
                'value'     => '1',
                'type'      => 'boolean',
                'group'     => 'chatbot',
                'is_public' => false,
            ],
            [
                'key'       => 'nia_model',
                'value'     => 'claude-sonnet-4-20250514',
                'type'      => 'string',
                'group'     => 'chatbot',
                'is_public' => false,
            ],
            [
                'key'       => 'nia_system_prompt',
                'value'     => '',
                'type'      => 'string',
                'group'     => 'chatbot',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
