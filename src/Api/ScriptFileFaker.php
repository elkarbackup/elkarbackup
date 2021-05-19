<?php
namespace App\Api;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ScriptFileFaker extends \Faker\Provider\Base
{
    public function upload($filename)
    {
        if (is_array($filename)) {
            $filename = \Faker\Provider\Base::randomElement($filename);
        }
        
        $path = sprintf('/tmp/elkarbackup-tests/uploads/%s', uniqid());
        
        $copy = copy($filename, $path);
        
        if (! $copy) {
            throw new \Exception('Copy failed');
        }
        
        $mimetype = MimeTypeGuesser::getInstance()->guess($path);
        $size = filesize($path);
        
        $file = new UploadedFile($path, $filename, $mimetype, $size, null, true);
        
        return $file;
    }
}

