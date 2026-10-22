import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import DiscussionHero from 'flarum/forum/components/DiscussionHero';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import CommentPost from 'flarum/forum/components/CommentPost';
import Avatar from 'flarum/common/components/Avatar';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import listItems from 'flarum/common/helpers/listItems';
import WidgetSlot from '../components/WidgetSlot';
import MoreInTag from './MoreInTag';
import groupBadge from './groupBadge';
import { bannerFor, readMinutes } from './lead';
import { has, isPhone, t } from '../util';

/*
 * The discussion page as an article with a discussion below it.
 *
 * 🚨 Every change here is an `extend` on core's own components, made in their
 * VIEWS, never a pass over the DOM after mount. A post that arrives later
 * (flarum/realtime, the reader's own reply, "load more") is rendered by the
 * same CommentPost view and so gets the same treatment, with nothing to
 * re-run. Core's DiscussionPage, PostStream and CommentPost stay in charge;
 * mentions, reactions, best answer, Scribe and everything else that extends
 * them keeps working.
 */

/** The discussion's tags, primary first (a child before its parent). */
function sortedTags(discussion: any): any[] {
  const tags = (discussion.tags?.() || []).filter(Boolean);
  const primary = tags.filter((tag: any) => tag.position() !== null).sort((a: any, b: any) => (a.isChild() ? -1 : 0) - (b.isChild() ? -1 : 0));
  const secondary = tags.filter((tag: any) => tag.position() === null);

  return [...primary, ...secondary];
}

function primaryTag(discussion: any): any {
  return sortedTags(discussion).find((tag) => tag.position() !== null) || null;
}

/** fof/best-answer's chosen post, when that extension is enabled and set one. */
function bestAnswer(discussion: any): any {
  if (!has('fof-best-answer')) return null;

  const post = discussion.bestAnswerPost?.();

  return post && post.number ? post : null;
}

function firstPost(discussion: any): any {
  const post = discussion.firstPost?.();

  return post && post.contentType?.() === 'comment' ? post : null;
}

function replyCount(discussion: any): number {
  return Math.max(0, (discussion.commentCount() || 1) - 1);
}

/** Core's reply action: the composer for a member, log in for a guest. */
function reply(discussion: any) {
  return DiscussionControls.replyAction.call(discussion, true, false).catch(() => {});
}

export default function topic() {
  // -- The story header ------------------------------------------------------

  override(DiscussionHero.prototype, 'className', function (original: () => string) {
    return original() + ' MastheadStory';
  });

  extend(DiscussionHero.prototype, 'items', function (this: any, items) {
    const discussion = this.attrs.discussion;
    const tags = sortedTags(discussion);
    const primary = primaryTag(discussion);

    // flarum/tags' own label row: Masthead draws the tags as the kicker below.
    if (items.has('tags')) items.remove('tags');

    if (tags.length) {
      const trail = primary ? [primary.parent?.() || null, primary].filter(Boolean) : [];

      items.add(
        'mastheadCrumbs',
        <nav className="MastheadCrumbs" aria-label={t('topic.breadcrumb')}>
          <Link href={app.route('index')}>{t('topic.forum')}</Link>
          {trail.map((tag: any) => [<span aria-hidden="true">›</span>, <Link href={app.route('tag', { tags: tag.slug() })}>{tag.name()}</Link>])}
        </nav>,
        30
      );

      items.add(
        'mastheadKicker',
        <div className="MastheadKickers">
          {tags.map((tag: any) => (
            <Link
              className={'MastheadKicker ' + (tag === primary ? 'MastheadKicker--solid' : 'MastheadKicker--outline')}
              href={app.route('tag', { tags: tag.slug() })}
              style={tag === primary && tag.color() ? { '--mh-kicker': tag.color() } : undefined}
            >
              {tag.name()}
            </Link>
          ))}
        </div>,
        20
      );
    }

    items.add('mastheadByline', <Byline discussion={discussion} />, -10);
  });

  // -- The lead post as the article; replies as cards ------------------------

  extend(CommentPost.prototype, 'elementAttrs', function (this: any, attrs: any) {
    const post = this.attrs.post;
    const extra = [];

    if (post.number() === 1) extra.push('MastheadArticle');
    if (bestAnswer(post.discussion())?.id() === post.id()) extra.push('MastheadPost--best');

    if (extra.length) attrs.className = [attrs.className, ...extra].join(' ');
  });

  extend(CommentPost.prototype, 'headerItems', function (this: any, items) {
    const post = this.attrs.post;

    if (post.number() === 1) return;

    const badge = groupBadge(post.user());
    if (badge) items.add('mastheadGroup', badge, 90);

    // ernestdefoe/cross-references already shows the number (and links it);
    // drawing a second one beside it would read as a glitch.
    if (!items.has('crossRefPostNumber')) {
      items.add('mastheadNumber', <span className="MastheadPostNumber">#{post.number()}</span>, -100);
    }
  });

  // The lead image, at the top of the article, from post #1's own HTML.
  extend(CommentPost.prototype, 'content', function (this: any, content: any[]) {
    const post = this.attrs.post;

    if (post.number() !== 1 || this.isEditing()) return;

    const image = bannerFor(post);
    if (image) {
      content.unshift(
        // 🚨 No key: core's content() array is unkeyed, and one keyed child
        // among unkeyed siblings is a Mithril error that blanks the post.
        <div className="MastheadLead">
          <img src={image} alt="" loading="eager" decoding="async" />
        </div>
      );
    }
  });

  /*
   * The banner IS the post's first picture, so the copy in the body is hidden
   * while the banner shows. Done in the post's own lifecycle hooks, which run
   * after every render of that post (an edit, a live update), never as a
   * one-off pass over the page.
   */
  const markLeadSource = function (this: any) {
    const post = this.attrs.post;
    if (post.number() !== 1 || !this.element) return;

    // Only the post's own picture has a copy in the body to hide; a Backdrop
    // banner never matches one.
    const image = !this.isEditing() ? bannerFor(post) : null;

    this.element.querySelectorAll('.Post-body img').forEach((img: HTMLImageElement) => {
      img.classList.toggle('MastheadLead-source', !!image && img.getAttribute('src') === image);
    });
  };

  extend(CommentPost.prototype, 'oncreate', markLeadSource);
  extend(CommentPost.prototype, 'onupdate', markLeadSource);

  // Copy link and share, under the article.
  extend(CommentPost.prototype, 'actionItems', function (this: any, items) {
    const post = this.attrs.post;

    if (post.number() !== 1) return;

    const url = app.forum.attribute<string>('baseUrl') + app.route.discussion(post.discussion()).replace(app.forum.attribute<string>('basePath'), '');

    items.add(
      'mastheadCopy',
      <Button
        className="Button Button--link MastheadShare"
        icon="fas fa-link"
        onclick={() =>
          navigator.clipboard?.writeText(url).then(
            () => app.alerts.show({ type: 'success' }, t('topic.link_copied')),
            () => {}
          )
        }
      >
        {t('topic.copy_link')}
      </Button>,
      -50
    );

    if (typeof navigator !== 'undefined' && (navigator as any).share) {
      items.add(
        'mastheadShare',
        <Button
          className="Button Button--link MastheadShare"
          icon="fas fa-share-nodes"
          onclick={() => (navigator as any).share({ title: post.discussion().title(), url }).catch(() => {})}
        >
          {t('topic.share')}
        </Button>,
        -60
      );
    }
  });

  // -- Inside the stream -----------------------------------------------------
  //
  // 🚨 By module NAME, not by import. PostStream is a lazy chunk in Flarum 2:
  // importing it hands back undefined at boot, `undefined.prototype` throws,
  // and every extend after it in this initializer silently never registers.
  // The string form waits for the chunk to load.

  /*
   * "Discussion · N replies", after the article. Core renders this list in a
   * keyed fragment of its own (`afterFirstPost`), so no key of Masthead's can
   * collide with the posts around it.
   */
  extend('flarum/forum/components/PostStream', 'afterFirstPostItems', function (this: any, items) {
    const count = replyCount(this.discussion);

    items.add('mastheadRepliesHeading', <h2 className="MastheadHeading MastheadReplies-heading">{t('topic.discussion_heading', { count })}</h2>, 100);
  });

  // On narrow screens the sidebar's "More in" sits at the very end instead.
  extend('flarum/forum/components/PostStream', 'endItems', function (this: any, items) {
    const tag = primaryTag(this.discussion);

    if (tag && !isWide()) {
      items.add(
        'mastheadMore',
        <div className="MastheadEnd" key="mastheadMore">
          <MoreInTag discussion={this.discussion} primary={tag} />
        </div>,
        -100
      );
    }
  });

  // -- The sidebar -----------------------------------------------------------

  extend(DiscussionPage.prototype, 'sidebarItems', function (this: any, items) {
    const discussion = this.discussion;

    if (!discussion || isPhone()) return;

    // Follow (flarum/subscriptions) and the controls move into the info card.
    const follow = items.has('subscription') ? items.get('subscription') : null;
    if (follow) items.remove('subscription');
    if (items.has('controls')) items.remove('controls');
    if (items.has('scrubber')) items.remove('scrubber');

    items.add('mastheadAbout', <AboutCard discussion={discussion} page={this} follow={follow} />, 100);

    const tag = primaryTag(discussion);
    if (tag && isWide()) items.add('mastheadMore', <MoreInTag discussion={discussion} primary={tag} />, 50);

    items.add('mastheadSlot', <WidgetSlot where="discussion" />, 10);
  });

  /*
   * The phone's floating Reply. Added to the page's own content, not around
   * the page: DiscussionPage's root element is the PageStructure, and wrapping
   * it in an array would change what `this.element` means to everything that
   * extends the page.
   */
  extend(DiscussionPage.prototype, 'view', function (this: any, vdom: any) {
    const discussion = this.discussion;

    if (!isPhone() || !discussion || this.loading || app.composer.isVisible()) return;
    if ((app.session.user && !discussion.canReply()) || !Array.isArray(vdom?.children)) return;

    vdom.children.push(
      <button type="button" className="MastheadFab MastheadFab--reply" onclick={() => reply(discussion)}>
        <Icon name="far fa-comment" />
        <span>{t('topic.reply')}</span>
      </button>
    );
  });
}

/** Wide enough for the sidebar beside the stream (the scope's 1100px). */
const wide = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(min-width: 1100px)') : null;
wide?.addEventListener?.('change', () => m.redraw());

function isWide(): boolean {
  return !!wide?.matches;
}

class Byline {
  view(vnode: any) {
    const discussion = vnode.attrs.discussion;
    const user = discussion.user();
    const lead = firstPost(discussion);
    const minutes = readMinutes(lead);
    const best = bestAnswer(discussion);
    const replies = replyCount(discussion);
    const participants = discussion.attribute('participantCount');

    return (
      <div className="MastheadByline">
        <Avatar user={user} className="MastheadByline-avatar" />
        <span className="MastheadByline-text">
          <span className="MastheadByline-who">
            {user ? <Link href={app.route.user(user)}>{user.displayName()}</Link> : t('topic.deleted_user')}
            {groupBadge(user)}
          </span>
          <span className="MastheadByline-meta">
            {dayjs(discussion.createdAt()).format('LL')}
            {minutes ? [' · ', t('topic.read_time', { count: minutes })] : null}
            {' · '}
            {t('latest.replies', { count: replies })}
            {participants ? <span className="MastheadByline-phoneOnly">{[' · ', t('topic.participants', { count: participants })]}</span> : null}
            {best ? (
              <Link className="MastheadByline-phoneBest" href={app.route.discussion(discussion, best.number())}>
                {' · '}
                {t('topic.jump_best_short')}
              </Link>
            ) : null}
          </span>
        </span>
        {best ? (
          <Link className="MastheadByline-best" href={app.route.discussion(discussion, best.number())}>
            <Icon name="fas fa-check" /> {t('topic.jump_best')}
          </Link>
        ) : null}
      </div>
    );
  }
}

/**
 * About this discussion: started, last reply, replies, who is in it, and
 * Follow plus core's discussion controls.
 *
 * The participant stack is drawn from the authors of the posts already on
 * the page; nothing is fetched for it.
 */
class AboutCard {
  view(vnode: any) {
    const { discussion, page, follow } = vnode.attrs;
    const authors: any[] = [];

    for (const post of page.stream?.posts?.() || []) {
      const user = post?.user?.();
      if (user && !authors.includes(user)) authors.push(user);
    }

    const total = discussion.attribute('participantCount') || authors.length;
    const shown = authors.slice(0, 5);

    const row = (label: any, value: any) => (
      <div className="MastheadAbout-row">
        <span>{label}</span>
        <span>{value}</span>
      </div>
    );

    return (
      <section className="MastheadWidget MastheadAbout" aria-labelledby="masthead-about">
        <h2 id="masthead-about" className="MastheadWidget-title">
          {t('topic.about')}
        </h2>
        {row(t('topic.started'), humanTime(discussion.createdAt()))}
        {discussion.lastPostedAt() ? row(t('topic.last_reply'), humanTime(discussion.lastPostedAt())) : null}
        {row(t('topic.replies'), replyCount(discussion))}
        {shown.length
          ? row(
              t('topic.participants_label'),
              <span className="MastheadStack">
                {shown.map((user) => (
                  <Avatar user={user} className="MastheadStack-avatar" title={user.displayName()} />
                ))}
                {total > shown.length ? <span className="MastheadStack-more">+{total - shown.length}</span> : null}
              </span>
            )
          : null}
        <div className="MastheadAbout-actions">
          {follow ? <ul className="MastheadAbout-follow">{listItems([follow])}</ul> : null}
          <Dropdown
            className="MastheadAbout-controls"
            buttonClassName="Button Button--icon"
            icon="fas fa-ellipsis-h"
            menuClassName="Dropdown-menu--right"
            accessibleToggleLabel={app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label')}
          >
            {DiscussionControls.controls(discussion, page).toArray()}
          </Dropdown>
        </div>
      </section>
    );
  }
}
