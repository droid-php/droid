<?php

namespace Droid;

use RuntimeException;

class Utils
{
    public static function absoluteFilename($filename)
    {
        switch ($filename[0]) {
            case '/':
                // absolute filename
                break;
            case '~':
                // relative to home
                $home = getenv("HOME");
                if (!$home) {
                    throw new RuntimeException("Can't use ~/ when `HOME` environment variable is undefined");
                }
                $filename = $home . '/' . substr($filename, 2);
                break;
            default:
                // relative from pwd/cwd
                $filename = getcwd() . '/' . $filename;
                break;
        }
        return $filename;
    }
}
