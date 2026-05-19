<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Document extends AbstractItem
{
    private Matter $matter;

    public function getMatter(): Matter
    {
        return $this->matter;
    }

    public function setMatter(Matter $matter): Document
    {
        $this->matter = $matter;

        return $this;
    }
}
