<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

use ItkDev\F2ApiClient\Model\F2Item\Links;

abstract class AbstractF2Item extends AbstractItem implements \Stringable
{
    public ?int $id = null;
    public Links $links;

    public function __construct()
    {
        $this->links = new Links([]);
    }

    // @mago-ignore analysis:non-documented-property
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
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
            'links' => $this->links->jsonSerialize(),
        ];
    }

    protected function serializeDateTime(?\DateTimeImmutable $dateTime): ?string
    {
        return $dateTime?->format(\DateTimeInterface::ATOM);
    }
}
