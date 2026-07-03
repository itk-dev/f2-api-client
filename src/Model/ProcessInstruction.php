<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class ProcessInstruction extends F2Item
{
    public string $title;
    public string $path;
    public string $code;

    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->title = (string) $sxe->Title;
        $this->path = (string) $sxe->Path;
        $this->code = (string) $sxe->Code;

        return $this;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        $serialized = [
            'title' => $this->title,
            'path' => $this->path,
            'code' => $this->code,
        ] + parent::jsonSerialize();

        // ProcessInstruction doesn't have an ID.
        unset($serialized['id']);

        return $serialized;
    }
}
