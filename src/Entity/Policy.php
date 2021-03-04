<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use \DateTime;

/**
 * @ORM\Entity
 */
class Policy
{
    private $allRetains = array('Yearly', 'Monthly', 'Weekly', 'Daily', 'Hourly');

    /**
     * Returns the retains that should be run in $time in the right order.
     * @param  DateTime $time
     * @return array of strings
     */
    public function getRunnableRetains(DateTime $time)
    {
        $allRetains = $this->allRetains;
        $retains = array();
        list($year, $month, $day, $hour, $dayOfWeek) = explode('-', $time->format('Y-n-j-H:i-N'));
        foreach ($allRetains as $retain) {
            $getCount       = "get{$retain}Count";
            $getDaysOfMonth = "get{$retain}DaysOfMonth";
            $getDaysOfWeek  = "get{$retain}DaysOfWeek";
            $getHours       = "get{$retain}Hours";
            $getMonth       = "get{$retain}Months";
            if ($this->$getCount() != 0 &&
                ($this->$getDaysOfMonth() == null || preg_match('/^'.$this->$getDaysOfMonth().'$/', $day)) &&
                ($this->$getDaysOfWeek()  == null || preg_match('/^'.$this->$getDaysOfWeek() .'$/', $dayOfWeek)) &&
                ($this->$getHours()       == null || preg_match('/^'.$this->$getHours()      .'$/', $hour)) &&
                ($this->$getMonth()       == null || preg_match('/^'.$this->$getMonth()      .'$/', $month))) {
                $retains[] = $retain;
            }
        }

        return $retains;
    }

    /**
     * Returns the retains for this Policy in the order they should have in the config file.
     * @return array of array(string, int)
     */
    public function getRetains()
    {
        $allRetains = $this->allRetains;
        foreach ($allRetains as $retain) {
            $getCount       = "get{$retain}Count";
            if ($this->$getCount() != 0) {
                $retains[] = array($retain, $this->$getCount());
            }
        }

        return array_reverse($retains);
    }

    /**
     * Returns true if $retain only rotates the backups and doesn't
     * try to fetch data from the client.
     */
    public function isRotation($retain)
    {
        $retains = $this->getRetains();
        return !(count($retains) && $retains[0][0] == $retain);
    }

    /**
     * @Assert\IsTrue(message = "You forgot to specify hours during the day")
     */
    public function isHourlyHoursValid()
    {
        return empty($this->hourlyCount) || !empty($this->hourlyHours);
    }

    /**
     * Returns true if running the retain $retain requires a previous sync operation
     */
    public function mustSync($retain)
    {
        $retains = $this->getRetains();
        return $this->getSyncFirst() && count($retains) && $retains[0][0] == $retain;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * Regex to match the time (H:i format in date) for hourly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $hourlyHours;

    /**
     * Regex to match the day of month (d format in date) for hourly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $hourlyDaysOfMonth;

    /**
     * Regex to match the day of the week (N format in date) for hourly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $hourlyDaysOfWeek;

    /**
     * Regex to match the month (m format in date) for hourly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $hourlyMonths;

    /**
     * Number of retains
     * @ORM\Column(type="integer")
     */
    protected $hourlyCount = 0;

    /**
     * Regex to match the time (H:i format in date) for daily intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $dailyHours;

    /**
     * Regex to match the day of month (d format in date) for daily intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $dailyDaysOfMonth;

    /**
     * Regex to match the day of the week (N format in date) for daily intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $dailyDaysOfWeek;

    /**
     * Regex to match the month (m format in date) for daily intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $dailyMonths;

    /**
     * Number of retains
     * @ORM\Column(type="integer")
     */
    protected $dailyCount = 0;

    /**
     * Regex to match the time (H:i format in date) for weekly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $weeklyHours;

    /**
     * Regex to match the day of month (d format in date) for weekly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $weeklyDaysOfMonth;

    /**
     * Regex to match the day of the week (N format in date) for weekly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $weeklyDaysOfWeek;

    /**
     * Regex to match the month (m format in date) for weekly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $weeklyMonths;

    /**
     * Number of retains
     * @ORM\Column(type="integer")
     */
    protected $weeklyCount = 0;

    /**
     * Regex to match the time (H:i format in date) for monthly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $monthlyHours;

    /**
     * Regex to match the day of month (d format in date) for monthly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $monthlyDaysOfMonth;

    /**
     * Regex to match the day of the week (N format in date) for monthly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $monthlyDaysOfWeek;

    /**
     * Regex to match the month (m format in date) for monthly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $monthlyMonths;

    /**
     * Number of retains
     * @ORM\Column(type="integer")
     */
    protected $monthlyCount = 0;

    /**
     * Regex to match the time (H:i format in date) for yearly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $yearlyHours;

    /**
     * Regex to match the day of month (d format in date) for yearly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $yearlyDaysOfMonth;

    /**
     * Regex to match the day of the week (N format in date) for yearly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $yearlyDaysOfWeek;

    /**
     * Regex to match the month (m format in date) for yearly intervals
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $yearlyMonths;

    /**
     * Number of retains
     * @ORM\Column(type="integer")
     */
    protected $yearlyCount = 0;

    /**
     * Include expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $include;

    /**
     * Exclude expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $exclude;

    /**
     * Whether to use rsnapshot sync_first option
     * @ORM\Column(type="boolean")
     */
    protected $syncFirst;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Policy
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * Set name
     *
     * @param string $name
     * @return Policy
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set hourlyHours
     *
     * @param string $hourlyHours
     * @return Policy
     */
    public function setHourlyHours($hourlyHours)
    {
        $this->hourlyHours = $hourlyHours;

        return $this;
    }

    /**
     * Get hourlyHours
     *
     * @return string
     */
    public function getHourlyHours()
    {
        return $this->hourlyHours;
    }

    /**
     * Set hourlyDaysOfMonth
     *
     * @param string $hourlyDaysOfMonth
     * @return Policy
     */
    public function setHourlyDaysOfMonth($hourlyDaysOfMonth)
    {
        $this->hourlyDaysOfMonth = $hourlyDaysOfMonth;

        return $this;
    }

    /**
     * Get hourlyDaysOfMonth
     *
     * @return string
     */
    public function getHourlyDaysOfMonth()
    {
        return $this->hourlyDaysOfMonth;
    }

    /**
     * Set hourlyDaysOfWeek
     *
     * @param string $hourlyDaysOfWeek
     * @return Policy
     */
    public function setHourlyDaysOfWeek($hourlyDaysOfWeek)
    {
        $this->hourlyDaysOfWeek = $hourlyDaysOfWeek;

        return $this;
    }

    /**
     * Get hourlyDaysOfWeek
     *
     * @return string
     */
    public function getHourlyDaysOfWeek()
    {
        return $this->hourlyDaysOfWeek;
    }

    /**
     * Set hourlyMonths
     *
     * @param string $hourlyMonths
     * @return Policy
     */
    public function setHourlyMonths($hourlyMonths)
    {
        $this->hourlyMonths = $hourlyMonths;

        return $this;
    }

    /**
     * Get hourlyMonths
     *
     * @return string
     */
    public function getHourlyMonths()
    {
        return $this->hourlyMonths;
    }

    /**
     * Set hourlyCount
     *
     * @param integer $hourlyCount
     * @return Policy
     */
    public function setHourlyCount($hourlyCount)
    {
        $this->hourlyCount = $hourlyCount;

        return $this;
    }

    /**
     * Get hourlyCount
     *
     * @return integer
     */
    public function getHourlyCount()
    {
        return $this->hourlyCount;
    }

    /**
     * Set dailyHours
     *
     * @param string $dailyHours
     * @return Policy
     */
    public function setDailyHours($dailyHours)
    {
        $this->dailyHours = $dailyHours;

        return $this;
    }

    /**
     * Get dailyHours
     *
     * @return string
     */
    public function getDailyHours()
    {
        return $this->dailyHours;
    }

    /**
     * Set dailyDaysOfMonth
     *
     * @param string $dailyDaysOfMonth
     * @return Policy
     */
    public function setDailyDaysOfMonth($dailyDaysOfMonth)
    {
        $this->dailyDaysOfMonth = $dailyDaysOfMonth;

        return $this;
    }

    /**
     * Get dailyDaysOfMonth
     *
     * @return string
     */
    public function getDailyDaysOfMonth()
    {
        return $this->dailyDaysOfMonth;
    }

    /**
     * Set dailyDaysOfWeek
     *
     * @param string $dailyDaysOfWeek
     * @return Policy
     */
    public function setDailyDaysOfWeek($dailyDaysOfWeek)
    {
        $this->dailyDaysOfWeek = $dailyDaysOfWeek;

        return $this;
    }

    /**
     * Get dailyDaysOfWeek
     *
     * @return string
     */
    public function getDailyDaysOfWeek()
    {
        return $this->dailyDaysOfWeek;
    }

    /**
     * Set dailyMonths
     *
     * @param string $dailyMonths
     * @return Policy
     */
    public function setDailyMonths($dailyMonths)
    {
        $this->dailyMonths = $dailyMonths;

        return $this;
    }

    /**
     * Get dailyMonths
     *
     * @return string
     */
    public function getDailyMonths()
    {
        return $this->dailyMonths;
    }

    /**
     * Set dailyCount
     *
     * @param integer $dailyCount
     * @return Policy
     */
    public function setDailyCount($dailyCount)
    {
        $this->dailyCount = $dailyCount;

        return $this;
    }

    /**
     * Get dailyCount
     *
     * @return integer
     */
    public function getDailyCount()
    {
        return $this->dailyCount;
    }

    /**
     * Set weeklyHours
     *
     * @param string $weeklyHours
     * @return Policy
     */
    public function setWeeklyHours($weeklyHours)
    {
        $this->weeklyHours = $weeklyHours;

        return $this;
    }

    /**
     * Get weeklyHours
     *
     * @return string
     */
    public function getWeeklyHours()
    {
        return $this->weeklyHours;
    }

    /**
     * Set weeklyDaysOfMonth
     *
     * @param string $weeklyDaysOfMonth
     * @return Policy
     */
    public function setWeeklyDaysOfMonth($weeklyDaysOfMonth)
    {
        $this->weeklyDaysOfMonth = $weeklyDaysOfMonth;

        return $this;
    }

    /**
     * Get weeklyDaysOfMonth
     *
     * @return string
     */
    public function getWeeklyDaysOfMonth()
    {
        return $this->weeklyDaysOfMonth;
    }

    /**
     * Set weeklyDaysOfWeek
     *
     * @param string $weeklyDaysOfWeek
     * @return Policy
     */
    public function setWeeklyDaysOfWeek($weeklyDaysOfWeek)
    {
        $this->weeklyDaysOfWeek = $weeklyDaysOfWeek;

        return $this;
    }

    /**
     * Get weeklyDaysOfWeek
     *
     * @return string
     */
    public function getWeeklyDaysOfWeek()
    {
        return $this->weeklyDaysOfWeek;
    }

    /**
     * Set weeklyMonths
     *
     * @param string $weeklyMonths
     * @return Policy
     */
    public function setWeeklyMonths($weeklyMonths)
    {
        $this->weeklyMonths = $weeklyMonths;

        return $this;
    }

    /**
     * Get weeklyMonths
     *
     * @return string
     */
    public function getWeeklyMonths()
    {
        return $this->weeklyMonths;
    }

    /**
     * Set weeklyCount
     *
     * @param integer $weeklyCount
     * @return Policy
     */
    public function setWeeklyCount($weeklyCount)
    {
        $this->weeklyCount = (int)$weeklyCount;

        return $this;
    }

    /**
     * Get weeklyCount
     *
     * @return integer
     */
    public function getWeeklyCount()
    {
        return $this->weeklyCount;
    }

    /**
     * Set monthlyHours
     *
     * @param string $monthlyHours
     * @return Policy
     */
    public function setMonthlyHours($monthlyHours)
    {
        $this->monthlyHours = $monthlyHours;

        return $this;
    }

    /**
     * Get monthlyHours
     *
     * @return string
     */
    public function getMonthlyHours()
    {
        return $this->monthlyHours;
    }

    /**
     * Set monthlyDaysOfMonth
     *
     * @param string $monthlyDaysOfMonth
     * @return Policy
     */
    public function setMonthlyDaysOfMonth($monthlyDaysOfMonth)
    {
        $this->monthlyDaysOfMonth = $monthlyDaysOfMonth;

        return $this;
    }

    /**
     * Get monthlyDaysOfMonth
     *
     * @return string
     */
    public function getMonthlyDaysOfMonth()
    {
        return $this->monthlyDaysOfMonth;
    }

    /**
     * Set monthlyDaysOfWeek
     *
     * @param string $monthlyDaysOfWeek
     * @return Policy
     */
    public function setMonthlyDaysOfWeek($monthlyDaysOfWeek)
    {
        $this->monthlyDaysOfWeek = $monthlyDaysOfWeek;

        return $this;
    }

    /**
     * Get monthlyDaysOfWeek
     *
     * @return string
     */
    public function getMonthlyDaysOfWeek()
    {
        return $this->monthlyDaysOfWeek;
    }

    /**
     * Set monthlyMonths
     *
     * @param string $monthlyMonths
     * @return Policy
     */
    public function setMonthlyMonths($monthlyMonths)
    {
        $this->monthlyMonths = $monthlyMonths;

        return $this;
    }

    /**
     * Get monthlyMonths
     *
     * @return string
     */
    public function getMonthlyMonths()
    {
        return $this->monthlyMonths;
    }

    /**
     * Set monthlyCount
     *
     * @param integer $monthlyCount
     * @return Policy
     */
    public function setMonthlyCount($monthlyCount)
    {
        $this->monthlyCount = (int)$monthlyCount;

        return $this;
    }

    /**
     * Get monthlyCount
     *
     * @return integer
     */
    public function getMonthlyCount()
    {
        return $this->monthlyCount;
    }

    /**
     * Set yearlyHours
     *
     * @param string $yearlyHours
     * @return Policy
     */
    public function setYearlyHours($yearlyHours)
    {
        $this->yearlyHours = $yearlyHours;

        return $this;
    }

    /**
     * Get yearlyHours
     *
     * @return string
     */
    public function getYearlyHours()
    {
        return $this->yearlyHours;
    }

    /**
     * Set yearlyDaysOfMonth
     *
     * @param string $yearlyDaysOfMonth
     * @return Policy
     */
    public function setYearlyDaysOfMonth($yearlyDaysOfMonth)
    {
        $this->yearlyDaysOfMonth = $yearlyDaysOfMonth;

        return $this;
    }

    /**
     * Get yearlyDaysOfMonth
     *
     * @return string
     */
    public function getYearlyDaysOfMonth()
    {
        return $this->yearlyDaysOfMonth;
    }

    /**
     * Set yearlyDaysOfWeek
     *
     * @param string $yearlyDaysOfWeek
     * @return Policy
     */
    public function setYearlyDaysOfWeek($yearlyDaysOfWeek)
    {
        $this->yearlyDaysOfWeek = $yearlyDaysOfWeek;

        return $this;
    }

    /**
     * Get yearlyDaysOfWeek
     *
     * @return string
     */
    public function getYearlyDaysOfWeek()
    {
        return $this->yearlyDaysOfWeek;
    }

    /**
     * Set yearlyMonths
     *
     * @param string $yearlyMonths
     * @return Policy
     */
    public function setYearlyMonths($yearlyMonths)
    {
        $this->yearlyMonths = $yearlyMonths;

        return $this;
    }

    /**
     * Get yearlyMonths
     *
     * @return string
     */
    public function getYearlyMonths()
    {
        return $this->yearlyMonths;
    }

    /**
     * Set yearlyCount
     *
     * @param integer $yearlyCount
     * @return Policy
     */
    public function setYearlyCount($yearlyCount)
    {
        $this->yearlyCount = (int)$yearlyCount;

        return $this;
    }

    /**
     * Get yearlyCount
     *
     * @return integer
     */
    public function getYearlyCount()
    {
        return $this->yearlyCount;
    }

    /**
     * Set include
     *
     * @param string $include
     * @return Policy
     */
    public function setInclude($include)
    {
        $this->include = $include;

        return $this;
    }

    /**
     * Get include
     *
     * @return string
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * Set exclude
     *
     * @param string $exclude
     * @return Policy
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Get exclude
     *
     * @return string
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * Set syncFirst
     *
     * @param boolean $syncFirst
     * @return Policy
     */
    public function setSyncFirst($syncFirst)
    {
        $this->syncFirst = $syncFirst;

        return $this;
    }

    /**
     * Get syncFirst
     *
     * @return boolean
     */
    public function getSyncFirst()
    {
        return $this->syncFirst;
    }
}
