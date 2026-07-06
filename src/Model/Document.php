<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Document extends AbstractF2Item
{
    public string $title;
    public ?string $description = null;
    public ?\DateTimeImmutable $createdDate = null;
    public ?\DateTimeImmutable $modifiedDate = null;

    // @mago-ignore analysis:non-documented-property,mixed-argument
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->id = (int) $sxe->Id;
        $this->title = (string) $sxe->Title;
        $this->description = (string) $sxe->Description;
        $this->createdDate = $this->createDateTime($sxe->CreatedDate);
        $this->modifiedDate = $this->createDateTime($sxe->ModifiedDate);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Document %s (#%d)', $this->title, $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'Title' => $this->title,
            'Description' => $this->description,
            'CreatedDate' => $this->jsonSerializeDateTime($this->createdDate),
            'ModifiedDate' => $this->jsonSerializeDateTime($this->modifiedDate),
        ] + parent::jsonSerialize();
    }
}
