<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** An earlier file of a document, kept when the file is replaced. */
#[Fillable(['document_id', 'file_path', 'external_url', 'original_name', 'file_size', 'checksum', 'replaced_by'])]
class DocumentVersion extends Model
{
    public $timestamps = false;
}
