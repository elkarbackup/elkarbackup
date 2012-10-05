<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Command;

use Binovo\Tknika\TknikaBackupsBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateAdminUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('backups:create_admin')
             ->setDescription('Creates initial admin user');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $factory = $container->get('security.encoder_factory');
        $user = new User();
        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword('root', $user->getSalt());
        $user->setPassword($password);
        $user->setUsername('root');
        $user->setEmail('root@localhost');
        $em->persist($user);
        $em->flush();
    }
}