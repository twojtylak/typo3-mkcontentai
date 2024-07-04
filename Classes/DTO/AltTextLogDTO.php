<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of TYPO3 CMS-based extension "mkcontentai" by DMK E-BUSINESS GmbH.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DMK\MkContentAi\DTO;

use DMK\MkContentAi\Domain\Model\AltTextLog;

class AltTextLogDTO
{
    private string $alternative;
    private ?\DateTime $createdAt;
    private string $fileUrl;

    public string $fileName;
    public int $fileMetadataUid;

    public function __construct(string $alternative, ?\DateTime $createdAt, string $fileUrl, string $fileName, int $fileMetadataUid)
    {
        $this->alternative = $alternative;
        $this->createdAt = $createdAt;
        $this->fileUrl = $fileUrl;
        $this->fileName = $fileName;
        $this->fileMetadataUid = $fileMetadataUid;
    }

    public static function fromAltTextLogs(AltTextLog $altTextLogs, string $fileUrl, string $fileName, int $fileMetadataUid): self
    {
        return new self(
            $altTextLogs->getAlternative(),
            $altTextLogs->getCreatedAt(),
            $fileUrl,
            $fileName,
            $fileMetadataUid,
        );
    }

    public function getAlternative(): string
    {
        return $this->alternative;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }
}
