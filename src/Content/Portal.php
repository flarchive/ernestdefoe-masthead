<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Content;

use ErnestDefoe\Masthead\ActiveTheme;
use ErnestDefoe\Masthead\Portal\Payload;
use Flarum\Forum\Content\Index;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * The portal's server half: its data, in the page.
 *
 * Preloaded so a full page load paints the hero at once instead of waiting
 * for a second request, and rendered as plain links so a search engine (or a
 * reader with scripts off) still finds the front page's stories.
 */
final class Portal
{
    public function __construct(protected Payload $payload, protected UrlGenerator $url, protected ActiveTheme $theme, protected Index $index)
    {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        /*
         * A visitor ernestdefoe/wardrobe is serving another theme has no
         * portal in their bundle: their app shows core's discussion list at
         * `/`. Hand them core's own index content (its preloaded list, its
         * crawlable links) rather than a portal payload nothing will read.
         */
        if (! $this->theme->for($request)) {
            return ($this->index)($document, $request);
        }

        $actor = RequestUtil::getActor($request);

        if (! $actor->can('viewForum')) {
            return $document;
        }

        $data = $this->payload->for($actor);
        $document->payload['mastheadPortal'] = $data;

        $items = '';
        foreach ($data['hero'] as $story) {
            $href = $story['kind'] === 'article'
                ? $this->url->to('forum')->base().$story['url']
                : $this->url->to('forum')->route('discussion', ['id' => $story['slug']]);

            $items .= '<li><a href="'.e($href).'">'.e($story['title']).'</a></li>';
        }

        if ($items !== '') {
            $document->content = '<div class="container"><ul>'.$items.'</ul></div>';
        }

        return $document;
    }
}
