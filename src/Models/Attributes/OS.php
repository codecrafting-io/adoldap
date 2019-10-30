<?php

namespace CodeCrafting\AdoLDAP\Models\Attributes;

/**
 * OS class.
 */
class OS
{
    /**
     * Operating system name
     *
     * @var string
     */
    private $name;

    /**
     * Operating system version
     *
     * @var string
     */
    private $version;

    /**
     * Operating system version flavor
     *
     * @var string
     */
    private $flavor;

    /**
     * Operating system version build
     *
     * @var string
     */
    private $build;

    /**
     * Constructor
     *
     * @param string $fullname the fullname of operating system
     */
    public function __construct($fullname = '', $build = '')
    {
        if (stripos($fullname, 'windows') !== false) {
            $parts = explode(' ', $fullname);
            $this->name = $parts[0] ?? '';
            $this->version = $parts[1] ?? '';
            $this->flavor = $parts[2] ?? '';
        } else {
            $this->name = $fullname;
        }
        $this->build = str_replace(['(', ')'], '', preg_replace('/\s+/', '.', trim($build)));
    }

    /**
     * Get operating system name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set operating system name
     *
     * @param  string  $name  Operating system name
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get operating system version
     *
     * @return  string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set operating system version
     *
     * @param  string  $version  Operating system version
     * @return  self
     */
    public function setVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get operating system version flavor

     * @return  string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * Set operating system version flavor
     *
     * @param  string  $flavor  Operating system version flavor
     * @return  self
     */
    public function setFlavor(string $flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get OS name with version
     *
     * @return string
     */
    public function getVersionName()
    {
        return $this->name . ' ' . $this->version;
    }

    /**
     * Get Operating System fullname
     *
     * @return void
     */
    public function getFullname()
    {
        return $this->name . ' ' . $this->version . ' ' . $this->flavor;
    }

    /**
     * Get operating system version build
     *
     * @return  string
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * Set operating system version build
     *
     * @param  string  $build  Operating system version build
     * @return  self
     */
    public function setBuild(string $build)
    {
        $this->build = str_replace(['(', ')'], '', preg_replace('/\s+/', '.', trim($build)));

        return $this;
    }

    /**
     * Compare OS to this version
     *
     * @param OS $os
     * @return int
     */
    public function compareTo(OS $os)
    {
        return self::compare($this, $os);
    }

    /**
     * Compare OS between two versions
     *
     * @param OS $os1
     * @param OS $os2
     * @return int
     */
    public static function compare(OS $os1, OS $os2)
    {
        if ($os1 !== null && $os2 !== null) {
            $v = version_compare($os1->getVersion(), $os2->getVersion());
            if ($v == 0) {
                return version_compare($os1->getBuild(), $os2->getBuild());
            }

            return $v;
        } elseif ($os1 === null) {
            return -1;
        } else {
            return 1;
        }
    }
}
