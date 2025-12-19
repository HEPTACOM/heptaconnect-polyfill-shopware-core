<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Plugin;

use Shopware\Core\Framework\Struct\Struct;

class PluginEntity extends Struct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $baseClass;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $composerName;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var bool
     */
    protected $managedByComposer;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var string|null
     */
    protected $author;

    /**
     * @var string|null
     */
    protected $copyright;

    /**
     * @var string|null
     */
    protected $license;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string|null
     */
    protected $upgradeVersion;

    /**
     * @var \DateTimeInterface|null
     */
    protected $installedAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $upgradedAt;

    /**
     * @var string|null
     */
    protected $iconRaw;

    /**
     * @var string|null
     */
    protected $icon;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $manufacturerLink;

    /**
     * @var string|null
     */
    protected $supportLink;

    /**
     * @var array|null
     */
    protected $changelog;

    /**
     * @var array
     */
    protected $autoload;

    public function jsonSerialize(): array
    {
        $serializedData = parent::jsonSerialize();
        unset($serializedData['iconRaw']);

        return $serializedData;
    }
}
