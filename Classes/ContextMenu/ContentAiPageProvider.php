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

namespace DMK\MkContentAi\ContextMenu;

use DMK\MkContentAi\Domain\Model\TtContent;
use DMK\MkContentAi\Domain\Repository\TtContentRepository;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentAiPageProvider extends AbstractProvider
{
    /**
     * @var array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     callbackAction: string
     * }>
     */
    protected $itemsConfiguration = [
        'translateContentEasy' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelTextTranslateContentEasy',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'translateContentEasy',
        ],
        'translateContentPlain' => [
            'type' => 'item',
            'label' => 'LLL:EXT:mkcontentai/Resources/Private/Language/locallang_contentai.xlf:labelTextTranslateContentPlain',
            'iconIdentifier' => 'actions-edit-copy',
            'callbackAction' => 'translateContentPlain',
        ],
    ];

    public function setContext(string $table, string $identifier, string $context = ''): void
    {
        $this->table = $table;
        $this->identifier = $identifier;
        $this->context = $context;
    }

    public function canHandle(): bool
    {
        return 'tt_content' === $this->table;
    }

    public function getPriority(): int
    {
        return 55;
    }

    /**
     * @param array<string, array{
     *     type: string,
     *     label: string,
     *     iconIdentifier: string,
     *     additionalAttributes: array<string,string>,
     *     callbackAction: string
     * }> $items
     *
     * @return array<string, array{
     * type: string,
     * label: string,
     * iconIdentifier: string,
     * additionalAttributes: array<string,string>,
     * callbackAction: string
     * }>
     */
    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $localItems = $this->prepareItems($this->itemsConfiguration);

        return $items + $localItems;
    }

    /**
     * This method is called for each item this provider adds and checks if given item can be added.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canRender(string $itemName, string $type): bool
    {
        $canRender = false;
        $availableActions = ['translateContentEasy', 'translateContentPlain'];

        if (in_array($itemName, $availableActions)) {
            $canRender = $this->isPageContent() && $this->isValidTypeOfRecord((int) $this->identifier);
        }

        return $canRender;
    }

    public function isPageContent(): bool
    {
        return 'tt_content' === $this->table;
    }

    public function generateUrl(string $itemName): UriInterface
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $majorVersion = $typo3Version->getMajorVersion();
        $parameters = (11 === $majorVersion) ? $this->getParametersForVersion11($itemName) : $this->getParametersForVersion12();
        $pathInfo = $this->getPathInfo($itemName, $majorVersion);

        $this->updateParametersForItemName($parameters, $itemName, $majorVersion);

        /**
         * @var UriBuilder $uriBuilder
         */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $extendedUrl = $uriBuilder->buildUriFromRoutePath(
            $pathInfo,
            $parameters
        );

        return $extendedUrl;
    }

    /**
     * @return array<string>
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        $extendUrl = $this->generateUrl($itemName);

        switch ($typo3Version->getMajorVersion()) {
            case 12:
                return [
                    'data-callback-module' => '@t3docs/mkcontentai/context-menu-actions',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            case 11:
                return [
                    'data-callback-module' => 'TYPO3/CMS/Mkcontentai/ContextMenu',
                    'data-navigate-uri' => $extendUrl->__toString(),
                ];
            default:
                throw new \RuntimeException('TYPO3 version not supported');
        }
    }

    /**
     * @param array<string, mixed> &$parameters
     */
    private function updateParametersForItemName(array &$parameters, string $itemName, int $version): void
    {
        $actionMapping = [
            'translateContentEasy' => 'translateContentEasy',
            'translateContentPlain' => 'translateContentPlain',
        ];

        if (11 === $version) {
            $parameters['tx_mkcontentai_system_mkcontentaicontentai']['action'] = $actionMapping[$itemName] ?? '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getParametersForVersion11(string $itemName): array
    {
        $arrayWithParameters = 'translateContentEasy' === $itemName || 'translateContentPlain' === $itemName ?
            [
                'tx_mkcontentai_system_mkcontentaicontentai' => [
                    'controller' => 'AiTranslation',
                    'uid' => $this->identifier,
                ],
            ] :
            [];

        return $arrayWithParameters;
    }

    /**
     * @return array<string, mixed>
     */
    private function getParametersForVersion12(): array
    {
        return ['uid' => $this->identifier];
    }

    private function getPathInfo(string $itemName, int $version): string
    {
        $pathInfoMapping = [
            'translateContentEasy' => [
                12 => '/module/mkcontentai/AiTranslation/translateContentEasy',
                11 => '/module/system/MkcontentaiContentai',
            ],
            'translateContentPlain' => [
                12 => '/module/mkcontentai/AiTranslation/translateContentPlain',
                11 => '/module/system/MkcontentaiContentai',
            ],
        ];

        return $pathInfoMapping[$itemName][$version] ?? '';
    }

    private function isValidTypeOfRecord(int $uid): bool
    {
        $ttContentRepository = GeneralUtility::makeInstance(TtContentRepository::class);

        /** @var TtContent|null $record */
        $record = $ttContentRepository->findByUid($uid);
        (null === $record) ? $recordType = '' : $recordType = $record->getCtype();

        $availableAction = ['text', 'textpic', 'textmedia'];

        return in_array($recordType, $availableAction);
    }
}
