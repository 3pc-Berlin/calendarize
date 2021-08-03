<?php

/**
 * Logical configuration group.
 */
declare(strict_types=1);

namespace HDNET\Calendarize\Domain\Model;

use HDNET\Autoloader\Annotation\DatabaseField;
use HDNET\Autoloader\Annotation\DatabaseTable;
use HDNET\Autoloader\Annotation\SmartExclude;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Logical configuration group.
 *
 * @DatabaseTable
 * @SmartExclude(excludes={"Language"})
 */
class ConfigurationGroup extends AbstractModel
{
    /**
     * Title.
     *
     * @var string
     * @DatabaseField("string")
     */
    protected $title;

    /**
     * Configurations.
     *
     * @var string
     * @DatabaseField("string")
     */
    protected $configurations;

    use ImportTrait;

    /**
     * Hidden.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get configurations.
     *
     * @return int[]
     */
    public function getConfigurationIds()
    {
        return GeneralUtility::intExplode(',', $this->configurations);
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }
}
