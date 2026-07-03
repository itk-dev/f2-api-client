<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Atom extends AbstractItem
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
        $this->published = $this->createDateTime($sxe->published);
        $this->updated = $this->createDateTime($sxe->updated);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->title, $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'published' => $this->published->format(\DateTimeInterface::ATOM),
            'updated' => $this->updated->format(\DateTimeInterface::ATOM),
        ];
    }
}
