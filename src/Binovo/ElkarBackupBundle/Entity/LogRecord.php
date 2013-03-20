<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class LogRecord
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $channel;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $dateTime;

    /**
     * @ORM\Column(type="integer")
     */
    protected $level;

    /**
     * @ORM\Column(type="string")
     */
    protected $levelName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $link;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $source;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $userName;

    public function __construct($channel, $dateTime, $level, $levelName, $message, $link = NULL, $source = NULL, $userId = NULL, $userName = NULL)
    {
        $this->channel   = $channel;
        $this->dateTime  = $dateTime;
        $this->level     = $level;
        $this->levelName = $levelName;
        $this->link      = $link;
        $this->message   = $message;
        $this->source    = $source;
        $this->userId    = $userId;
        $this->userName  = $userName;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set channel
     *
     * @param string $channel
     * @return LogRecord
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set dateTime
     *
     * @param \DateTime $dateTime
     * @return LogRecord
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set level
     *
     * @param integer $level
     * @return LogRecord
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set levelName
     *
     * @param string $levelName
     * @return LogRecord
     */
    public function setLevelName($levelName)
    {
        $this->levelName = $levelName;

        return $this;
    }

    /**
     * Get levelName
     *
     * @return string
     */
    public function getLevelName()
    {
        return $this->levelName;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return LogRecord
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return LogRecord
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return LogRecord
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userName
     *
     * @param string $userName
     * @return LogRecord
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return LogRecord
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }
}