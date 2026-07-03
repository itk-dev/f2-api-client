<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

abstract class AbstractItem implements \JsonSerializable, \Stringable
{
    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        // @mago-ignore analysis:unsafe-instantiation
        return (new static())->setFromSimpleXMLElement($sxe);
    }

    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        return $this;
    }

    /**
     * @template T of AbstractItem
     *
     * @param class-string<T> $class
     *
     * @return T[]
     */
    protected static function listOf(string $class, \SimpleXMLElement $sxe): array
    {
        $items = [];
        foreach ($sxe as $child) {
            if (!$child instanceof \SimpleXMLElement) {
                continue;
            }
            /** @var T $item */
            $item = $class::fromSimpleXMLElement($child);
            $items[] = $item;
        }

        return $items;
    }

    protected function createDateTime(\SimpleXMLElement|string $value): \DateTimeImmutable
    {
        // @todo Adjust for time zones!
        $apiTimeZone = new \DateTimeZone('UTC');
        $appTimeZone = new \DateTimeZone('UTC');

        return (new \DateTimeImmutable((string) $value, $apiTimeZone))->setTimeZone($appTimeZone);
    }
}
