<?php

namespace App\Platform\Pdf\Rendering;

use Illuminate\Support\Facades\File;

class ImageUtils
{
    /**
     * Convert local path to Base64 encoded data source
     *
     * @return string
     */
    public static function toBase64Src($path)
    {
        if (! $path || ! File::exists($path)) {
            return '';
        }

        return sprintf('data:%s;base64,%s', File::mimeType($path), base64_encode(File::get($path)));
    }
}
