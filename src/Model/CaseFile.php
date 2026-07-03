<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class CaseFile extends F2Item
{
    // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56

    public string $caseNumber;

    public string $title;
    public bool $closed;
    public JournalPlan $journalPlan;
    public ProcessInstruction $processInstruction;
    public ?\DateTimeImmutable $deadline;
    public \DateTimeImmutable $createdDate;
    public \DateTimeImmutable $modifiedDate;
    public PartyItem $modifiedBy;
    public ?PartyItem $responsible;

    // Link
    // List of Link (read-only)
    // One or more links to other related resources.
    // Matters
    // List of Matter
    // List of all accessible matters on the case.
    /** @var Matter[] */
    public array $matters;

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
        $this->modifiedBy = PartyItem::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = $sxe->Responsible ? PartyItem::fromSimpleXMLElement($sxe->Responsible) : null;

        $this->matters = static::listOf(Matter::class, $sxe->Matters);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Case %s: %s (#%d)', $this->caseNumber, $this->title, $this->id);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'caseNumber' => $this->caseNumber,
            'title' => $this->title,
            'closed' => $this->closed,
            'journalPlan' => $this->journalPlan->jsonSerialize(),
            'processInstruction' => $this->processInstruction->jsonSerialize(),
            'deadline' => $this->deadline,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible?->jsonSerialize(),
            'matters' => array_map(static fn (Matter $matter) => $matter->jsonSerialize(), $this->matters),
        ] + parent::jsonSerialize();
    }
}
