<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\Matter;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AccessLevel: string implements TranslatableInterface
{
    // resources/f2-rest-docs/f2-rest-docs-v13s.html#65
    case Involved = 'Involved';
    case Unit = 'Unit';
    case All = 'All';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Involved => $translator->trans('Involved'),
            self::Unit => $translator->trans('Unit'),
            self::All => $translator->trans('All'),
        };
    }
}
