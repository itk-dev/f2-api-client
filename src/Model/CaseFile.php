<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class CaseFile extends AbstractItem
{
    /**
     * @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56
     */
    public function __construct(
        // Id
        // int
        // Internal identiﬁer.
        public readonly int $id,

        // CaseNumber
        // string
        // Oﬃcial case number (for instance "2012 - 342").
        public readonly string $caseNumber,

        // Title
        // string
        // Case title.
        public readonly string $title,

        // CreatedDate
        // DateTime
        // Date and time the case was created.
        public readonly \DateTimeImmutable $createdDate,

        // ModifiedDate
        // DateTime
        // Date and time the case was last modiﬁed.
        public readonly \DateTimeImmutable $modifiedDate,

        // ModifiedBy
        // PartyItem
        // Last user who modiﬁed the case.
        public readonly PartyItem $modifiedBy,

        // Responsible
        // PartyItem
        // The party who is responsible for the case.
        public readonly PartyItem $responsible,

        // Link
        // List of Link (read-only)
        // One or more links to other related resources.

        // Matters
        // List of Matter
        // List of all accessible matters on the case.
        // /** @var Matter[] */
        // public readonly array $matters,
    ) {
    }

    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe)
    {
        return new self(
            id: (int) $sxe->Id,
            caseNumber: (string) $sxe->CaseNumber,
            title: (string) $sxe->Title,
            createdDate: new \DateTimeImmutable((string) $sxe->CreatedDate),
            modifiedDate: new \DateTimeImmutable((string) $sxe->ModifiedDate),
            modifiedBy: PartyItem::fromSimpleXMLElement($sxe->ModifiedBy),
            responsible: PartyItem::fromSimpleXMLElement($sxe->Responsible),
            // matters: static::listOf(Matter::class, $sxe->Matters),
        );
    }

    public function __toString()
    {
        return sprintf('Case %s: %s', $this->caseNumber, $this->title);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'caseNumber' => $this->caseNumber,
            'title' => $this->title,
            'createdDate' => $this->createdDate,
            'modifiedDate' => $this->modifiedDate,
            'modifiedBy' => $this->modifiedBy->jsonSerialize(),
            'responsible' => $this->responsible->jsonSerialize(),
            // 'matters' => array_map(static fn (Matter $matter) => $matter->jsonSerialize(), $this->matters),
        ];
    }
}
