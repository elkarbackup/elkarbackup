<?php
namespace App\Api;

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
        
        $mimetype = mime_content_type($path) ?: null;

        $file = new UploadedFile($path, $filename, $mimetype, null, true);
        
        return $file;
    }
}

