<?php

namespace Database\Factories;

use App\Models\EasypostCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

class EasypostCredentialFactory extends Factory
{
    public function definition()
    {
        return [
            'api_base_url' => 'https://api.easypost.com/v2/',
            'api_key' => env('EASYPOST_API_KEY'),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
