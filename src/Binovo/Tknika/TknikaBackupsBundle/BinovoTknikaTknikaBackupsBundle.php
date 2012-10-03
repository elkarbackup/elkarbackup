<?php

namespace Binovo\Tknika\TknikaBackupsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Binovo\Tknika\TknikaBackupsBundle\Lib\Globals;

class BinovoTknikaTknikaBackupsBundle extends Bundle
{
    public function boot()
    {
        // Set some static globals
        Globals::setBackupDir($this->container->getParameter('backup_dir'));
        Globals::setUploadDir($this->container->getParameter('upload_dir'));
    }

}
