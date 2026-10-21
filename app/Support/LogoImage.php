<?php

namespace App\Support;

final class LogoImage
{
    public const MAX_DIMENSION = 1600;

    /**
     * Convert a raster logo to a PNG with the outer white background removed.
     * White pixels connected to the border become transparent; white details
     * enclosed by the artwork are preserved.
     */
    public static function toTransparentPng(string $source, string $destination): bool
    {
        $data = @file_get_contents($source);

        if ($data === false) {
            return false;
        }

        $image = @imagecreatefromstring($data);

        if (! $image) {
            return false;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $scale = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            imagedestroy($image);
            $image = $resized;
            $width = $targetWidth;
            $height = $targetHeight;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        if (self::hasWhiteBorder($image, $width, $height)) {
            self::floodWhite($image, $width, $height);
        }

        $saved = imagepng($image, $destination, 6);

        imagedestroy($image);

        return $saved;
    }

    private static function hasWhiteBorder(\GdImage $image, int $width, int $height): bool
    {
        $white = 0;
        $total = 0;
        $stepX = max(1, (int) floor($width / 40));
        $stepY = max(1, (int) floor($height / 40));

        for ($x = 0; $x < $width; $x += $stepX) {
            $total += 2;
            $white += self::isWhite($image, $x, 0) ? 1 : 0;
            $white += self::isWhite($image, $x, $height - 1) ? 1 : 0;
        }

        for ($y = 0; $y < $height; $y += $stepY) {
            $total += 2;
            $white += self::isWhite($image, 0, $y) ? 1 : 0;
            $white += self::isWhite($image, $width - 1, $y) ? 1 : 0;
        }

        return $total > 0 && ($white / $total) >= 0.6;
    }

    private static function isWhite(\GdImage $image, int $x, int $y): bool
    {
        $rgba = imagecolorat($image, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F;

        if ($alpha >= 100) {
            return false;
        }

        return (($rgba >> 16) & 0xFF) >= 238
            && (($rgba >> 8) & 0xFF) >= 238
            && ($rgba & 0xFF) >= 238;
    }

    private static function floodWhite(\GdImage $image, int $width, int $height): void
    {
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        $seen = str_repeat("\0", $width * $height);
        $stack = [];

        $push = static function (int $x, int $y) use (&$stack, $width): void {
            $stack[] = $y * $width + $x;
        };

        for ($x = 0; $x < $width; $x++) {
            $push($x, 0);
            $push($x, $height - 1);
        }

        for ($y = 0; $y < $height; $y++) {
            $push(0, $y);
            $push($width - 1, $y);
        }

        while ($stack !== []) {
            $index = array_pop($stack);
            $y = intdiv($index, $width);
            $x = $index % $width;

            if ($seen[$index] !== "\0" || ! self::isWhite($image, $x, $y)) {
                continue;
            }

            while ($x > 0 && $seen[$y * $width + $x - 1] === "\0" && self::isWhite($image, $x - 1, $y)) {
                $x--;
            }

            $spanAbove = false;
            $spanBelow = false;

            while ($x < $width) {
                $current = $y * $width + $x;

                if ($seen[$current] !== "\0" || ! self::isWhite($image, $x, $y)) {
                    break;
                }

                $seen[$current] = "\1";
                imagesetpixel($image, $x, $y, $transparent);

                if ($y > 0) {
                    $up = ($y - 1) * $width + $x;
                    if ($seen[$up] === "\0" && self::isWhite($image, $x, $y - 1)) {
                        if (! $spanAbove) {
                            $push($x, $y - 1);
                            $spanAbove = true;
                        }
                    } else {
                        $spanAbove = false;
                    }
                }

                if ($y < $height - 1) {
                    $down = ($y + 1) * $width + $x;
                    if ($seen[$down] === "\0" && self::isWhite($image, $x, $y + 1)) {
                        if (! $spanBelow) {
                            $push($x, $y + 1);
                            $spanBelow = true;
                        }
                    } else {
                        $spanBelow = false;
                    }
                }

                $x++;
            }
        }
    }
}
