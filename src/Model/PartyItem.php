<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class PartyItem extends AbstractItem
{
    /**
     * @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56
     */
    public function __construct(
        // Name
        // string
        // Displayed name of party.
        public readonly string $name,

        // EMail
        // string
        // Contact e-mail.
        public readonly string $email,

        // Type
        // string
        // Party type (see below).
        public readonly string $type,

        // Id
        // int
        // Internal identiﬁer of party - not visible in F2.
        public readonly int $id,

        // PartyNumber
        // int
        // Internal identiﬁer of party - displayed in F2 party details window.
        public readonly int $partyNumber,

        // SynchronizationKey
        // string
        // Identiﬁer for synchronizing parties with external repositories such as Windows
        // Active Directory.

        // CPRCVR
        // string
        // Personal security number or VAT number.

        // CVR_P
        // string
        // Danish P-number extension to CVR (VAT) number.
    )
    {
    }

    public static function fromSimpleXMLElement(\SimpleXMLElement $sxe)
    {
        return new static(
            name: (string) $sxe->Name,
            email: (string) $sxe->Email,
            type: (string) $sxe->Type,
            id: (int) $sxe->Id,
            partyNumber: (int) $sxe->PartyNumber,
        );
    }

    public function __toString()
    {
        return sprintf('Party: %s (%s)', $this->name, $this->type);
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'id' => $this->id,
            'partyNumber' => $this->partyNumber,
        ];
    }
}
