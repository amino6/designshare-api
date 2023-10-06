<?php

namespace App\Models;

use App\Models\Traits\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Cviebrock\EloquentTaggable\Taggable;
use ShiftOneLabs\LaravelCascadeDeletes\CascadesDeletes;
use Illuminate\Http\Request;

class Design extends Model
{
    use HasFactory, Taggable, Likeable, CascadesDeletes;

    protected $cascadeDeletes = ['comments', 'likes'];

    protected $fillable = [
        'user_id',
        'image',
        'title',
        'description',
        'slug',
        'colse_to_comment',
        'is_live',
        'upload_successful',
        'disk',
        'team_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('created_at', 'asc');
    }

    public function getImagesAttribute()
    {
        $thumbnail = Storage::disk($this->disk)->url('uploads/designs/thumbnail/' . $this->image);
        $large = Storage::disk($this->disk)->url('uploads/designs/large/' . $this->image);
        $original = Storage::disk($this->disk)->url('uploads/designs/original/' . $this->image);

        return [
            $large,
            $thumbnail,
            $original
        ];
    }

    protected function scopeSearch($query, Request $request)
    {
        $query->where('is_live', true);

        if ($request->has_comments) {
            $query = $query->has('comments');
        }

        if ($request->has_team) {
            $query = $query->has('team');
        }

        if ($request->q) {
            $query = $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%')
                    ->orWhere('description', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->orderBy == 'likes') {
            $query = $query->withCount('likes')->orderBy('likes_count', 'desc');
        } else {
            $query = $query->latest();
        }

        return $query;
    }
}
