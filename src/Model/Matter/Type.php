<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\Matter;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Type: string implements TranslatableInterface
{
    // resources/f2-rest-docs/f2-rest-docs-v13s.html#65
    case Internal = 'Internal';
    case Inbound = 'Inbound';
    case Outbound = 'Outbound';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Internal => $translator->trans('Internal'),
            self::Inbound => $translator->trans('Inbound'),
            self::Outbound => $translator->trans('Outbound'),
        };
    }
}
