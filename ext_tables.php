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

defined('TYPO3') or exit;

$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']['mkcontentaiSettingsPermissions'] = [
    'header' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelMkcontentaiPermissions',
    'items' => [
        'settingsPermissions' => [
            'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelSettingsPageCurrentGroup',
        ],
        'tt_contentImagePrompt' => [
            'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelSettingsImageGenerationPrompt',
        ],
    ],
];

$GLOBALS['TCA']['tx_mkcontentai_domain_model_image']['ctrl']['security']['ignorePageTypeRestriction'] = [
    'EXT:mkcontentai/Resources/Private/Language/locallang_csh_tx_mkcontentai_domain_model_image.xlf',
];
