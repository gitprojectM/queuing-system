<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    private const VIDEO_URL_KEY = 'display_video_url';

    public function edit(): Response
    {
        $videoUrl = Cache::rememberForever(self::VIDEO_URL_KEY, function () {
            return AppSetting::where('key', self::VIDEO_URL_KEY)->value('value') ?: '';
        });

        return Inertia::render('settings/Display', [
            'videoUrl' => $videoUrl,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Allow empty string to disable video.
            'video_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $videoUrl = trim((string) ($validated['video_url'] ?? ''));

        // If provided, require an actual URL OR a public relative path like /videos/foo.mp4
        if ($videoUrl !== '') {
            $isAbsoluteUrl = filter_var($videoUrl, FILTER_VALIDATE_URL) !== false;
            $isPublicPath = str_starts_with($videoUrl, '/');
            if (! $isAbsoluteUrl && ! $isPublicPath) {
                return back()->withErrors([
                    'video_url' => 'Video URL must be a full URL (https://...) or a public path starting with /.',
                ]);
            }
        }

        AppSetting::updateOrCreate(
            ['key' => self::VIDEO_URL_KEY],
            ['value' => $videoUrl]
        );

        Cache::forget(self::VIDEO_URL_KEY);

        return back()->with('status', 'saved');
    }
}
