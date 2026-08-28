<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $media = Media::latest()->paginate(24);

        return view('admin.media.index', compact('media', 'site'));
    }

    public function store(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("sites/{$site->id}/media", $filename, 'public');

        $media = Media::create([
            'site_id' => $site->id,
            'name' => $originalName,
            'file_path' => '/storage/' . $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'alt_text' => pathinfo($originalName, PATHINFO_FILENAME),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'url' => $media->file_path,
                'media' => $media,
            ]);
        }

        return redirect()->route('admin.media.index')->with('success', 'Media uploaded successfully.');
    }

    public function destroy(Media $media)
    {
        $media->delete();
        return redirect()->route('admin.media.index')->with('success', 'Media deleted.');
    }
}
