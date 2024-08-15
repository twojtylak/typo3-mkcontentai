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

namespace DMK\MkContentAi\Http\Client\Action;

class SummAiAction extends BaseAction
{
    public const API_LINK = 'https://backend.summ-ai.com/api/';

    public function getActions(): array
    {
        return [
            'glossary' => 'v1/glossary/',
            'glossaryAi' => 'v1/glossary/ai/',
            'searchGlossary' => 'v1/glossary/search/',
            'translation' => 'v1/translation/',
            'getTranslation' => 'v1/translation/usage/',
        ];
    }
}
