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
