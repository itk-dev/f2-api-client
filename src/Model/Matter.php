<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Model;

final class Matter extends AbstractItem
{
    private CaseFile $caseFile;

    public function getCaseFile(): CaseFile
    {
        return $this->caseFile;
    }

    public function setCaseFile(CaseFile $caseFile): Matter
    {
        $this->caseFile = $caseFile;

        return $this;
    }
}
