<?php
require_once __DIR__ . '/../core/Captcha.php';

session_start();

$key = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['key'] ?? 'site_captcha');
$captcha = Captcha::generate($key);
$code = $captcha['code'];

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (extension_loaded('gd')) {
    $width = 190;
    $height = 56;
    $image = imagecreatetruecolor($width, $height);

    $bg = imagecolorallocate($image, 244, 248, 255);
    $ink = imagecolorallocate($image, 16, 24, 40);
    $muted = imagecolorallocate($image, 100, 116, 139);
    $lineA = imagecolorallocate($image, 37, 99, 235);
    $lineB = imagecolorallocate($image, 20, 184, 166);
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);

    for ($i = 0; $i < 7; $i++) {
        $color = $i % 2 === 0 ? $lineA : $lineB;
        imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $color);
    }

    for ($i = 0; $i < 90; $i++) {
        imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $muted);
    }

    $x = 22;
    for ($i = 0; $i < strlen($code); $i++) {
        $y = random_int(16, 25);
        imagestring($image, 5, $x, $y, $code[$i], $ink);
        $x += random_int(28, 32);
    }

    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}

header('Content-Type: image/svg+xml; charset=UTF-8');
$escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
$lines = '';
for ($i = 0; $i < 8; $i++) {
    $x1 = random_int(0, 190);
    $y1 = random_int(0, 56);
    $x2 = random_int(0, 190);
    $y2 = random_int(0, 56);
    $color = $i % 2 === 0 ? '#2563eb' : '#14b8a6';
    $lines .= "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"$color\" stroke-opacity=\"0.45\" stroke-width=\"1.4\"/>";
}

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="190" height="56" viewBox="0 0 190 56">
  <rect width="190" height="56" rx="10" fill="#f4f8ff"/>
  $lines
  <text x="25" y="37" font-family="Consolas, monospace" font-size="28" font-weight="800" letter-spacing="9" fill="#101828" transform="skewX(-6)">{$escaped}</text>
  <path d="M10 42 C45 26, 78 51, 118 32 S165 19, 181 37" fill="none" stroke="#64748b" stroke-opacity="0.5" stroke-width="2"/>
</svg>
SVG;
