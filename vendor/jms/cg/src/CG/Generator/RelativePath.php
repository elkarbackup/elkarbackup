<?php

namespace CG\Generator;

class RelativePath
{
    private $relativePath;

    public function __construct($relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function getRelativePath()
    {
        return $this->relativePath;
    }
}