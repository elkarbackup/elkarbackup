<?php

namespace JMS\SecurityExtraBundle\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * An introspectable access decision manager.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberingAccessDecisionManager implements AccessDecisionManagerInterface
{
    private $delegate;
    private $lastDecisionCall;

    public function __construct(AccessDecisionManagerInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Get arguments of last call to "Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface::decide" method.
     *
     * Purpose of this method is to allow custom access denied handler to access arguments which lead to denial of access
     * and to, per example, redirect to custom page or execute custom action based on those arguments to rectify made decision.
     *
     * @return array        Arguments of last call to "Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface::decide" method,
     *                      array(0 => TokenInterface $token, 1 => array $attributes, 2 => $object = null).
     */
    public function getLastDecisionCall()
    {
        return $this->lastDecisionCall;
    }

    /**
     * Decides whether the access is possible or not.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param array          $attributes An array of attributes associated with the method being invoked
     * @param object         $object     The object to secure
     *
     * @return Boolean true if the access is granted, false otherwise
     */
    public function decide(TokenInterface $token, array $attributes, $object = null)
    {
        $this->lastDecisionCall = array($token, $attributes, $object);

        return $this->delegate->decide($token, $attributes, $object);
    }

    /**
     * Configures the voters.
     *
     * @param VoterInterface[] $voters An array of VoterInterface instances
     */
    public function setVoters(array $voters)
    {
        if (!method_exists($this->delegate, 'setVoters')) {
            $interfaceName = 'Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface';

            throw new \RuntimeException(sprintf('Decorated implementation of "%s", instance of class "%s" does not have "setVoters" which is required for development environment.', $interfaceName, get_class($this->delegate)));
        }

        $this->delegate->setVoters($voters);
    }

    /**
     * Checks if the access decision manager supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return Boolean true if this decision manager supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        if (!method_exists('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface', 'supportsAttribute')) {
            throw new \LogicException('AccessDecisionManagerInterface::supportsAttribute() is not available anymore in symfony 3.0.');
        }

        return $this->delegate->supportsAttribute($attribute);
    }

    /**
     * Checks if the access decision manager supports the given class.
     *
     * @param string $class A class name
     *
     * @return true if this decision manager can process the class
     */
    public function supportsClass($class)
    {
        if (!method_exists('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface', 'supportsClass')) {
            throw new \LogicException('AccessDecisionManagerInterface::supportsClass() is not available anymore in symfony 3.0.');
        }

        return $this->delegate->supportsClass($class);
    }
}
