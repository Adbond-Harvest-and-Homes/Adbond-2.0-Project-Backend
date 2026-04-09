<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

use app\Mail\Contract;

use app\Models\ClientPackage;
use app\Models\File;

use app\Services\ClientPackageService;

use app\Utilities;

class SendContractMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected ClientPackage $asset, protected $uploadedFile = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // If uploadedFile is not provided, try to get it from Cloudinary via asset model
            if (!$this->uploadedFile && $this->asset->contract_file_id) {
                $file = File::find($this->asset->contract_file_id);
                if ($file) {
                    $this->uploadedFile = $file->url;
                }
            }

            if (!$this->uploadedFile) {
                Utilities::jobLog("Contract file not found for asset ID: " . $this->asset->id);
                return;
            }

            // Send Contract Mail
            Mail::to($this->asset->client->email)->send(new Contract($this->asset->client, $this->uploadedFile));

            $this->asset->markContractSent();
            
            $this->removeContractFile();
        } catch (\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to send Contract Email..".$e);

            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Utilities::jobLog("SendContractMailJob failed permanently: " . $exception->getMessage());
        $this->removeContractFile();
    }

    private function removeContractFile()
    {
        // Only delete if it's a local file (not a URL)
        if (isset($this->uploadedFile) && !str_starts_with($this->uploadedFile, 'http') && file_exists($this->uploadedFile)) {
            unlink($this->uploadedFile);
        }
    }
}
