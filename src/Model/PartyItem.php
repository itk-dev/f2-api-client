<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class PartyItem extends F2Item
{
    // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56

    public string $name;

    // EMail
    // string
    // Contact e-mail.
    public string $email;

    // Type
    // string
    // Party type (see below).
    public string $type;

    // PartyNumber
    // int
    // Internal identiﬁer of party - displayed in F2 party details window.
    public int $partyNumber;

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

    public function setFromSimpleXMLElement(\SimpleXMLElement $sxe): static
    {
        parent::setFromSimpleXMLElement($sxe);

        $this->name = (string) $sxe->Name;
        $this->email = (string) $sxe->Email;
        $this->type = (string) $sxe->Type;
        $this->id = (int) $sxe->Id;
        $this->partyNumber = (int) $sxe->PartyNumber;

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('Party: %s (%s)', $this->name, $this->type);
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'id' => $this->id,
            'partyNumber' => $this->partyNumber,
        ] + parent::jsonSerialize();
    }
}
