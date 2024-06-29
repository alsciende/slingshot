<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;

class JsonReader
{
    public function __construct()
    {
    }    

    public function read(string $path): array
    {
        $fileContent = json_decode(file_get_contents($path), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Error decoding JSON from file: " . __DIR__ . DIRECTORY_SEPARATOR . $path);
        }

        return $fileContent;
    }
};