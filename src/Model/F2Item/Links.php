<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\F2Item;

use ItkDev\F2ApiClient\Exception\RuntimeException;

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

    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe): self
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
     * @return Link
     */
    public function getLink(string $rel): array
    {
        if (!array_key_exists($rel, $this->links)) {
            throw new RuntimeException(sprintf('Cannot get link "%s"', $rel));
        }

        return $this->links[$rel];
    }

    public function getLinkUrl(string $rel): string
    {
        $link = $this->getLink($rel);
        if (!array_key_exists('href', $link) || !is_string($link['href'])) {
            throw new RuntimeException(sprintf('Cannot get URL for link "%s"', $rel));
        }

        return $link['href'];
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->links;
    }
}
