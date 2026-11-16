<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

use ItkDev\F2ApiClient\Model\Matter\AccessLevel;
use ItkDev\F2ApiClient\Model\Matter\Status;
use ItkDev\F2ApiClient\Model\Matter\Type;

final class Matter extends AbstractF2Item
{
    public const int TITLE_MAX_LENGTH = 255;

    public ?string $matterNumber = null;
    public ?string $title = null;
    public ?Type $type = null;
    public ?AccessLevel $accessLevel = null;
    public ?Status $status = null;
    public ?bool $archived = null;
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
        $this->type = Type::from((string) $sxe->Type);
        $this->accessLevel = AccessLevel::from((string) $sxe->AccessLevel);
        $this->status = Status::from((string) $sxe->Status);
        $this->archived = $this->parseBoolean($sxe->Archived);
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

    #[\Override]
    public function apiSerialize(): array
    {
        $serialized = [
            'Title' => $this->title,
            'Type' => $this->type,
            'AccessLevel' => $this->accessLevel,
            'Status' => $this->status,
            'Archived' => $this->formatBoolean($this->archived ?? false),
        ];
        if ($this->responsible) {
            $serialized['Responsible'] = $this->responsible->apiSerialize();
        }

        return $serialized;
    }
}
