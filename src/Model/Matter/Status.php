<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model\Matter;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Status: string implements TranslatableInterface
{
    // resources/f2-rest-docs/f2-rest-docs-v13s.html#65
    case InProgress = 'InProgress';
    case Closed = 'Closed';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::InProgress => $translator->trans('In progress'),
            self::Closed => $translator->trans('Closed'),
        };
    }
}
