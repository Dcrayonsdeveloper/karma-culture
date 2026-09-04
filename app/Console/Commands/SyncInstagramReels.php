<?php

namespace App\Console\Commands;

use App\Services\InstagramReelService;
use Illuminate\Console\Command;

/**
 * Pull the latest reels into the About Us strip.
 *
 * Scheduled in routes/console.php for a host that runs `schedule:run`. This one
 * does not - there is no cron binary on the account - so on production the
 * button on Homepage > About Reels is what actually fires. The command is still
 * the right place for the work: it is what a scheduler, a deploy hook or an
 * admin over SSH would call.
 */
class SyncInstagramReels extends Command
{
    protected $signature = 'instagram:sync-reels';

    protected $description = 'Fetch the latest Instagram reels into the About Us strip on the home page';

    public function handle(InstagramReelService $instagram): int
    {
        if (! $instagram->configured()) {
            $this->warn('No Instagram access token is configured. Add one under Homepage > About Reels.');

            return self::SUCCESS;
        }

        $result = $instagram->sync();

        if (! $result['ok']) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Instagram reels: %d added, %d updated, %d removed, %d skipped.',
            $result['added'],
            $result['updated'],
            $result['removed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
