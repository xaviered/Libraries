<?php

namespace ixavier\Libraries\Server\RestfulRecords\MenuTemplates;

class Product
{
    public function hasPrice()
    {
        return !empty($this->price);
    }

    public function getPrice(int $index)
    {
        return $this->price[$index] ?? null;
    }
}
