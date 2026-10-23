<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

abstract class AbstractItem implements \JsonSerializable
{
    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        // @mago-ignore analysis:unsafe-instantiation
        // @phpstan-ignore new.static
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
        /** @var \SimpleXMLElement $child */
        foreach ($sxe as $child) {
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

    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $values = [];
        foreach (get_object_vars($this) as $key => $value) {
            $values[$key] = match (true) {
                $value instanceof self => $value->toArray(),
                $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
                default => $value,
            };
        }

        return $values;
    }
}
