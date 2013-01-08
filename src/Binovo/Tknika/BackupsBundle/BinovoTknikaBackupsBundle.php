<?php

namespace Binovo\Tknika\BackupsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Binovo\Tknika\BackupsBundle\Lib\Globals;

class BinovoTknikaBackupsBundle extends Bundle
{
    public function boot()
    {
        // Set some static globals
        Globals::setBackupDir($this->container->getParameter('backup_dir'));
        Globals::setUploadDir($this->container->getParameter('upload_dir'));
    }

}
