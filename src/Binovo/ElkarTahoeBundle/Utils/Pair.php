<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Utils;

use Binovo\ElkarBackupBundle\Entity\Job;

class Pair
{
    private $_job;
    private $_retain;

    public function __construct(Job $job, $retain)
    {
        $this->_job = $job;
        $this->_retain = $retain;
    }

    public function getJob()
    {
        return $this->_job;
    }

    public function getRetain()
    {
        return $this->_retain;
    }
}