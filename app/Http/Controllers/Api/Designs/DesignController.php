<?php

namespace App\Http\Controllers\Api\Designs;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDesignRequest;
use App\Http\Resources\DesignResource;
use App\Models\Design;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DesignController extends Controller
{
    public function update(UpdateDesignRequest $request, Design $design)
    {
        $this->authorize("update", $design);

        $is_live = $design->upload_successful ? (filter_var($request->is_live, FILTER_VALIDATE_BOOLEAN)) : false;

        $design->update([
            "title" => $request->title,
            "slug" => Str::slug($request->title),
            "description" => $request->description,
            "is_live" => $is_live,
            "team_id" => $request->team && $request->assign_to_team ? $request->team : null,
        ]);

        $design->retag($request->tags);

        return new DesignResource($design);
    }

    public function destroy(Design $design)
    {
        $this->authorize('delete', $design);

        foreach (['thumbnail', 'large', 'original'] as $size) {
            if (Storage::disk($design->disk)->exists("/uploads/designs/{$size}/" . $design->image)) {
                Storage::disk($design->disk)->delete("/uploads/designs/{$size}/" . $design->image);
            }
        }

        $design->delete();

        return response()->json(status: 200);
    }

    public function like(Design $design)
    {
        if ($design->alreadyLikedByUser()) {
            $design->unlike();
            return response()->json(["unliked"]);
        } else {
            $design->like();
            return response()->json(["liked"]);
        }
    }

    public function likedByUser(Design $design)
    {
        return response()->json([
            "liked" => $design->alreadyLikedByUser()
        ]);
    }

    public function findBySlug($slug)
    {
        $design = Design::where('slug', $slug)->with([
            "team",
            "comments",
            "tags",
            "user",
            "likes"
        ])->get();

        if ($design->count() > 0) {
            return new DesignResource($design[0]);
        } else {
            return response()->json(['no design found'], 404);
        }
    }

    public function findById($id)
    {
        $design = Design::where('id', $id)->with(['tags', 'team'])->get();

        if ($design->count() > 0) {
            return new DesignResource($design[0]);
        } else {
            return response()->json(['no design found'], 404);
        }
    }

    public function search(Request $request)
    {
        $designs = Design::with([
            "likes",
            "tags",
            "team",
            "user"
        ])->search($request)->paginate(12);

        return DesignResource::collection($designs);
    }
}
