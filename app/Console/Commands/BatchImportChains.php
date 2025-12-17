<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BatchImportChains extends Command
{
    protected $signature = 'import:batch
                            {--chain= : Specific chain to import (or omit for all chains)}
                            {--delay=10 : Seconds to wait between cities (default: 10)}
                            {--dry-run : Preview without saving}';

    protected $description = 'Import chain locations from multiple US cities with rate limiting';

    // Major US cities for batch import
    protected array $cities = [
        // Top 50 US cities by population
        'New York',
        'Los Angeles',
        'Chicago',
        'Houston',
        'Phoenix',
        'Philadelphia',
        'San Antonio',
        'San Diego',
        'Dallas',
        'San Jose',
        'Austin',
        'Jacksonville',
        'Fort Worth',
        'Columbus',
        'Charlotte',
        'San Francisco',
        'Indianapolis',
        'Seattle',
        'Denver',
        'Washington',
        'Boston',
        'Nashville',
        'Oklahoma City',
        'Las Vegas',
        'Portland',
        'Detroit',
        'Memphis',
        'Louisville',
        'Milwaukee',
        'Baltimore',
        'Albuquerque',
        'Tucson',
        'Fresno',
        'Sacramento',
        'Atlanta',
        'Kansas City',
        'Miami',
        'Raleigh',
        'Omaha',
        'Minneapolis',
        'Cleveland',
        'Tampa',
        'New Orleans',
        'Orlando',
        'Pittsburgh',
        'Cincinnati',
        'St. Louis',
        'Salt Lake City',
    ];

    public function handle()
    {
        $chain = $this->option('chain');
        $delay = (int) $this->option('delay');
        $dryRun = $this->option('dry-run');

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🧊 Batch Chain Import");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Cities: " . count($this->cities));
        $this->line("   Chain filter: " . ($chain ?: 'All chains'));
        $this->line("   Delay between cities: {$delay} seconds");
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
            $this->newLine();
        }

        $totalImported = 0;
        $totalCities = count($this->cities);

        foreach ($this->cities as $index => $city) {
            $cityNum = $index + 1;
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📍 [{$cityNum}/{$totalCities}] Importing: {$city}");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $args = [
                '--city' => $city,
            ];

            if ($chain) {
                $args['--chain'] = $chain;
            } else {
                $args['--all'] = true;
            }

            if ($dryRun) {
                $args['--dry-run'] = true;
            }

            // Run the import command
            $exitCode = Artisan::call('import:chains', $args);
            $output = Artisan::output();

            // Extract saved count from output
            if (preg_match('/Total saved: (\d+)/', $output, $matches)) {
                $saved = (int) $matches[1];
                $totalImported += $saved;
                $this->line("   Imported: {$saved} locations");
            }

            // Delay between cities (except for the last one)
            if ($index < $totalCities - 1 && $delay > 0) {
                $this->line("   Waiting {$delay} seconds before next city...");
                sleep($delay);
            }
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Batch Import Complete");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("   Total cities processed: {$totalCities}");
        $this->line("   Total locations imported: {$totalImported}");
        $this->newLine();

        if (!$dryRun && $totalImported > 0) {
            $this->info("🎉 Successfully imported {$totalImported} locations across {$totalCities} cities!");
        }

        return 0;
    }
}
