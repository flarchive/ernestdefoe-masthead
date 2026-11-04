import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import humanTime from 'flarum/common/helpers/humanTime';
import type { Story } from '../portalData';
import { t } from '../util';

function href(story: Story): string {
  return story.kind === 'article' ? app.forum.attribute<string>('basePath') + story.url : app.route('discussion', { id: story.slug });
}

/**
 * The picture, or the striped placeholder when a story has none.
 *
 * The placeholder is drawn in CSS from the story's tag colour, so a hero
 * without photos still reads as designed rather than as broken images.
 */
function media(story: Story, feature: boolean) {
  return (
    <div className="MastheadHero-media" style={story.tag?.color ? { '--mh-tone': story.tag.color } : undefined}>
      {story.image ? (
        <img src={story.image} alt="" loading={feature ? 'eager' : 'lazy'} fetchpriority={feature ? 'high' : undefined} decoding="async" />
      ) : null}
    </div>
  );
}

function meta(story: Story) {
  const parts: any[] = [];

  if (story.author) parts.push(t('hero.byline', { name: story.author }));
  if (story.replies !== null) parts.push(t('hero.replies', { count: story.replies }));
  else if (story.createdAt) parts.push(humanTime(new Date(story.createdAt)));

  return parts.flatMap((part, i) => (i ? [' · ', part] : [part]));
}

/**
 * Wraps a story in the right kind of link: an in-app route for a discussion,
 * a plain href for a Page Builder article (its page is Page Builder's to
 * route, and Masthead does not assume its routes are registered).
 */
function StoryLink(story: Story, className: string, children: any) {
  return story.kind === 'article' ? (
    <a className={className} href={href(story)}>
      {children}
    </a>
  ) : (
    <Link className={className} href={href(story)}>
      {children}
    </Link>
  );
}

export interface HeroAttrs {
  stories: Story[];
  style: string;
  empty?: { source: string; name: string | null } | null;
}

/**
 * Where the hero would be, for an admin, when the chosen source has nothing
 * to feature. Visitors see no block at all.
 */
function EmptyHint(empty: { source: string; name: string | null }) {
  const name = empty.name || '';
  const text =
    empty.source === 'tag'
      ? name
        ? t('hero.empty_tag', { tag: name })
        : t('hero.empty_tag_gone')
      : empty.source === 'sticky'
        ? t('hero.empty_sticky')
        : t('hero.empty_pagebuilder', { type: name });

  return (
    <p className="MastheadHero MastheadHero--empty">
      {text} <a href={app.forum.attribute<string>('adminUrl') + '#/extension/ernestdefoe-masthead'}>{t('hero.empty_settings')}</a>
    </p>
  );
}

/** The featured story and the three beside it. */
export default class Hero extends Component<HeroAttrs> {
  view() {
    const { stories, style, empty } = this.attrs;

    if (style === 'off') return null;
    if (!stories.length) return empty ? EmptyHint(empty) : null;

    const [feature, ...side] = stories;
    // The tag on each story, unless the admin turned it off (the stories'
    // colours still come from their tags).
    const tags = app.forum.attribute('mastheadHeroTags') !== false;

    return (
      <section className={`MastheadHero MastheadHero--${style}`} aria-label={t('hero.label')}>
        {StoryLink(
          feature,
          'MastheadHero-feature',
          <>
            {media(feature, true)}
            <div className="MastheadHero-body">
              <span className="MastheadKicker MastheadKicker--solid">
                {tags && feature.tag ? t('hero.kicker_tag', { tag: feature.tag.name }) : t('hero.kicker')}
              </span>
              <h2 className="MastheadHero-title">{feature.title}</h2>
              {feature.excerpt ? <p className="MastheadHero-excerpt">{feature.excerpt}</p> : null}
              <span className="MastheadHero-meta">{meta(feature)}</span>
            </div>
          </>
        )}

        {side.length ? (
          <div className={`MastheadHero-side MastheadHero-side--${side.length}`}>
            {side.map((story) =>
              StoryLink(
                story,
                'MastheadStory',
                <>
                  {media(story, false)}
                  <div className="MastheadStory-body">
                    {tags && story.tag ? <span className="MastheadKicker">{story.tag.name}</span> : null}
                    <span className="MastheadStory-title">{story.title}</span>
                    <span className="MastheadStory-meta">{meta(story)}</span>
                  </div>
                </>
              )
            )}
          </div>
        ) : null}
      </section>
    );
  }
}
