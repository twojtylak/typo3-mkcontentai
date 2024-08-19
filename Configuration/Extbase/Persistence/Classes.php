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

return [
    DMK\MkContentAi\Domain\Model\AltTextLog::class => [
        'tableName' => 'tx_mkcontentai_domain_model_alt_text_logs',
        'subclasses' => [
            'sys_file_metadata' => DMK\MkContentAi\Domain\Model\SysFileMetadataAltTextLog::class,
        ],
        'properties' => [
            'createdAt' => [
                'fieldName' => 'crdate',
            ],
        ],
    ],
    DMK\MkContentAi\Domain\Model\SysFileMetadata::class => [
        'tableName' => 'sys_file_metadata',
        'properties' => [
            'fileUid' => [
                'fieldName' => 'file',
            ],
        ],
    ],
    DMK\MkContentAi\Domain\Model\SysFileMetadataAltTextLog::class => [
        'tableName' => 'tx_mkcontentai_domain_model_alt_text_logs',
        'recordType' => 'sys_file_metadata',
        'properties' => [
            'createdAt' => [
                'fieldName' => 'crdate',
            ],
        ],
    ],
    DMK\MkContentAi\Domain\Model\TtContent::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'cType' => [
                'fieldName' => 'CType',
            ],
            'sorting' => [
                'fieldName' => 'sorting',
            ],
        ],
    ],
];
