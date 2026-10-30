<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Matter extends AbstractF2Item
{
    // resources/f2-rest-docs/f2-rest-docs-v13s.html#65
    public const string TYPE_INTERNAL = 'Internal';
    public const string TYPE_INBOUND = 'Inbound';
    public const string TYPE_OUTBOUND = 'Outbound';

    public ?string $matterNumber = null;
    public ?string $title = null;
    public ?string $type = null;
    public ?string $caseNumber = null;
    public ?\DateTimeImmutable $createdDate = null;
    public ?\DateTimeImmutable $modifiedDate = null;
    public ?Party $createdBy = null;
    public ?Party $modifiedBy = null;
    public ?Party $responsible = null;

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
        $this->createdBy = Party::fromSimpleXMLElement($sxe->CreatedBy);
        $this->modifiedBy = Party::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = $sxe->Responsible ? Party::fromSimpleXMLElement($sxe->Responsible) : null;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Matter %s: %s (#%d)', $this->matterNumber, $this->title, $this->id);
    }

    public function apiSerialize(): array
    {
        return [
            'Title' => $this->title,
            'Type' => $this->type,
        ];
    }
}
