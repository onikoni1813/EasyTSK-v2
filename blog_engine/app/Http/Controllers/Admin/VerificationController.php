<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RootFile;
use App\Services\SiteContext;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(SiteContext $siteContext)
    {
        $site = $siteContext->get();
        if (!$site) {
            return redirect()->route('admin.sites.index')->with('error', 'Please select a blog site first.');
        }

        $rootFiles = RootFile::where('site_id', $site->id)->orderBy('filename')->get();

        return view('admin.verification.index', compact('site', 'rootFiles'));
    }

    public function saveAdsTxt(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();
        $validated = $request->validate([
            'ads_txt' => 'nullable|string',
        ]);

        $site->update([
            'ads_txt' => $validated['ads_txt'] ?? '',
        ]);

        return redirect()->route('admin.verification.index')->with('success', "ads.txt updated for '{$site->name}'.");
    }

    public function storeRootFile(Request $request, SiteContext $siteContext)
    {
        $site = $siteContext->get();

        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'content' => 'required|string',
            'mime_type' => 'required|string|max:100',
        ]);

        $filename = ltrim(trim($validated['filename']), '/');

        RootFile::updateOrCreate(
            [
                'site_id' => $site->id,
                'filename' => $filename,
            ],
            [
                'content' => $validated['content'],
                'mime_type' => $validated['mime_type'],
            ]
        );

        return redirect()->route('admin.verification.index')->with('success', "Root file '{$filename}' saved. Live at {$site->url}/{$filename}");
    }

    public function destroyRootFile(RootFile $rootFile)
    {
        $filename = $rootFile->filename;
        $rootFile->delete();

        return redirect()->route('admin.verification.index')->with('success', "Root file '{$filename}' removed.");
    }
}
