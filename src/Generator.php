<?php

namespace Droid;

use RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Generator
{
    public function generate($src, $dest, $data = [])
    {
        if (!file_exists($src)) {
            throw new RuntimeException("Source directory does not exist: " . $src);
        }
        if (!file_exists($dest)) {
            throw new RuntimeException("Destination directory does not exist: " . $dest);
        }
        
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));
        
        foreach ($rii as $file) {
            $path = substr($file->getPathname(), strlen($src) + 1);
            if ($file->isDir()) {
                $path = rtrim($path, '.');
                if (!file_exists($dest . '/' . $path)) {
                    mkdir($dest . '/' . $path);
                }
                continue;
            }
            echo "FILE: " . $path . "\n";
            $content = file_get_contents($src . '/' . $path);
            $content = $this->processContent($content, $data);
            file_put_contents($dest . '/' . $path, $content);
        }

    }
    
    public function processContent($content, $data = [])
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{'  . $key . '}}', $value, $content);
        }
        return $content;
    }
}
