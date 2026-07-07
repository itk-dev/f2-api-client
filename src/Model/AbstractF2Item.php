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

    /**
     * Serialize with the names (paths) needed for JSON patch.
     *
     * @return array<string, mixed>
     */
    public function apiSerialize(): array
    {
        return [];
    }

    /**
     * Serialize with the names (paths) needed for JSON patch.
     *
     * Only names starting with a capital letter will be included in JSON patch'ing (cf. self::filterForJsonPatch).
     *
     * @see self::filterForJsonPatch()
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->apiSerialize()
        + [
            'id' => $this->id,
            'links' => $this->links->jsonSerialize(),
        ];
    }
}
