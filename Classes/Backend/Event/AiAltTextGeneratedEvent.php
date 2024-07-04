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

namespace DMK\MkContentAi\Backend\Event;

final class AiAltTextGeneratedEvent
{
    protected string $tableName;
    protected int $resourceUid;
    protected string $altText;

    public function __construct(string $tableName, int $resourceUid, string $altText)
    {
        $this->tableName = $tableName;
        $this->resourceUid = $resourceUid;
        $this->altText = $altText;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getResourceUid(): int
    {
        return $this->resourceUid;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }
}
