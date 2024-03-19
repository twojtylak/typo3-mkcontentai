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

namespace DMK\MkContentAi\Backend\EventListener;

use DMK\MkContentAi\ContextMenu\ContentAiItemProvider;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

final class FileListActionsEventListener
{
    protected ContentAiItemProvider $contentAiItemProvider;
    private Typo3Version $typo3Version;

    public function __construct(Typo3Version $typo3Version)
    {
        $this->typo3Version = $typo3Version;

        if (11 === $this->typo3Version->getMajorVersion()) {
            $this->contentAiItemProvider = GeneralUtility::makeInstance(ContentAiItemProvider::class, '', '');
        }

        if (12 === $this->typo3Version->getMajorVersion()) {
            $this->contentAiItemProvider = GeneralUtility::makeInstance(ContentAiItemProvider::class);
        }
    }

    public function handleEvent(ProcessFileListActionsEvent $event): void
    {
        $resourceIdentifier = $this->getFileIdentifier($event->getResource());

        if ('' === $resourceIdentifier) {
            return;
        }

        $actionItems = $event->getActionItems();

        foreach ($this->contentAiItemProvider->getItemsConfiguration() as $actionName => $values) {
            $this->contentAiItemProvider->setContext('sys_file', $resourceIdentifier, 'tree');

            if (false === $this->contentAiItemProvider->canRender($actionName, $values['type'])) {
                continue;
            }

            $uriGenerated = $this->contentAiItemProvider->generateUrl($actionName);
            $altTextAction = $this->buildAltTextAction($uriGenerated, LocalizationUtility::translate($values['label']));
            $actionItems[$actionName] = $altTextAction;
        }
        $event->setActionItems($actionItems);
    }

    private function getFileIdentifier(ResourceInterface $fileResource): string
    {
        return $fileResource->getStorage()->getUid().':'.$fileResource->getIdentifier();
    }

    private function buildAltTextAction(UriInterface $uriGenerated, ?string $labelActionName): string
    {
        switch ($this->typo3Version->getMajorVersion()) {
            case 11:
                $altTextAction = '
        <a href='.$uriGenerated.' class="btn btn-default" title="'.$labelActionName.'"><span class="t3js-icon icon icon-size-small icon-state-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-rocket" /></svg>
	</span>
</span></a>';

                return $altTextAction;

            case 12:
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $altTextAction = '
        <a href='.$uriGenerated.' class="dropdown-item dropdown-item-spaced"  title="'.$labelActionName.'"><span class="t3js-icon icon icon-size-small icon-state-default icon-actions-rocket">
	<span class="icon-markup">
	'.$iconFactory->getIcon('actions-rocket', Icon::SIZE_SMALL)->getMarkup().'
	</span>
</span></a>';

                return $altTextAction;

            default:
                return '';
        }
    }
}
