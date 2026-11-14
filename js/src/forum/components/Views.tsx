import app from 'flarum/forum/app';
import Icon from 'flarum/common/components/Icon';
import abbreviateNumber from 'flarum/common/utils/abbreviateNumber';
import extractText from 'flarum/common/utils/extractText';
import { t } from '../util';

/**
 * A discussion's view count, or null when there is none to show: the
 * "Show view counts" setting is off, fof/discussion-views is not enabled
 * (mastheadViews is false), or the discussion came without its `views`.
 * The count is fof/discussion-views' own serialised attribute; Masthead
 * fetches and counts nothing.
 */
export function viewCount(discussion: any): number | null {
  if (!app.forum.attribute<boolean>('mastheadViews')) return null;

  const views = discussion?.attribute?.('views');

  return typeof views === 'number' ? views : null;
}

/** An eye and the count, compact (1.2K), with the exact number as its label. */
export default function views(count: number) {
  // Plain text for title and aria-label: trans() returns parts, which an
  // attribute would join with commas ("12,345, views").
  const label = extractText(t('views', { count }));

  return (
    <span className="MastheadViews" title={label} aria-label={label}>
      <Icon name="far fa-eye" />
      <span aria-hidden="true">{abbreviateNumber(count)}</span>
    </span>
  );
}
