<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class CaseFile extends AbstractF2Item
{
    // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56
    public ?string $caseNumber = null;
    public ?string $title = null;
    public bool $closed = false;
    public ?JournalPlan $journalPlan = null;
    public ?ProcessInstruction $processInstruction = null;
    public ?\DateTimeImmutable $deadline = null;
    public ?\DateTimeImmutable $createdDate = null;
    public ?\DateTimeImmutable $modifiedDate = null;
    public ?Party $modifiedBy = null;
    public ?Party $responsible = null;

    // @mago-ignore analysis:non-documented-property,mixed-argument
    #[\Override]
    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->id = (int) $sxe->Id;
        $this->caseNumber = (string) $sxe->CaseNumber;
        $this->title = (string) $sxe->Title;
        $this->closed = 'true' === (string) $sxe->Closed;
        $this->journalPlan = JournalPlan::fromSimpleXMLElement($sxe->JournalPlan);
        $this->processInstruction = ProcessInstruction::fromSimpleXMLElement($sxe->ProcessInstruction);
        $this->deadline = $this->createDateTime($sxe->Deadline);
        $this->createdDate = $this->createDateTime($sxe->CreatedDate);
        $this->modifiedDate = $this->createDateTime($sxe->ModifiedDate);
        $this->modifiedBy = Party::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = $sxe->Responsible ? Party::fromSimpleXMLElement($sxe->Responsible) : null;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Case %s: %s (#%d)', $this->caseNumber, $this->title, $this->id);
    }

    #[\Override]
    public function apiSerialize(): array
    {
        return [
            'CaseNumber' => $this->caseNumber,
            'Title' => $this->title,
            'Closed' => $this->closed,
            'Deadline' => $this->deadline,
        ];
    }
}
