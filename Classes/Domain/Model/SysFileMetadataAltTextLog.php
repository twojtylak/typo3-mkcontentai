<?php

declare(strict_types=1);

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

namespace DMK\MkContentAi\Domain\Model;

/**
 * This file is part of the "MKContentAI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024
 */
class SysFileMetadataAltTextLog extends AltTextLog
{
    public const TYPE = 'sys_file_metadata';

    protected SysFileMetadata $sysFileMetadata;

    public function __construct(SysFileMetadata $sysFileMetadata, string $alternative)
    {
        parent::__construct(self::TYPE, $alternative);
        $this->sysFileMetadata = $sysFileMetadata;
    }

    public function getFileUid(): int
    {
        return $this->sysFileMetadata->getFileUid();
    }
}
