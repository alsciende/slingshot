<?php

namespace App;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class UrlBuilder
{
    private PropertyAccessorInterface $propertyAccessor;
    private array $pathElements;

    public function __construct(string $path)
    {
        $this->pathElements = explode("/", $path);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    private function isSquareBraced(string $string): bool
    {
        return substr($string, 0, 1) === '[' && substr($string, -1) === ']';
    }

    public function build(array $jsonContent): string
    {
        foreach ($this->pathElements as $elem)
        {
            if ($this->isSquareBraced($elem)) {
                $finalPath[] = $this->propertyAccessor->getValue($jsonContent, $elem);
            } else {
                $finalPath[] = $elem;
            }
        }
        return implode("/", $finalPath);
    }
}