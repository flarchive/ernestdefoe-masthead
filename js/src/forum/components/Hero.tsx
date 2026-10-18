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
}

/** The featured story and the three beside it. */
export default class Hero extends Component<HeroAttrs> {
  view() {
    const { stories, style } = this.attrs;

    if (style === 'off' || !stories.length) return null;

    const [feature, ...side] = stories;

    return (
      <section className={`MastheadHero MastheadHero--${style}`} aria-label={t('hero.label')}>
        {StoryLink(
          feature,
          'MastheadHero-feature',
          <>
            {media(feature, true)}
            <div className="MastheadHero-body">
              <span className="MastheadKicker MastheadKicker--solid">
                {feature.tag ? t('hero.kicker_tag', { tag: feature.tag.name }) : t('hero.kicker')}
              </span>
              <h2 className="MastheadHero-title">{feature.title}</h2>
              {feature.excerpt ? <p className="MastheadHero-excerpt">{feature.excerpt}</p> : null}
              <span className="MastheadHero-meta">{meta(feature)}</span>
            </div>
          </>
        )}

        {side.length ? (
          <div className="MastheadHero-side">
            {side.map((story) =>
              StoryLink(
                story,
                'MastheadStory',
                <>
                  {media(story, false)}
                  <div className="MastheadStory-body">
                    {story.tag ? <span className="MastheadKicker">{story.tag.name}</span> : null}
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
