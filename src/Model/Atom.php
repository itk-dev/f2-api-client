<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Atom extends AbstractItem implements \Stringable
{
    public string $id;
    public string $title;
    public \DateTimeImmutable $published;
    public \DateTimeImmutable $updated;

    // @mago-ignore analysis:non-documented-property,mixed-argument
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        $this->id = (string) $sxe->id;
        $this->title = (string) $sxe->title;
        /** @var \DateTimeImmutable $published */
        $published = $this->createDateTime($sxe->published);
        $this->published = $published;
        /** @var \DateTimeImmutable $updated */
        $updated = $this->createDateTime($sxe->updated);
        $this->updated = $updated;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->title, $this->id);
    }
}
