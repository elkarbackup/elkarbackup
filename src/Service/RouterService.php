<?php
namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouterService
{
    private $router;
    
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }
    public function generateUrl($route)
    {
        return $this->router->generate($route);
    }
    public function generateClientRoute($id)
    {
        return $this->router->generate('editClient', array('id' => $id));
    }
    
    public function generateJobRoute($idJob, $idClient)
    {
        return $this->router->generate('editJob', array(
            'idClient' => $idClient,
            'idJob' => $idJob
        ));
    }
    
    public function generatePolicyRoute($id)
    {
        return $this->router->generate('editPolicy', array('id' => $id));
    }
    
    public function generateScriptRoute($id)
    {
        return $this->router->generate('editScript', array('id' => $id));
    }
    
    public function generateBackupLocationRoute($id)
    {
        return $this->router->generate('editBackupLocation', array('id' => $id));
    }
    
    public function generateUserRoute($id)
    {
        return $this->router->generate('editUser', array('id' => $id));
    }
}

