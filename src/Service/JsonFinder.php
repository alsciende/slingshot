<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

class JsonFinder
{
    public function __construct()
    {   
    }

    public function readFolder(string $path): array
    {
        $paths = [];
        $finder = new Finder();
        $finder->files()->in($path)->name("*.json");

        foreach ($finder as $file)
        {
            $paths[] = $file->getRealPath();
        }

        return $paths;
    }

    public function find(string $path): array
    {
        $fileContents = [];

        $normalizedPath = Path::normalize($path);
        
        if ($normalizedPath === false || !file_exists($normalizedPath)) {
            throw new \RuntimeException("File not found: " . $normalizedPath);
        }

        $paths = is_dir($normalizedPath) 
            ? $this->readFolder($normalizedPath) 
            : [$normalizedPath];

        return $paths;
    }
};