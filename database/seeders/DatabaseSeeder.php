<?php

namespace Database\Seeders;

use App\Models\ProductUpdate;
use App\Models\Release;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (filled(config('admin.default_user.email')) && filled(config('admin.default_user.password'))) {
            $this->call(AdminUserSeeder::class);
        }

        Release::query()->firstOrCreate([
            'version' => '1.0.0-beta.1',
        ], [
            'channel' => 'beta',
            'platform' => 'macos',
            'architecture' => 'universal',
            'file_path' => 'releases/LidUp-1.0.0-beta.1.dmg',
            'minimum_os' => 'macOS 14 Sonoma',
            'release_notes' => 'The first private beta release of LidUp.',
            'is_current' => true,
            'published_at' => now(),
        ]);

        ProductUpdate::query()->firstOrCreate([
            'slug' => 'private-beta-foundation',
        ], [
            'title' => 'The private beta foundation is ready',
            'summary' => 'Accounts, downloads, trials, and subscription management are now connected.',
            'body' => 'The LidUp private beta backend is ready for signed app releases and a production billing provider.',
            'published_at' => now(),
        ]);
    }
}
