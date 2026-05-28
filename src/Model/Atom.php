<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Atom extends AbstractItem
{
    public function __construct(
        protected string $id,
        protected string $title,
        protected \DateTimeImmutable $published,
        protected \DateTimeImmutable $updated,
    )
    {
    }

    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe)
    {
        return new self(
            id: (string) $sxe->id,
            title: (string) $sxe->title,
            published: new \DateTimeImmutable((string) $sxe->published),
            updated: new \DateTimeImmutable((string) $sxe->updated),
        );
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->title, $this->id);
    }

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
