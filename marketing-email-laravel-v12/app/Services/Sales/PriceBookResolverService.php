<?php

namespace App\Services\Sales;

use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\User;
use Illuminate\Support\Collection;

class PriceBookResolverService
{
    public function __construct(
        private readonly PriceBookAccessService $accessService,
    ) {}

    public function resolvePriceBook(User $user, int $priceBookId, string $customerType = 'personal'): ?PriceBook
    {
        $books = $this->accessService->getAccessiblePriceBooks($user, $customerType);

        return $books->firstWhere('id', $priceBookId);
    }

    public function getItemsForPriceBook(PriceBook $priceBook): Collection
    {
        return $priceBook->items()->with('servicePackage.service')->orderBy('sort_order')->get();
    }

    public function resolveItem(PriceBook $priceBook, int $itemId): ?PriceBookItem
    {
        return $priceBook->items()->find($itemId);
    }
}
