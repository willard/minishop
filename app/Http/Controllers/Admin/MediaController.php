<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Media::class);

        $media = Media::query()
            ->with('uploader:id,name')
            ->when(request('search'), fn ($q, $search) => $q->where('original_name', 'like', "%{$search}%"))
            ->when(request('type'), function ($q, $type) {
                if ($type === 'image') {
                    $q->where('mime_type', 'like', 'image/%');
                } elseif ($type === 'document') {
                    $q->where('mime_type', 'not like', 'image/%');
                }
            })
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('admin/Media/Index', [
            'media' => $media,
            'filters' => request()->only(['search', 'type']),
        ]);
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $this->authorize('create', Media::class);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'media/'.now()->format('Y/m'),
            Str::uuid().'.'.$extension,
            'public'
        );

        Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.media.index')
            ->with('success', 'File uploaded successfully.');
    }

    public function update(UpdateMediaRequest $request, Media $medium): RedirectResponse
    {
        $this->authorize('update', $medium);

        $medium->update($request->validated());

        return redirect()->back()->with('success', 'Media updated.');
    }

    public function destroy(Media $medium): RedirectResponse
    {
        $this->authorize('delete', $medium);

        Storage::disk($medium->disk)->delete($medium->path);
        $medium->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'File deleted.');
    }
}
