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

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

$externalMediaCollection = new MutationCollection(
    new Mutation(
        MutationMode::Extend,
        Directive::ImgSrc,
        new UriValue('*.stablediffusionapi.com'),
        new UriValue('*.windows.net'),
        new UriValue('*.r2.dev'),
        new UriValue('*.cloudfront.net')
    ),
    new Mutation(
        MutationMode::Extend,
        Directive::MediaSrc,
        TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme::data,
    ),
);

return Map::fromEntries(
    [Scope::backend(), $externalMediaCollection],
);
