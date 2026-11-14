<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * M4: copies the investor files that still live on the current site into this site's private store.
 * Safe to repeat: documents that already have a file are skipped. Every file is checked by its content before it is kept.
 */
class FetchLegacyDocuments extends Command
{
    protected $signature = 'documents:fetch-legacy {--dry-run : List what would be fetched} {--limit=0 : Stop after this many} {--sleep=500 : Milliseconds to wait between requests} {--max-mb=100 : Size limit for legacy files (new uploads stay at 25 MB)}';

    protected $description = 'Download legacy investor documents into the private store and record their checksums';

    public function handle(): int
    {
        $todo = Document::whereNull('file_path')->whereNotNull('external_url')->with('category')->orderBy('id');
        if ($limit = (int) $this->option('limit')) {
            $todo->limit($limit);
        }
        $docs = $todo->get();
        $this->info($docs->count().' documents to fetch.');
        if ($this->option('dry-run')) {
            foreach ($docs->take(10) as $d) {
                $this->line(" - {$d->id} {$d->title} <- {$d->external_url}");
            }

            return self::SUCCESS;
        }

        $disk = Storage::disk('investor');
        $report = ['ok' => 0, 'failed' => []];

        foreach ($docs as $d) {
            [$scheme, $rest] = explode('://', $d->external_url, 2);
            $parts = explode('/', $rest);
            $host = array_shift($parts);
            $url = $scheme.'://'.$host.'/'.implode('/', array_map('rawurlencode', $parts));

            try {
                $res = Http::timeout(300)->connectTimeout(20)->withUserAgent('PGFoilsMigration/1.0')->get($url);
            } catch (\Throwable $e) {
                $report['failed'][] = ['id' => $d->id, 'title' => $d->title, 'url' => $d->external_url, 'why' => 'Could not connect: '.Str::limit($e->getMessage(), 120)];
                continue;
            }

            $body = $res->body();
            $mime = $body !== '' ? (new \finfo(FILEINFO_MIME_TYPE))->buffer($body) : null;
            $ext = Document::MIMES[$mime] ?? null;
            $zipOffice = in_array($mime, ['application/zip', 'application/octet-stream'], true) && preg_match('/\.(xlsx|docx|xls)$/i', $d->external_url, $m);
            if ($zipOffice) {
                $ext = strtolower($m[1]);
            }

            $why = match (true) {
                ! $res->successful() => 'HTTP '.$res->status(),
                $ext === null => 'Not a PDF, Excel or Word file ('.($mime ?: 'empty').'); the old address may be a folder, not a file',
                strlen($body) > ((int) $this->option('max-mb')) * 1048576 => 'Larger than '.$this->option('max-mb').' MB',
                default => null,
            };
            if ($why) {
                $report['failed'][] = ['id' => $d->id, 'title' => $d->title, 'url' => $d->external_url, 'why' => $why];
                continue;
            }

            $dir = 'legacy/'.($d->category->parent?->slug ? $d->category->parent->slug.'/' : '').$d->category->slug;
            $name = $d->id.'-'.Str::slug(pathinfo($d->original_name ?: $d->title, PATHINFO_FILENAME)).'.'.$ext;
            $disk->put("$dir/$name", $body);

            $d->forceFill([
                'file_path' => "$dir/$name", 'file_size' => strlen($body), 'checksum' => hash('sha256', $body), 'file_type' => $ext,
            ])->saveQuietly();
            $report['ok']++;
            usleep(((int) $this->option('sleep')) * 1000);
        }

        Storage::disk('local')->put('legacy-import-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Stored {$report['ok']}; failed ".count($report['failed']).'. Report: storage/app/private/legacy-import-report.json');
        foreach ($report['failed'] as $f) {
            $this->warn("  #{$f['id']} {$f['title']}: {$f['why']}");
        }
        activity('investors')->withProperties(['stored' => $report['ok'], 'failed' => count($report['failed'])])->log('Legacy documents imported');

        return self::SUCCESS;
    }
}
