<?php
namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorService
{
    private $translator;
    
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator= $translator;
    }
    public function trans($msg, $params = array(), $domain = 'BinovoElkarBackup')
    {
        return $this->translator->trans($msg, $params, $domain);
    }
}

