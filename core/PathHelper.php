<?php

function qlpt_public_asset_url(?string $path, string $fallback = 'assets/images/default-room.jpg'): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    return BASE_URL . $path;
}

function qlpt_relative_public_asset_url(?string $path, string $fallback = 'assets/images/default-room.jpg'): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    return ltrim(str_replace('\\', '/', $path), '/');
}

