<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Generate thumbnail PNG untuk template marketplace.
 *
 * Karena tidak ada headless browser (Chrome/Puppeteer/Spatie Browsershot)
 * di project ini, kita pakai GD untuk composite thumbnail dari elemen HTML
 * yang relevan (background, logo, title, button).
 *
 * Approach: extract visual signature dari login.html + render ke 400x300
 * PNG dengan GD. Tidak 100% pixel-perfect seperti browser screenshot, tapi
 * cukup untuk thumbnail card marketplace.
 */
class TemplateThumbnailGenerator
{
    const WIDTH = 400;
    const HEIGHT = 300;
    const PREVIEW_FOLDER = 'original';

    /**
     * Generate thumbnail untuk template {id}.
     * Simpan ke: storage/app/public/templates/{id}/thumbnail.png
     * Return relative path untuk disimpan ke DB.
     */
    public static function generate(int $templateId, string $templateName, string $folderPath): ?string
    {
        if (!is_dir($folderPath)) {
            return null;
        }

        // Resolve path ke login.html untuk inspect
        $loginPath = $folderPath . '/login.html';
        $stylePath = $folderPath . '/style.css';
        $logoPath = null;

        // Cari logo (bisa di root atau img/)
        foreach (['logo.png', 'logo.jpg', 'img/logo.png', 'images/logo.png', 'assets/logo.png'] as $c) {
            if (file_exists($folderPath . '/' . $c)) {
                $logoPath = $folderPath . '/' . $c;
                break;
            }
        }

        // Extract signature
        $bgColor1 = self::extractBgColor($stylePath, '#4F46E5');
        $bgColor2 = self::extractBgColor2($stylePath, $bgColor1);
        $primaryColor = self::extractPrimaryColor($stylePath, '#2563EB');
        $titleText = self::extractTitle($loginPath) ?: $templateName;
        $subtitle = self::extractSubtitle($loginPath) ?: 'Premium Hotspot Template';
        $btnText = self::extractButtonText($loginPath) ?: 'Login Hotspot';

        // Render
        $img = self::renderThumbnail($bgColor1, $bgColor2, $primaryColor, $titleText, $subtitle, $btnText, $logoPath);

        // Save
        $thumbDir = dirname($folderPath); // parent (/{id})
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        $thumbPath = $thumbDir . '/thumbnail.png';

        if ($img !== null) {
            imagepng($img, $thumbPath, 6);
            imagedestroy($img);
            return 'templates/' . $templateId . '/thumbnail.png';
        }

        return null;
    }

    /**
     * Extract background color dari CSS (primary color atau :root)
     */
    private static function extractBgColor(string $stylePath, string $default = '#4F46E5'): string
    {
        if (!file_exists($stylePath)) return $default;
        $css = file_get_contents($stylePath);
        // Cari --bg-color, --primary, atau :root { --color-xxx: }
        if (preg_match('/--bg-color[^:]*:\s*([^;\}]+)/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $default);
        if (preg_match('/--primary[^:]*:\s*([^;\}]+)/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $default);
        if (preg_match('/background\s*:\s*(#[0-9a-fA-F]{3,6})/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $default);
        return $default;
    }

    private static function extractBgColor2(string $stylePath, string $primary): string
    {
        if (!file_exists($stylePath)) return $primary;
        $css = file_get_contents($stylePath);
        // Gradient second color
        if (preg_match('/--bg-color-?2?[^:]*:\s*([^;\}]+)/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $primary);
        // Hitung complementary color dari primary
        return self::complementaryColor($primary);
    }

    private static function extractPrimaryColor(string $stylePath, string $default = '#2563EB'): string
    {
        if (!file_exists($stylePath)) return $default;
        $css = file_get_contents($stylePath);
        if (preg_match('/--primary[^:]*:\s*([^;\}]+)/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $default);
        if (preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,6})/i', $css, $m)) return self::sanitizeHex(trim($m[1]), $default);
        return $default;
    }

    private static function extractTitle(string $loginPath): ?string
    {
        if (!file_exists($loginPath)) return null;
        $html = file_get_contents($loginPath);
        // Cari <h1>...</h1>
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        // Fallback: cari text besar pertama
        if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    private static function extractSubtitle(string $loginPath): ?string
    {
        if (!file_exists($loginPath)) return null;
        $html = file_get_contents($loginPath);
        if (preg_match('/<p[^>]*class\s*=\s*["\'][^"\']*(?:subtitle|description|tagline|welcome)[^"\']*["\'][^>]*>([^<]+)/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        if (preg_match('/<p[^>]*>([^<]{10,80})<\/p>/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    private static function extractButtonText(string $loginPath): ?string
    {
        if (!file_exists($loginPath)) return null;
        $html = file_get_contents($loginPath);
        if (preg_match('/<button[^>]*>([^<]+)<\/button>/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        if (preg_match('/type\s*=\s*["\']submit["\'][^>]*value\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private static function sanitizeHex(string $color, string $default = '#4F46E5'): string
    {
        $color = trim($color);
        if (preg_match('/^#?[0-9a-fA-F]{3,6}$/', $color)) {
            return str_starts_with($color, '#') ? $color : '#' . $color;
        }
        // rgb() / rgba() → convert ke hex sederhana
        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $color, $m)) {
            return sprintf('#%02X%02X%02X', $m[1], $m[2], $m[3]);
        }
        return $default;
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function complementaryColor(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        // Lighten & shift hue sedikit
        $r = min(255, (int) ($r * 0.7 + 100));
        $g = min(255, (int) ($g * 0.7 + 80));
        $b = min(255, (int) ($b * 0.7 + 160));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Render 400x300 PNG dengan gradient bg + glassmorphism card.
     */
    private static function renderThumbnail(
        string $bgColor1, string $bgColor2, string $primary,
        string $title, string $subtitle, string $btnText, ?string $logoPath
    ): ?\GdImage {
        if (!function_exists('imagecreatetruecolor')) return null;

        $w = self::WIDTH;
        $h = self::HEIGHT;
        $img = imagecreatetruecolor($w, $h);

        // Allocate colors
        [$r1, $g1, $b1] = self::hexToRgb($bgColor1);
        [$r2, $g2, $b2] = self::hexToRgb($bgColor2);
        [$pr, $pg, $pb] = self::hexToRgb($primary);

        // Vertical gradient bg
        for ($y = 0; $y < $h; $y++) {
            $ratio = $y / $h;
            $r = (int) ($r1 + ($r2 - $r1) * $ratio);
            $g = (int) ($g1 + ($g2 - $g1) * $ratio);
            $b = (int) ($b1 + ($b2 - $b1) * $ratio);
            $line = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $w, $y, $line);
        }

        // Glassmorphism card
        $cardW = 240;
        $cardH = 180;
        $cardX = ($w - $cardW) / 2;
        $cardY = ($h - $cardH) / 2;
        $cardColor = imagecolorallocatealpha($img, 255, 255, 255, 30);
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $cardColor);
        $borderColor = imagecolorallocatealpha($img, 255, 255, 255, 90);
        imagerectangle($img, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $borderColor);

        // Logo
        if ($logoPath && file_exists($logoPath) && @getimagesize($logoPath)) {
            $logoImg = @imagecreatefromstring(file_get_contents($logoPath));
            if ($logoImg) {
                $logoSize = 36;
                $logoX = $cardX + ($cardW - $logoSize) / 2;
                $logoY = $cardY + 16;
                imagecopyresampled($img, $logoImg, (int) $logoX, (int) $logoY, 0, 0, $logoSize, $logoSize, imagesx($logoImg), imagesy($logoImg));
                imagedestroy($logoImg);
            }
        } else {
            // Fallback: rounded square with primary color
            $logoSize = 36;
            $logoX = $cardX + ($cardW - $logoSize) / 2;
            $logoY = $cardY + 16;
            $logoColor = imagecolorallocatealpha($img, $pr, $pg, $pb, 80);
            imagefilledrectangle($img, (int) $logoX, (int) $logoY, (int) ($logoX + $logoSize), (int) ($logoY + $logoSize), $logoColor);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagestring($img, 5, (int) ($logoX + 12), (int) ($logoY + 10), strtoupper(substr($title, 0, 1)), $white);
        }

        // Title
        $white = imagecolorallocate($img, 255, 255, 255);
        $titleX = $cardX + 12;
        $titleY = $cardY + 70;
        imagestring($img, 5, (int) $titleX, (int) $titleY, mb_strimwidth($title, 0, 24, '…'), $white);

        // Subtitle
        $subtitleShort = mb_strimwidth($subtitle, 0, 30, '…');
        imagestring($img, 2, (int) $titleX, (int) ($titleY + 18), $subtitleShort, imagecolorallocate($img, 220, 220, 230));

        // Input mock
        $inputY = $cardY + 110;
        $inputColor = imagecolorallocatealpha($img, 255, 255, 255, 50);
        imagefilledrectangle($img, (int) $cardX + 16, (int) $inputY, (int) ($cardX + $cardW - 16), (int) ($inputY + 16), $inputColor);

        $inputY2 = $inputY + 22;
        imagefilledrectangle($img, (int) $cardX + 16, (int) $inputY2, (int) ($cardX + $cardW - 16), (int) ($inputY2 + 16), $inputColor);

        // Login button
        $btnY = $inputY2 + 24;
        $btnColor = imagecolorallocate($img, $pr, $pg, $pb);
        imagefilledrectangle($img, (int) $cardX + 16, (int) $btnY, (int) ($cardX + $cardW - 16), (int) ($btnY + 20), $btnColor);
        $btnTextShort = mb_strimwidth($btnText, 0, 18, '…');
        $btnTextWidth = imagefontwidth(3) * strlen($btnTextShort);
        imagestring($img, 3,
            (int) ($cardX + ($cardW - $btnTextWidth) / 2),
            (int) ($btnY + 4),
            $btnTextShort,
            $white
        );

        // Watermark "MT" bottom-right
        $watermark = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagestring($img, 2, $w - 30, $h - 14, 'MT', $watermark);

        return $img;
    }
}
