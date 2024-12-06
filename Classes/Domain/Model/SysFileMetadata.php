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

/*
 * This file is part of the "MKContentAI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2024
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class SysFileMetadata extends AbstractEntity
{
    /**
     * @var ObjectStorage<SysFileMetadataAltTextLog>
     *
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ObjectStorage $altTextLogs;

    protected int $fileUid;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->altTextLogs = new ObjectStorage();
    }

    /**
     * @return ObjectStorage<SysFileMetadataAltTextLog>
     */
    public function getAltTextLogs(): ObjectStorage
    {
        return $this->altTextLogs;
    }

    public function addAltTextLog(SysFileMetadataAltTextLog $altTextLog): void
    {
        $this->altTextLogs->attach($altTextLog);
    }

    public function getFileUid(): int
    {
        return $this->fileUid;
    }
}
