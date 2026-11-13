<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An external resource (a Google Drive folder or file, video, PDF, document…)
 * that the school or the tutor attached to one class. Learners open it from
 * My Learning → Class Library; the title is the link, the URL stays in the href.
 */
class ClassLibraryResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'created_by', 'title', 'description', 'url', 'type',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /** The administrator, CEO or tutor who added the link. */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resource types offered on the add form.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'link' => 'Web link',
            'video' => 'Video',
            'pdf' => 'PDF',
            'document' => 'Document',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? 'Web link';
    }

    /** Font Awesome icon shown next to the title. */
    public function typeIcon(): string
    {
        $icons = [
            'video' => 'fa-video',
            'pdf' => 'fa-file-pdf',
            'document' => 'fa-file-word',
            'link' => 'fa-link',
        ];

        return $icons[$this->type] ?? 'fa-link';
    }
}
