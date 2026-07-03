<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

abstract class AbstractItem implements \JsonSerializable, \Stringable
{
    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        return (new static())->setFromSimpleXMLElement($sxe);
    }

    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): self
    {
        return $this;
    }

    /**
     * @param class-string<AbstractItem> $class
     */
    protected static function listOf(string $class, \SimpleXMLElement $sxe): array
    {
        $items = [];
        foreach ($sxe as $child) {
            $items[] = $class::fromSimpleXMLElement($child);
        }

        return $items;
    }

    protected function createDateTime(\SimpleXMLElement|string $value): ?\DateTimeImmutable
    {
        $value = trim((string) $value);
        if ('' !== $value) {
            // @todo Adjust for time zones!
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
