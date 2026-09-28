<?php

namespace App\Console\Commands;

use App\Models\EvacuationCenterQuickCount;
use Illuminate\Console\Command;

/**
 * Raises every EC Board's stored cumulative counters (families_cumulative /
 * persons_cumulative) to at least the families/persons actually on record
 * at that center+event, creating any missing board row. Needed for
 * evacuation records that never went through
 * EvacuationCenterQuickCount::recordArrival() -- the demo seeders' data.
 * Only ever raises (GREATEST), so running it again changes nothing.
 * Dry run unless --apply. See EvacuationCenterQuickCount::backfillCumulative().
 */
class BackfillCumulative extends Command
{
    protected $signature = 'elikas:backfill-cumulative
        {--apply : Write the changes (without it, nothing is written)}
        {--event=* : Only these evacuation event ids (repeatable)}';

    protected $description = 'Backfill missing EC Board rows and raise low cumulative counters to what is on record (dry run unless --apply)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $eventIds = array_map('intval', (array) $this->option('event'));

        $changes = EvacuationCenterQuickCount::backfillCumulative($apply, $eventIds);

        $this->line($apply ? 'APPLYING changes.' : 'DRY RUN -- nothing will be written. Re-run with --apply to write.');
        $this->line($eventIds ? 'Events: '.implode(', ', $eventIds) : 'Events: all');

        if ($changes->isEmpty()) {
            $this->info('Nothing to change: every board already covers what is on record.');

            return self::SUCCESS;
        }

        $this->table(
            ['Event', 'Center', 'Action', 'Families before -> after', 'Persons before -> after'],
            $changes->map(fn ($c) => [
                $c['event_id'],
                $c['center_id'],
                $c['created'] ? 'create row' : 'raise',
                ($c['families_before'] ?? '(none)').' -> '.$c['families_after'],
                ($c['persons_before'] ?? '(none)').' -> '.$c['persons_after'],
            ])->all()
        );

        $created = $changes->where('created', true)->count();
        $this->info(sprintf(
            '%s %d board(s): %d row(s) created, %d raised.',
            $apply ? 'Changed' : 'Would change',
            $changes->count(),
            $created,
            $changes->count() - $created
        ));

        return self::SUCCESS;
    }
}
