<?php

declare(strict_types=1);

/**
 * Class FrontendUserMiddleware
 *
 * Copyright (C) Leipzig University Library 2020 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
namespace Ubl\Supportchat\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class FrontendUserMiddleware
 * @package Ubl\Supportchat\Middleware
 */
class FrontendUserMiddleware implements MiddlewareInterface
{
    /**
     * Typo3 Version
     *
     * @var string $typo3Version
     * @access private
     */
    private string $typo3Version;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        $this->typo3Version = (string)(new \TYPO3\CMS\Core\Information\Typo3Version());
    }

    /**
     * Dispatches the request to the corresponding eID class or eID script
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $userAspect = $context->getAspect('frontend.user');
        if (!$userAspect->isLoggedIn()) {

            $language = $request->getAttribute('language', $site->getDefaultLanguage());
            if (!$language instanceof SiteLanguage) {
                return $handler->handle($request);
            }

            if (version_compare($this->typo3Version, '11.5', '>=')) {
                $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? $site->getRootPageId();
                $type = $request->getQueryParams()['type'] ?? $request->getParsedBody()['type'] ?? '0';
                $pageArguments = $request->getAttribute('routing');
                if (!$pageArguments instanceof PageArguments) {
                    $pageArguments = new PageArguments((int)$id, (string)$type, []);
                }
            } else {
                $pageArguments = null;
            }

            $frontendUser = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                GeneralUtility::makeInstance(Context::class),
                $site,
                $language,
                $pageArguments,
                GeneralUtility::makeInstance(FrontendUserAuthentication::class)
            );
            // Write register
            $frontendUser->determineId($request);
            $GLOBALS['TSFE'] = $frontendUser;
        }
        return $handler->handle($request);
    }
}
