<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

use ItkDev\F2ApiClient\Model\F2Item\Links;

abstract class F2Item extends AbstractItem
{
    public ?int $id = null;
    public ?Links $links = null;

    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->id = (int) $sxe->Id;
        $this->links = Links::fromSimpleXMLElement($sxe);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('F2 Item (%s)', $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'links' => $this->links?->jsonSerialize(),
        ];
    }
}
