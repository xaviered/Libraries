<?php

namespace ixavier\Libraries\Server\RestfulRecords\MenuTemplates;

use ixavier\Libraries\Server\RestfulRecords\Media\Image;

class MenuTemplate
{
    public $name;
    public $categories = [];
    public $menuMediaType;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getTemplatePath(): string
    {
        return '/ixavier-libraries/menu-templates/templates/'.$this->name;
    }

    public function getTemplateCss()
    {
        return $this->getTemplatePath().'/style.css';
    }

    public function getBackgroundImage(?string $menuMediaType = null): Image
    {
        $menuMediaType = $menuMediaType ?? $this->menuMediaType;
        $i = new Image();
        $i->src = $this->getTemplatePath().'/background-'.$menuMediaType.'.jpg';

        return $i;
    }

    public function loadData($menu)
    {
        $this->categories = new ProductCollection();
        $this->categories->loadData($menu);

        return $this;
    }

    public function setMenuMediaType($type)
    {
        $this->menuMediaType = $type;

        return $this;
    }
}
