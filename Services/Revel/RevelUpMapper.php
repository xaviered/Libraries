<?php

namespace ixavier\Libraries\Services\Revel;

class RevelUpMapper
{
    protected $credentials;
    protected $url;
    /**
     * @var int
     */
    protected static $version = 1;

    const URL_MAPPER = [
        'login' => '/login',
        'export-products' => '/export-import/product/',
    ];

    const PRODUCT_FIELDS = [
        'CRV',
        'Grocery',
        'Inventory',
        'Product Dimensions',
        'Rewards',
        'Service Options',
        'Sold by Weight',
        'Vendor options',
        'Additional Categories',
        'Allow Price Override',
        'Alternate lookup',
        'Alternative Price',
        'Color Code',
        'Comments',
        'Course Number',
        'Custom Menu',
        'Dining options',
        'Disable Modifier Popup',
        'Display on online and 3rd party applications',
        'Do not allow sale of this product without stock on hand',
        'Dynamic cost',
        'EBT No',
        'Eligible for Discounts',
        'Hot or Cold',
        'Id',
        'Image Url',
        'Is Drink',
        'Kitchen Description',
        'Kitchen Print Name',
        'MSRP',
        'Manufacturer',
        'Maximum price',
        'Minimum price',
        'Not Returnable',
        'Preparation Time',
        'Print Tags',
        'Printers',
        'Product Group',
        'Product Third Party Id',
        'Prompt For Quantity',
        'Require Serial Number',
        'Reset Barcode',
        'Reset SKU',
        'Shopify SKU',
        'Size Chart',
        'Sort Order',
        'Tax Codes',
        'Tax Group',
        'Tax Units',
        'Vendor Name',
    ];

    public function __construct(string $url, array $credentials)
    {
        $this->credentials = $credentials;
        $this->url = rtrim($url, '/');
    }

    public function url(string $section, ?array $params = null): string
    {
        $url = $this->url.'/'.$this->path($section);

        if ($params) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    public function path(string $section)
    {
        if (isset(static::URL_MAPPER[$section])) {
            return static::URL_MAPPER[$section];
        }

        return $section;
    }

    public function productFields()
    {
        return static::PRODUCT_FIELDS;
    }

}
