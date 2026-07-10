<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

use ItkDev\F2ApiClient\Model\Collection\Item;

/**
 * A (simple) collection.
 */
final class Collection extends AbstractItem
{
    public string $title;
    /** @var Item[] */
    public array $items;

    // @mago-ignore analysis:non-documented-property,mixed-property-access
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        $this->title = (string) $sxe->Title;
        $this->items = [];
        /** @var \SimpleXMLElement[] $items */
        $items = $sxe->Items->Item;
        foreach ($items as $item) {
            $this->items[] = Item::fromSimpleXMLElement($item);
        }

        return $this;
    }
}
