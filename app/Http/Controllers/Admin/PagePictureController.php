<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PagePictureController extends Controller
{
    // The list: one card for each page
    public function index()
    {
        $disk  = Storage::disk('public');
        $slots = collect(config('pagepictures.slots', []))->map(function (array $slot, string $key) use ($disk) {
            $path = 'page-headers/' . $key . '.jpg';
            $has  = $disk->exists($path);

            return $slot + [
                'key' => $key,
                'has' => $has,
                'url' => $has ? asset('storage/' . $path) . '?v=' . $disk->lastModified($path) : null,
                'kb'  => $has ? (int) round($disk->size($path) / 1024) : null,
            ];
        });

        return view('admin.page-pictures.index', [
            'slots'  => $slots,
            'width'  => (int) config('pagepictures.width', 1600),
            'height' => (int) config('pagepictures.height', 700),
            'max_kb' => (int) config('pagepictures.max_kb', 4096),
        ]);
    }

    // Save (or replace) the picture of one page
    public function update(Request $request, string $key)
    {
        abort_unless(array_key_exists($key, (array) config('pagepictures.slots', [])), 404);

        $bag = 'slot-' . $key;

        if (! function_exists('imagecreatetruecolor')) {
            return back()->withErrors(['picture' => 'This server cannot process pictures yet (the PHP "gd" extension is off).'], $bag);
        }

        $maxKb = (int) config('pagepictures.max_kb', 4096);

        // SECURITY: only real JPG, PNG or WebP pictures (the file itself is checked, not only its name)
        $request->validateWithBag($bag, [
            'picture' => ['required', 'file', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp',
                          'mimetypes:image/jpeg,image/png,image/webp', 'max:' . $maxKb,
                          'dimensions:min_width=800,min_height=350,max_width=6000,max_height=6000'],
            'focus'   => ['nullable', 'in:left,center,right'],
        ], [
            'picture.required'   => 'Please choose a picture.',
            'picture.extensions' => 'The picture must be a JPG, PNG or WebP file.',
            'picture.mimes'      => 'The picture must be a JPG, PNG or WebP file.',
            'picture.mimetypes'  => 'The picture must be a JPG, PNG or WebP file.',
            'picture.max'        => 'The picture must be smaller than ' . round($maxKb / 1024) . ' MB.',
            'picture.dimensions' => 'The picture must be at least 800 x 350 pixels, and not larger than 6000 x 6000.',
            'focus.in'           => 'Please choose left, middle or right.',
        ]);

        try {
            $jpeg = $this->makeHeaderJpeg($request->file('picture')->getRealPath(), (string) $request->input('focus', 'center'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['picture' => 'That picture could not be processed. Please try another one.'], $bag);
        }

        // Always the same file name, so a new picture replaces the old one
        Storage::disk('public')->put('page-headers/' . $key . '.jpg', $jpeg);

        return redirect()->route('admin.page-pictures.index')
            ->with('success', 'The picture for the ' . config('pagepictures.slots.' . $key . '.label') . ' was saved.')
            ->with('alert_title', 'Picture saved');
    }

    // Remove the picture: the page goes back to the plain green header
    public function destroy(string $key)
    {
        abort_unless(array_key_exists($key, (array) config('pagepictures.slots', [])), 404);

        Storage::disk('public')->delete('page-headers/' . $key . '.jpg');

        return redirect()->route('admin.page-pictures.index')
            ->with('success', 'The picture for the ' . config('pagepictures.slots.' . $key . '.label') . ' was removed.')
            ->with('alert_title', 'Picture removed');
    }

    // Crops to the header shape, resizes, and returns a clean JPEG (the original file is never kept)
    private function makeHeaderJpeg(string $path, string $focus): string
    {
        $width  = (int) config('pagepictures.width', 1600);
        $height = (int) config('pagepictures.height', 700);

        $info = getimagesize($path);
        $type = $info[2] ?? null;

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default        => false,
        };

        if (! $source) {
            throw new \RuntimeException('The picture could not be read.');
        }

        $source = $this->turnUpright($source, $path, (int) $type);

        $w     = imagesx($source);
        $h     = imagesy($source);
        $ratio = $width / $height;

        if (($w / $h) > $ratio) {
            $cropW = (int) round($h * $ratio);
            $cropH = $h;
        } else {
            $cropW = $w;
            $cropH = (int) round($w / $ratio);
        }

        $cropX = match ($focus) {
            'left'  => 0,
            'right' => $w - $cropW,
            default => (int) (($w - $cropW) / 2),
        };
        $cropY = (int) (($h - $cropH) / 2);

        $output = imagecreatetruecolor($width, $height);
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));   // a PNG with transparency gets a white background
        imagecopyresampled($output, $source, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);

        ob_start();
        imagejpeg($output, null, 82);
        $data = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($output);

        return $data;
    }

    // A phone photo can be stored sideways with a "turn me" note. This follows the note.
    private function turnUpright($image, string $path, int $type)
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $angle       = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle !== 0) {
            $turned = imagerotate($image, $angle, 0);
            if ($turned !== false) {
                return $turned;
            }
        }

        return $image;
    }
}