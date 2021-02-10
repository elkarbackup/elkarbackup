<?php

namespace JMS\SecurityExtraBundle\Twig;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityExtension extends \Twig_Extension
{
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface|SecurityContextInterface $authorizationChecker
     */
    public function __construct($authorizationChecker)
    {
        if (!$authorizationChecker instanceof SecurityContextInterface && !$authorizationChecker instanceof AuthorizationCheckerInterface) {
            throw new InvalidArgumentException(sprintf('The first argument should be an instance of AuthorizationCheckerInterface or SecurityContextInterface, "%s" given.', is_object($authorizationChecker) ? get_class($authorizationChecker) : gettype($authorizationChecker)));
        }

        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('is_expr_granted', array($this, 'isExprGranted')),
        );
    }

    public function isExprGranted($expr, $object = null)
    {
        return $this->authorizationChecker->isGranted(array(new Expression($expr)), $object);
    }

    public function getName()
    {
        return 'jms_security_extra';
    }
}
