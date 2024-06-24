<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

class JsonFileReader
{
    public function __construct()
    {
        
    }    

    public function readFile(string $path): array
    {
        return json_decode(file_get_contents($path), true);
    }

    public function readFolder(string $path): array
    {
        $contents = [];
        $finder = new Finder();
        $finder->files()->in($path)->name("*.json");
        foreach ($finder as $file)
        {
            $content = $this->readFile($file->getRealPath());

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Error decoding JSON from file: " . $file->getRealPath());
            }

            $contents[] = $content;
        }
        return $contents;
    }

    public function read(string $path): array
    {
        $fileContents = [];

        $normalizedPath = Path::normalize($path);
        
        if ($normalizedPath === false || !file_exists($normalizedPath)) {
            throw new \RuntimeException("File not found: " . $normalizedPath);
        }

        $fileContents = is_dir($normalizedPath) 
            ? $this->readFolder($normalizedPath) 
            : [$this->readFile($normalizedPath)];

        return $fileContents;
    }
};