<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use Carbon\Carbon;

class SetInactiveProperites extends Command
{
    // Command signature to run it manually
    protected $signature = 'properties:set-inactive';

    protected $description = 'Set properties older than 30 days to inactive';

    public function handle()
    {
        $thresholdDate = Carbon::now()->subDays(30);

        // Only update properties that are still active (status = 2)
        $updated = Property::where('status', 2)
            ->where('created_at', '<', $thresholdDate)
            ->update(['status' => 1]); // 1 = Inactive

        $this->info("{$updated} properties set to inactive.");
    }
}
