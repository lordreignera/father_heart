<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrepareLegacyInventory extends Command
{
    protected $signature = 'legacy:prepare-inventory {--write : Create missing uncounted item types} {--rehearsal : Use the isolated restored target}';
    protected $description = 'Prepare historical stock item types without importing balances or movements';

    public function handle(): int
    {
        $targetName = $this->option('rehearsal') ? 'fathersheart2_restoretest_fd1ca66b' : 'fathersheart2';
        $sourceName = $this->option('rehearsal') ? 'fathersheart_restoretest_fd1ca66b' : 'fathersheart';
        $config = array_merge(config('database.connections.mysql'), ['url' => null, 'database' => $targetName]);
        config(['database.connections.legacy_inventory_target' => $config]);
        DB::purge('legacy_inventory_target');
        $db = DB::connection('legacy_inventory_target');
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== $targetName) throw new RuntimeException('Unexpected target database.');
        $items = [
            'cane' => 'Cane', 'white_cane' => 'White cane', 'walkers' => 'Walker', 'crutches' => 'Crutches',
            'gen1' => 'GEN1', 'gen2_s_13' => 'GEN2 S 13', 'gen2_m_17' => 'GEN2 M 17', 'gen2_l_18_5' => 'GEN2 L 18.5', 'gen2_xl_20' => 'GEN2 XL 20',
            'gen3_s_13' => 'GEN3 S 13', 'gen3_m_17' => 'GEN3 M 17', 'gen3_l_18_5' => 'GEN3 L 18.5', 'gen3_xl_20' => 'GEN3 XL 20',
            'jaf' => 'JAF', 'hb_11' => 'HB 11', 'hb_13' => 'HB 13',
        ];
        foreach ($items as $table => $name) {
            if (! $db->table('legacy_source_records')->where('source_database', $sourceName)->where('source_table', $table)->exists()) throw new RuntimeException('Missing historical stock source '.$table);
        }
        $created = 0; $existing = 0;
        $db->transaction(function () use ($db, $items, &$created, &$existing) {
            foreach ($items as $name) {
                if ($db->table('inventory_items')->where('name', $name)->exists()) { $existing++; continue; }
                if ($this->option('write')) $db->table('inventory_items')->insert(['name' => $name, 'unit' => 'item', 'created_at' => now(), 'updated_at' => now()]);
                $created++;
            }
        });
        $this->info(($this->option('write') ? 'Created ' : 'Would create ').$created.' item types; '.$existing.' already exist.');
        $this->warn('No historical counts or movements were imported. Every new item needs an approved opening count.');
        return self::SUCCESS;
    }
}
