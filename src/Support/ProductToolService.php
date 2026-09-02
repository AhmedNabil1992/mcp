<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Product\Models\Category;
use Webkul\Product\Models\Product;

class ProductToolService
{
    use HasQueryHelpers;

    public function productCatalogSummary(): array
    {
        $productModel = Product::class;
        $categoryModel = Category::class;

        return [
            'total_products'           => $this->count($productModel),
            'active_products'          => $this->count($productModel, fn ($q) => $q->where('is_active', true)),
            'services_count'           => $this->count($productModel, fn ($q) => $q->where('type', 'service')),
            'goods_count'              => $this->count($productModel, fn ($q) => $q->where('type', 'consu')->orWhere('type', 'product')),
            'total_categories'         => $this->count($categoryModel),
            'products_by_category'     => $this->groupCountLimit($productModel, 'category_id', null, 5),
            'average_sale_price'       => $this->average($productModel, 'price'),
            'average_cost_price'       => $this->average($productModel, 'cost'),
        ];
    }
}
