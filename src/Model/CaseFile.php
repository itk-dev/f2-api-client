<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class CaseFile extends F2Item
{
    // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56

    public string $caseNumber;

    public string $title;
    public \DateTimeImmutable $createdDate;
    public \DateTimeImmutable $modifiedDate;
    public PartyItem $modifiedBy;
    public PartyItem $responsible;

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
        $this->createdDate = new \DateTimeImmutable((string) $sxe->CreatedDate);
        $this->modifiedDate = new \DateTimeImmutable((string) $sxe->ModifiedDate);
        $this->modifiedBy = PartyItem::fromSimpleXMLElement($sxe->ModifiedBy);
        $this->responsible = PartyItem::fromSimpleXMLElement($sxe->Responsible);

        $this->matters = static::listOf(Matter::class, $sxe->Matters);

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Case %s: %s', $this->caseNumber, $this->title);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'caseNumber' => $this->caseNumber,
            'title' => $this->title,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible->jsonSerialize(),
            'matters' => array_map(static fn(Matter $matter) => $matter->jsonSerialize(), $this->matters),
        ] + parent::jsonSerialize();
    }
}
