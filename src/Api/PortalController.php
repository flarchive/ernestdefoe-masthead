<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Api;

use ErnestDefoe\Masthead\Portal\Payload;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /api/masthead/portal — the hero and the top posters.
 *
 * Asked for when a reader navigates to the portal inside the app. A full page
 * load gets the same data preloaded into the document instead (see
 * Content\Portal), so the first paint never waits on it.
 */
final class PortalController implements RequestHandlerInterface
{
    public function __construct(protected Payload $payload)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Gated like the forum itself: on a forum guests cannot view, a guest
        // gets nothing here either.
        RequestUtil::getActor($request)->assertCan('viewForum');

        return new JsonResponse($this->payload->for(RequestUtil::getActor($request)));
    }
}
