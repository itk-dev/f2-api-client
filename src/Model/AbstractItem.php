<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

abstract class AbstractItem implements \JsonSerializable, \Stringable
{
    protected string $id;
    protected string $title;
    protected \DateTimeImmutable $published;
    protected \DateTimeImmutable $updated;

    public function __construct(\SimpleXMLElement $entry)
    {
        $this->id = (string) $entry->id;
        $this->title = (string) $entry->title;
        $this->published = new \DateTimeImmutable((string) $entry->published);
        $this->updated = new \DateTimeImmutable((string) $entry->updated);
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
