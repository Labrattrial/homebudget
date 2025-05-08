<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PublishDefaultProfilePicture extends Command
{
    protected $signature = 'profile:publish-default';
    protected $description = 'Publish the default profile picture to storage';

    public function handle()
    {
        $defaultPath = 'defaults/default-profile.png';
        $sourcePath = resource_path('images/default-profile.png');

        if (!file_exists($sourcePath)) {
            $this->error('Default profile picture not found at: ' . $sourcePath);
            return 1;
        }

        Storage::disk('public')->put(
            $defaultPath,
            file_get_contents($sourcePath)
        );

        $this->info('Default profile picture published successfully!');
        return 0;
    }
} 