<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class JournalPlan extends AbstractF2Item
{
    public string $title;
    public string $path;
    public string $code;

    // @mago-ignore analysis:non-documented-property
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
        $serialized =
            [
                'title' => $this->title,
                'path' => $this->path,
                'code' => $this->code,
            ] + parent::jsonSerialize();

        // JournalPlan doesn't have an ID.
        unset($serialized['id']);

        return $serialized;
    }
}
