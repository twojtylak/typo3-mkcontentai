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

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1697195476] =
    DMK\MkContentAi\ContextMenu\ContentAiItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Backend\Form\Element\InputTextElement::class] = [
    'className' => DMK\MkContentAi\Backend\Form\Element\InputTextWithAiAltTextSupportElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent::class] = [
    'className' => DMK\MkContentAi\Backend\EventListener\FileEventListener::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DMK\MkContentAi\Backend\Hooks\CustomDataHandler::class;

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1697195577] =
    DMK\MkContentAi\ContextMenu\ContentAiPageProvider::class;
