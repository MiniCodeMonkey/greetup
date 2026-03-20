<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('greetup:install')]
#[Description('Interactive first-time setup wizard for Greetup')]
class GreetupInstall extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Welcome to Greetup! Let\'s get your instance set up.');
        $this->newLine();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $siteName = $this->getSiteName();
        $this->saveSetting('site_name', $siteName);
        $this->info("Site name set to: {$siteName}");

        $admin = $this->createAdminUser();
        $this->info("Admin user created: {$admin->email}");

        $this->configureGeocodio();

        $this->newLine();
        $this->info('Greetup installation complete!');

        return self::SUCCESS;
    }

    private function getSiteName(): string
    {
        if (! $this->input->isInteractive()) {
            return 'Greetup';
        }

        return text(
            label: 'What would you like to name your site?',
            default: 'Greetup',
            required: true,
        );
    }

    private function createAdminUser(): User
    {
        if (! $this->input->isInteractive()) {
            $user = User::query()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
            $user->markEmailAsVerified();
            $user->assignRole('admin');

            return $user;
        }

        $name = text(
            label: 'Admin name',
            default: 'Admin',
            required: true,
        );

        $email = text(
            label: 'Admin email address',
            required: true,
            validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Please enter a valid email address.',
        );

        $pw = password(
            label: 'Admin password',
            required: true,
            validate: fn (string $value): ?string => strlen($value) >= 8 ? null : 'Password must be at least 8 characters.',
        );

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($pw),
        ]);
        $user->markEmailAsVerified();
        $user->assignRole('admin');

        return $user;
    }

    private function configureGeocodio(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        if (! confirm('Would you like to configure a Geocodio API key for location geocoding?', false)) {
            return;
        }

        $apiKey = text(
            label: 'Geocodio API key',
            required: true,
        );

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('.env file not found. Please add GEOCODIO_API_KEY manually.');

            return;
        }

        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'GEOCODIO_API_KEY=')) {
            $envContent = preg_replace('/^GEOCODIO_API_KEY=.*$/m', "GEOCODIO_API_KEY={$apiKey}", $envContent);
        } else {
            $envContent .= "\nGEOCODIO_API_KEY={$apiKey}\n";
        }

        file_put_contents($envPath, $envContent);
        $this->info('Geocodio API key saved to .env file.');
    }

    private function saveSetting(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
        Setting::clearCache();
    }
}
