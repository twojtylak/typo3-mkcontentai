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

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata',
    [
        'alt_text_logs' => [
            'config' => [
                'type' => 'inline',
                'allowed' => 'tx_mkcontentai_domain_model_alt_text_logs',
                'foreign_table' => 'tx_mkcontentai_domain_model_alt_text_logs',
                'foreign_field' => 'sys_file_metadata',
                'foreign_match_fields' => [
                    'table_name' => 'sys_file_metadata',
                ],
                'behaviour' => [
                    'enableCascadingDelete' => 1,
                ],
            ],
        ],
    ],
);
