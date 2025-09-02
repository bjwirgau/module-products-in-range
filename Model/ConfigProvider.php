<?php

namespace CrimsonAgility\ProductsInRange\Model;

use CrimsonAgility\ProductsInRange\Api\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var ScopeConfigInterface  */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface  $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_PATH_CAR_PROFILE_ENABLED, $scope, $storeId);
    }

}