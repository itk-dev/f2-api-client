<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\F2Item;

/**
 * @phpstan-type Link array{rel: string, href: string, title:string}
 */
class Links implements \JsonSerializable
{
    public function __construct(
        /** @var array<string, Link> */
        private readonly array $links,
    )
    {
    }

    static function fromSimpleXMLElement(\SimpleXMLElement $sxe): self
    {
        $links = [];
        foreach ($sxe->Link as $link) {
            $attributes = ((array) $link)['@attributes'] ?? null;
            if (is_array($attributes) && array_key_exists('rel', $attributes)) {
                $links[$attributes['rel']] = $attributes;
            }
        }

        return new self(links: $links);
    }

    /**
     * @param string $rel
     * @return Link
     */
    public function getLink(string $rel): ?array
    {
        return $this->links[$rel] ?? null;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->links;
    }
}
