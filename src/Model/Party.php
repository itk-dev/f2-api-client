<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Party extends AbstractF2Item
{
    // @see resources/f2-rest-docs/f2-rest-docs-v13s.html#56

    public ?string $name = null;
    public ?string $email = null;
    public ?string $type = null;
    public ?int $partyNumber = null;

    // @mago-ignore analysis:non-documented-property
    #[\Override]
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
    public function apiSerialize(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,
            'partyNumber' => $this->partyNumber,
        ]);
    }
}
