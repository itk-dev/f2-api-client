<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\Collection;

use ItkDev\F2ApiClient\Model\AbstractItem;

final class Item extends AbstractItem
{
    public int $id;
    public string $title;

    // @mago-ignore analysis:non-documented-property
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        $this->id = (int) $sxe->Id;
        $this->title = (string) $sxe->Title;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (#%d)', $this->title, $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
        ];
    }
}
