<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\Collection;

use ItkDev\F2ApiClient\Model\AbstractF2Item;

final class Item extends AbstractF2Item
{
    public string $title;

    // @mago-ignore analysis:non-documented-property
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

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
            'Title' => $this->title,
        ] + parent::jsonSerialize();
    }
}
