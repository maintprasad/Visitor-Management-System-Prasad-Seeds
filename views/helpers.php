<?php

declare(strict_types=1);

use Vsm\Core\Request;

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL aset + cache-busting berdasarkan waktu ubah file. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    return Request::basePath() . '/assets/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}
