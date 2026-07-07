<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\F2Item;

use ItkDev\F2ApiClient\Exception\RuntimeException;
use ItkDev\F2ApiClient\Model\AbstractItem;

/**
 * @phpstan-type Link array{rel: string, href: string, title:string}
 */
final class Links extends AbstractItem
{
    public function __construct(
        /** @var array<string, Link> */
        private readonly array $links,
    ) {
    }

    #[\Override]
    public function __toString(): string
    {
        return self::class;
    }

    // @mago-ignore analysis:non-documented-property
    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        $links = [];
        /** @var \SimpleXMLElement[] $elements */
        $elements = $sxe->Link;
        foreach ($elements as $element) {
            $attributes = $element->attributes();
            if (null === $attributes) {
                continue;
            }
            $link = [];
            foreach ($attributes as $key => $value) {
                $link[$key] = (string) $value;
            }
            /** @var Link $link */
            if (array_key_exists('rel', $link)) {
                $links[$link['rel']] = $link;
            }
        }

        return new self(links: $links);
    }

    public function has(string $rel): bool
    {
        return array_key_exists($rel, $this->links);
    }

    /**
     * @return Link
     */
    public function get(string $rel): array
    {
        if (!$this->has($rel)) {
            throw new RuntimeException(sprintf('Cannot get link "%s"', $rel));
        }

        return $this->links[$rel];
    }

    public function getUrl(string $rel): string
    {
        $link = $this->get($rel);
        if (!array_key_exists('href', $link)) {
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
