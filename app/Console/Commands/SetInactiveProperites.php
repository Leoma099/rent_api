<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use Carbon\Carbon;

class SetInactiveProperites extends Command
{
    protected $signature = 'properties:set-inactive';
    protected $description = 'Set properties inactive if not viewed for 30 days';

    public function handle()
    {
        $thresholdDate = Carbon::now()->subDays(30);

        $updated = Property::where('status', 2)
            ->where(function ($query) use ($thresholdDate) {
                $query->where(function ($q) use ($thresholdDate) {
                        $q->whereNull('last_viewed_at')
                          ->where('created_at', '<', $thresholdDate);
                    })
                    ->orWhere('last_viewed_at', '<', $thresholdDate);
            })
            ->update(['status' => 1]);

        $this->info("{$updated} properties set to inactive.");
    }
}

