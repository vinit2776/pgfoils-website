<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An Investors Corner document. Never hard-deleted from the admin: unpublish hides it.
 * The public URL is /investors/documents/{id}/{slug} and does not change when the file is replaced.
 */
#[Fillable(['category_id', 'title', 'fiscal_year', 'period', 'doc_date', 'status', 'publish_at', 'published_at', 'file_path', 'external_url',
    'original_name', 'legacy_path', 'file_type', 'file_size', 'checksum', 'last_updated_on_site', 'created_by', 'updated_by', 'sort_order'])]
class Document extends Model
{
    use LogsActivity;

    public const DRAFT = 'draft';

    public const SCHEDULED = 'scheduled';

    public const PUBLISHED = 'published';

    public const UNPUBLISHED = 'unpublished';

    public const STATUSES = [
        self::DRAFT => 'Draft',
        self::SCHEDULED => 'Scheduled',
        self::PUBLISHED => 'Published',
        self::UNPUBLISHED => 'Unpublished',
    ];

    public const MIMES = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public const MAX_KB = 25600;

    protected function casts(): array
    {
        return ['doc_date' => 'date', 'publish_at' => 'datetime', 'published_at' => 'datetime', 'last_updated_on_site' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Document $d) {
            $user = auth()->user();
            // Reviewers prepare drafts; only Editors and Admins may move a document out of draft. Enforced here, not only in the form.
            if ($user && ! $user->hasAnyRole([User::ADMIN, User::EDITOR]) && $d->isDirty('status') && $d->status !== self::DRAFT) {
                throw new \DomainException('Only an Editor or Admin can publish, schedule or unpublish a document.');
            }
            if ($d->isDirty('status') && $d->status === self::PUBLISHED && ! $d->published_at) {
                $d->published_at = now();
            }
            if ($d->isDirty('file_path') && $d->file_path) {
                $disk = Storage::disk('investor');
                $d->file_size = $disk->size($d->file_path);
                $d->checksum = self::checksum($disk, $d->file_path);
                // Refuse an exact copy of a file already stored (checked here so it also works when the file went straight to R2).
                if (self::where('checksum', $d->checksum)->where('id', '!=', $d->id ?? 0)->exists()) {
                    $disk->delete($d->file_path);
                    throw ValidationException::withMessages(['file_path' => 'This exact file is already in the documents list. Open that document and use "Replace file" if it has changed.']);
                }
                $d->file_type = self::MIMES[$disk->mimeType($d->file_path)] ?? strtolower(pathinfo($d->file_path, PATHINFO_EXTENSION));
            }
            if ($user) {
                $d->updated_by = $user->id;
                $d->created_by ??= $user->id;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->latest('id');
    }

    /** Visible to the public: published, or scheduled and its time has passed (so it does not depend on the scheduler). */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('status', self::PUBLISHED)
            ->orWhere(fn ($s) => $s->where('status', self::SCHEDULED)->where('publish_at', '<=', now())));
    }

    public function isLive(): bool
    {
        return $this->status === self::PUBLISHED || ($this->status === self::SCHEDULED && $this->publish_at?->isPast());
    }

    public function slug(): string
    {
        return Str::slug($this->title) ?: 'document';
    }

    public function url(): string
    {
        return route('investors.document', [$this, $this->slug()]);
    }

    /** SHA-256 of a stored file, read as a stream so large files do not fill memory. */
    public static function checksum($disk, string $path): string
    {
        $h = hash_init('sha256');
        $stream = $disk->readStream($path);
        hash_update_stream($h, $stream);
        fclose($stream);

        return hash_final($h);
    }

    /** Keeps the current file as an archived version and points the document at the new one. Same public URL. */
    public function replaceFile(string $newPath): void
    {
        $this->versions()->create([
            'file_path' => $this->file_path, 'external_url' => $this->external_url, 'original_name' => $this->original_name,
            'file_size' => $this->file_size, 'checksum' => $this->checksum, 'replaced_by' => auth()->id(),
        ]);
        $this->external_url = null;
        $this->file_path = $newPath;
        $this->original_name = basename($newPath);
        $this->last_updated_on_site = now();
        $this->save();
        activity('investors')->causedBy(auth()->user())->performedOn($this)->withProperties(['ip' => request()->ip()])->log('File replaced');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'category_id', 'fiscal_year', 'period', 'doc_date', 'status', 'publish_at', 'file_path', 'last_updated_on_site'])
            ->logOnlyDirty()->dontLogEmptyChanges()->useLogName('investors');
    }
}
