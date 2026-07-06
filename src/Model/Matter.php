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
            'matterNumber' => $this->matterNumber,
            'title' => $this->title,
            'type' => $this->type,
            'caseNumber' => $this->caseNumber,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'createdBy' => $this->createdBy->jsonSerialize(),
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible?->jsonSerialize(),
        ] + parent::jsonSerialize();
    }
}
