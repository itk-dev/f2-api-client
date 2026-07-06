<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Matter extends AbstractF2Item
{
    // resources/f2-rest-docs/f2-rest-docs-v13s.html#65
    public const string TYPE_INTERNAL = 'Internal';
    public const string TYPE_INBOUND = 'Inbound';
    public const string TYPE_OUTBOUND = 'Outbound';

    public string $matterNumber;
    public string $title;
    public string $type;
    public string $caseNumber;
    public \DateTimeImmutable $createdDate;
    public \DateTimeImmutable $modifiedDate;
    public PartyItemAbstract $createdBy;
    public PartyItemAbstract $modifiedBy;
    public ?PartyItemAbstract $responsible;

    // @mago-ignore analysis:non-documented-property,mixed-argument
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->id = (int) $sxe->Id;
        $this->matterNumber = (string) $sxe->MatterNumber;
        $this->caseNumber = (string) $sxe->CaseNumber;
        $this->title = (string) $sxe->Title;
        $this->type = (string) $sxe->Type;
        $this->createdDate = $this->createDateTime($sxe->CreatedDate);
        $this->modifiedDate = $this->createDateTime($sxe->ModifiedDate);
        $this->createdBy = PartyItemAbstract::fromSimpleXMLElement($sxe->CreatedBy);
        $this->modifiedBy = PartyItemAbstract::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = $sxe->Responsible ? PartyItemAbstract::fromSimpleXMLElement($sxe->Responsible) : null;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Matter %s: %s (#%d)', $this->matterNumber, $this->title, $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'MatterNumber' => $this->matterNumber,
            'Title' => $this->title,
            'Type' => $this->type,
            'CaseNumber' => $this->caseNumber,
            'CreatedDate' => $this->jsonSerializeDateTime($this->createdDate),
            'ModifiedDate' => $this->jsonSerializeDateTime($this->modifiedDate),
            'CreatedBy' => $this->createdBy->jsonSerialize(),
            'ModifiedBy' => $this->modifiedBy->jsonSerialize(),
            'Responsible' => $this->responsible?->jsonSerialize(),
        ] + parent::jsonSerialize();
    }
}
