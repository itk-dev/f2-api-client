<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Matter extends AbstractItem
{
    public function __construct(
        public readonly int $id,
        public readonly string $matterNumber,
        public readonly string $title,
        public readonly \DateTimeImmutable $createdDate,
        public readonly \DateTimeImmutable $modifiedDate,
        public readonly PartyItem $modifiedBy,
        public readonly PartyItem $responsible,
    )
    {
    }

    #[\Override]
    static function fromSimpleXMLElement(\SimpleXMLElement $sxe)
    {
        return new self(
            id: (int) $sxe->Id,
            matterNumber: (string) $sxe->MatterNumber,
            title: (string) $sxe->Title,
            createdDate: new \DateTimeImmutable((string) $sxe->CreatedDate),
            modifiedDate: new \DateTimeImmutable((string) $sxe->ModifiedDate),
            modifiedBy: PartyItem::fromSimpleXMLElement($sxe->ModifiedBy),
            responsible: PartyItem::fromSimpleXMLElement($sxe->Responsible),
        );
    }

    public function __toString()
    {
        return sprintf('Matter %s: %s', $this->matterNumber, $this->matterNumber);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'matterNumber' => $this->matterNumber,
            'title' => $this->title,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible->jsonSerialize(),
        ];
    }
}
