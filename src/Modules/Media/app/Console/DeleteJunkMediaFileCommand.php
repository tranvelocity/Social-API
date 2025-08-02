<?php

namespace Modules\Media\app\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Media\app\Services\MediaService;

class DeleteJunkMediaFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'Media:delete-junk-file';

    /**
     * The console command description.
     */
    protected $description = 'This command deletes all junk media files from the S3 bucket.';

    private MediaService $mediaService;

    /**
     * Create a new command instance.
     */
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        Log::info('Started deleting junk media file from the S3.');
        $this->mediaService->deleteJunkFiles();
        Log::info('Finished deleting junk media file from the S3.');
    }
}
