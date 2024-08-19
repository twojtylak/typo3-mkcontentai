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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AltTextLog extends AbstractEntity
{
    protected string $tableName = '';

    protected string $alternative = '';

    protected ?\DateTime $createdAt = null;

    protected bool $deleted = false;

    public function __construct(string $tableName, string $alternative)
    {
        $this->tableName = $tableName;
        $this->alternative = $alternative;
        $this->createdAt = new \DateTime();
    }

    public function getAlternative(): string
    {
        return $this->alternative;
    }

    public function setAlternative(string $alternative): void
    {
        $this->alternative = $alternative;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    abstract public function getFileUid(): int;
}
