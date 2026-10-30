<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** M4 check: counts per section, plus a spot check that a random 10% of stored files open and match their checksum. */
class VerifyLegacyDocuments extends Command
{
    protected $signature = 'documents:verify-legacy {--sample=10 : Percent of stored files to re-check}';

    protected $description = 'Compare stored documents with the list from the current site and spot-check files';

    public function handle(): int
    {
        $disk = Storage::disk('investor');
        $rows = [];
        $missing = 0;
        foreach (Category::orderBy('parent_id')->orderBy('sort_order')->get() as $c) {
            $docs = $c->documents()->get();
            if ($docs->isEmpty()) {
                continue;
            }
            $stored = $docs->filter(fn ($d) => $d->file_path && $disk->exists($d->file_path))->count();
            $missing += $docs->count() - $stored;
            $rows[] = [($c->parent ? $c->parent->name.' › ' : '').$c->name, $docs->count(), $stored, $docs->count() - $stored];
        }
        $this->table(['Section', 'Documents', 'File stored', 'Missing file'], $rows);

        $stored = Document::whereNotNull('file_path')->get();
        $n = max(1, (int) ceil($stored->count() * ((int) $this->option('sample')) / 100));
        $bad = 0;
        foreach ($stored->shuffle()->take($n) as $d) {
            $body = $disk->exists($d->file_path) ? $disk->get($d->file_path) : null;
            $ok = $body !== null && hash('sha256', $body) === $d->checksum && ($d->file_type !== 'pdf' || str_starts_with($body, '%PDF'));
            $bad += $ok ? 0 : 1;
            $this->line(($ok ? ' ok   ' : ' FAIL ').$d->title);
        }
        $this->info("Spot check: {$n} files opened, {$bad} problems. Documents without a stored file: {$missing}.");

        return $bad || $missing ? self::FAILURE : self::SUCCESS;
    }
}
