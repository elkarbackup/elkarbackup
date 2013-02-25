<?php

namespace Binovo\ElkarBackupBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Binovo\ElkarBackupBundle\Lib\Globals;

class BinovoElkarBackupBundle extends Bundle
{
    public function boot()
    {
        // Set some static globals
        Globals::setBackupDir($this->container->getParameter('backup_dir'));
        Globals::setUploadDir($this->container->getParameter('upload_dir'));
    }

}
