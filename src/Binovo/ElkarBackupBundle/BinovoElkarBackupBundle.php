<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

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
