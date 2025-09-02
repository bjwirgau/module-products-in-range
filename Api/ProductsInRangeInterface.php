<?php

namespace CrimsonAgility\ProductsInRange\Api;

interface ProductsInRangeInterface
{
    /**
     * @param float $minPrice
     * @param float $maxPrice
     * @param string $direction
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function searchByPrice(float $minPrice, float $maxPrice, string $direction, int $page, int $pageSize): array;
}