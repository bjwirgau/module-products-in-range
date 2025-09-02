<?php

namespace CrimsonAgility\ProductsInRange\Api;

use Magento\Store\Model\ScopeInterface;

interface ConfigProviderInterface
{
    const XML_PATH_ENABLED = 'productsinrange/general/enabled';

    /**
     * @param $storeId
     * @param $scope
     * @return bool|null
     */
    public function isEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;
}