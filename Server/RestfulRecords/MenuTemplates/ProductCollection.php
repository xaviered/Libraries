<?php

namespace ixavier\Libraries\Server\RestfulRecords\MenuTemplates;

use ixavier\Libraries\Server\Core\ModelCollection;

class ProductCollection extends ModelCollection
{
    public function getDataPath(): string
    {
        return database_path().'/data';
    }

    public function loadData(string $name): self
    {
        $csv_file = $this->getDataPath().'/'.$name.'.csv';
        $fhandle = fopen($csv_file, 'r');

        // @todo: find the single method call
        foreach ($this->all() as $key => $product) {
            $this->offsetUnset($key);
        }

        $this->categories = new ModelCollection();

        $headers = fgetcsv($fhandle);
        while (($row = fgetcsv($fhandle))) {
            $product = new Product();
            $product->title = $row[0];
            $product->slug = str_slug($product->title, '_');
            $product->description = $row[1] ? '&nbsp;('.$row[1].')' : '';
            $product->price = [];
            $prices = strpos($row[2], ';') ? explode(';', $row[2]) : [$row[2]];
            foreach ($prices as $price) {
                if ($price === '-' || $price == '0') {
                    $product->price[] = '';
                } elseif (!empty($price)) {
                    $product->price[] = '$'.$price;
                }
            }
            $product->category = $row[8] ?? '';
            $product->sku = $row[9] ?? '';
            $product->extra = $row[10] ?? '';
            $product->sort = $row[11] ?? '';
            $product->notes = $row[12] ?? '';

            /** @var ModelCollection $category */
            if ($this->offsetExists($product->category)) {
                $category = $this->offsetGet($product->category);
            } else {
                $category = new Category();
                $category->title = $product->category;
                $category->slug = str_slug($product->category, '_');
                $category->products = new ModelCollection();
                $category->listType = 'block';
                $this->offsetSet($product->category, $category);
            }

            $category->products->push($product);
        }

        return $this;
    }
}
