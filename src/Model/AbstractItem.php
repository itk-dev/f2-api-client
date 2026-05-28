<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

abstract class AbstractItem implements \JsonSerializable, \Stringable
{
    abstract static function fromSimpleXMLElement(\SimpleXMLElement $sxe);

    protected static function listOf(string $class, \SimpleXMLElement $sxe): array
    {
        $items = [];
        foreach ($sxe as $child) {
            $items[] = $child::class::fromSimpleXMLElement($child);
        }

        return $items;
    }
}
