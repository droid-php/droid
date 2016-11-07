<?php

namespace Droid;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use Psr\Log\LoggerInterface;

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

        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($rii as $file) {

            $filename = $file->getFilename();
            $relDir = substr($file->getPathname(), strlen($src)+1, 0-strlen($filename));
            $writePath = $dest . DIRECTORY_SEPARATOR . $relDir . $filename;

            if ($file->isDir()) {
                if (! file_exists($writePath)) {
                    mkdir($writePath);
                }
                continue;
            }

            $content = $this->processContent(file_get_contents($file), $data);

            // do any rename operations
            //
            // rename Droid*Command.php to match the class name
            $m = [];
            if (array_key_exists('classname', $data)
                && preg_match('/Droid(\w*)Command.php/', $filename, $m)
            ) {
                $filename = sprintf('%s%sCommand.php', $data['classname'], $m[1]);
                $writePath = $dest . DIRECTORY_SEPARATOR . $relDir . $filename;
            }

            $this->logInfo(sprintf('Writing "%s"', $relDir . $filename));

            file_put_contents($writePath, $content);
        }
    }

    public function processContent($content, $data = [])
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{'  . $key . '}}', $value, $content);
        }
        return $content;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function logInfo($message)
    {
        if (! $this->logger) {
            return;
        }
        $this->logger->info($message);
    }
}
