<?php

namespace CrimsonAgility\ProductsInRange\Block\Customer;

use Magento\Framework\View\Element\Template;

class Search extends Template
{
    protected $_template = 'CrimsonAgility_ProductsInRange::form/product/search.phtml';

    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }
}   