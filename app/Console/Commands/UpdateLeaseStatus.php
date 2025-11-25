<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateLeaseStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lease:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update lease statuses automatically based on dates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $leases = \App\Models\Lease::all();
        foreach ($leases as $lease) {
            $lease->autoUpdateStatus();
        }
        $this->info('Lease statuses updated!');
    }

}
