<?php

namespace CrimsonAgility\ProductsInRange\Model;

use CrimsonAgility\ProductsInRange\Api\ProductsInRangeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Validator\ValidateException;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;


class ProductsInRange implements ProductsInRangeInterface
{

    /**
     * @var \CrimsonAgility\ProductsInRange\Model\ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private Image $imageHelper;
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private Emulation $appEmulation;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;
    /**
     * @var \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku
     */
    private GetSalableQuantityDataBySku $getSalableQuantityBySku;
    /**
     * @var \Magento\InventoryApi\Api\GetSourceItemsBySkuInterface
     */
    private GetSourceItemsBySkuInterface $getSalableQuantity;
    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        GetSalableQuantityDataBySku $getSalableQuantityBySku,
        GetSourceItemsBySkuInterface $getSalableQuantity,
        SortOrderBuilder $sortOrderBuilder,
        StockRegistryInterface $stockRegistry,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        Emulation $appEmulation,
        Image $imageHelper
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->imageHelper = $imageHelper;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->getSalableQuantityBySku = $getSalableQuantityBySku;
        $this->getSalableQuantity = $getSalableQuantity;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     * @param string $direction
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \Exception
     */
    public function searchByPrice(float $minPrice, float $maxPrice, string $direction, int $page, int $pageSize): array
    {
        try {
            $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $this->validateInput($minPrice, $maxPrice, $direction, $page, $pageSize);

            $productCollection = $this->getProductCollection($minPrice, $maxPrice, $direction, $page, $pageSize);
            $productData = $this->mapProductItems($productCollection);

            $this->appEmulation->stopEnvironmentEmulation();
            return $productData;
        } catch (\Exception $e) {
            throw new \Exception('Error searching for products.');
        }
    }

    /**
     * @param float $minPrice
     * @param float $maxPrice
     * @param string $direction
     * @param int $page
     * @param int $pageSize
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    protected function getProductCollection(float $minPrice, float $maxPrice, string $direction, int $page, int $pageSize = 10)
    {
        $direction = ($direction == 'DESC') ? SortOrder::SORT_DESC : SortOrder::SORT_ASC;
        $sortOrder = $this->sortOrderBuilder
            ->setField('price')
            ->setDirection($direction)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('price', $minPrice, 'gteq')
            ->addFilter('price', $maxPrice, 'lteq')
            ->addFilter('visibility', Visibility::VISIBILITY_BOTH, 'eq')
            ->setSortOrders([$sortOrder])
            ->setPageSize($pageSize)
            ->setCurrentPage($page)
            ->create();

        return $this->productRepository->getList($searchCriteria);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterface $productCollection
     * @return array
     */
    protected function mapProductItems(ProductSearchResultsInterface $productCollection): array
    {
        $products = $productCollection->getItems();
        foreach ($products as $product) {
            $productData['items'][] = [
                'thumbnail' => $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'price' => $this->priceCurrency->format($product->getMinimalPrice()),
                'quantity' => $this->getTotalSourceQuantity($product),
                'link' => $product->getProductUrl(),
            ];
        }
        $productData['count'] = $productCollection->getTotalCount();

        return $productData;
    }

    /**
     * @param int $minPrice
     * @param int $maxPrice
     * @param string $sortOrder
     * @param int $page
     * @param int $pageSize
     * @return void
     * @throws \Magento\Framework\Validator\ValidateException
     */
    protected function validateInput(int $minPrice, int $maxPrice, string $sortOrder, int $page, int $pageSize): void
    {
        if (!$this->validateCurrency($minPrice) || !$this->validateCurrency($maxPrice)) {
            throw new ValidateException('Invalid currency amount.');
        }

        if ($sortOrder !== SortOrder::SORT_ASC && $sortOrder !== SortOrder::SORT_DESC) {
            throw new ValidateException('Invalid sort order.');
        }

        if ($page < 1) {
            throw new ValidateException('Invalid page.');
        }

        if ($pageSize !== 10 && $pageSize !== 20 && $pageSize !== 50) {
            throw new ValidateException('Invalid page size.');
        }
    }

    /**
     * @param $value
     * @return bool
     */
    protected function validateCurrency($value): bool
    {
        return is_float($value) || is_int($value);
    }

    protected function getTotalSourceQuantity(ProductInterface $product): int
    {
        $totalQuantity = 0;

        if ($product->getTypeId() == Product\Type::TYPE_SIMPLE) {
            return $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId())->getQty();
        }

        if ($product->getTypeId() == 'configurable') {
            $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($simpleProducts as $simpleProduct) {
                $totalQuantity += $this->stockRegistry->getStockItem($simpleProduct->getId(), $simpleProduct->getStore()->getWebsiteId())->getQty();
            }

            return $totalQuantity;
        }
    }
}