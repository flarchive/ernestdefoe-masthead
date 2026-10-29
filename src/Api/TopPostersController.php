<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Api;

use ErnestDefoe\Masthead\Portal\TopPosters;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * GET /api/masthead/top-posters — for the widget when it is placed somewhere
 * other than the portal (a Bespoke zone, a fof/forum-widgets-core section).
 */
final class TopPostersController implements RequestHandlerInterface
{
    public function __construct(protected TopPosters $topPosters)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('viewForum');

        return new JsonResponse(['topPosters' => $this->topPosters->get()]);
    }
}
