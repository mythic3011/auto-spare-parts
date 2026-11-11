<?php

declare(strict_types=1);

final class ProductInOrderException extends DomainException
{
    /** @param list<int> $itemIds */
    public function __construct(public readonly array $itemIds)
    {
        parent::__construct('These products appear in order records and cannot be deleted. You can still update their stock and price.', 409);
    }
}
