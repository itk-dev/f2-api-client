<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Matter extends F2Item
{
    public string $matterNumber;
    public string $title;
    public \DateTimeImmutable $createdDate;
    public \DateTimeImmutable $modifiedDate;
    public PartyItem $modifiedBy;
    public PartyItem $responsible;

    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->id = (int) $sxe->Id;
        $this->matterNumber = (string) $sxe->MatterNumber;
        $this->title = (string) $sxe->Title;
        $this->createdDate = new \DateTimeImmutable((string) $sxe->CreatedDate);
        $this->modifiedDate = new \DateTimeImmutable((string) $sxe->ModifiedDate);
        $this->modifiedBy = PartyItem::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = PartyItem::fromSimpleXMLElement($sxe->Responsible);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Matter %s: %s', $this->matterNumber, $this->matterNumber);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'matterNumber' => $this->matterNumber,
            'title' => $this->title,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible->jsonSerialize(),
        ] + parent::jsonSerialize();
    }
}
