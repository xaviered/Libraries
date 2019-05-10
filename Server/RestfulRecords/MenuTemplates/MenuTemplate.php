<?php

namespace ixavier\Libraries\Server\RestfulRecords\MenuTemplates;

use ixavier\Libraries\Server\RestfulRecords\Media\Image;

class MenuTemplate
{
    public $name;
    /** @var ProductCollection */
    public $categories;
    public $menuMediaType;

    private static $baseTemplate;

    public function __construct(string $name)
    {
        $this->categories = new ProductCollection();
        $this->name = $name;
    }

    public function getBaseTemplate(): self
    {
        if (!isset(static::$baseTemplate)) {
            static::$baseTemplate = new self('base');
        }

        return static::$baseTemplate;
    }

    public function getTemplatePath(): string
    {
        return '/menu-templates/'.$this->name;
    }

    public function getTemplateCss()
    {
        return $this->getTemplatePath().'/css/style.css';
    }

    public function getBackgroundImage(?string $menuMediaType = null): Image
    {
        $menuMediaType = $menuMediaType ?? $this->menuMediaType;
        $i = new Image();
        $i->src = $this->getTemplatePath().'/images/background-'.$menuMediaType.'.jpg';

        return $i;
    }

    public function loadData($menu)
    {
        $this->categories = new ProductCollection();
        $this->categories->loadData($menu);

        return $this;
    }

    public function setMediaType($type)
    {
        $this->menuMediaType = $type;

        return $this;
    }
}
