import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import listItems from 'flarum/common/helpers/listItems';

/**
 * Everything that says who the author is, as one row beside their name: the
 * first displayed group as a text chip ("Moderator"), core's own user badges
 * (suspended, other groups, anything an extension adds to User.badges()) and
 * fof/badges' earned badges as labelled chips.
 *
 * Core floats its badges into the avatar column and fof/badges draws its
 * earned ones as bare icons in the post's footer; on Masthead's cards both
 * came out as unlabelled dots in a corner (stream.less hides those copies).
 * Everything here is read from the payload the post already carries.
 */
function firstGroup(user: any): any {
  return (user?.groups?.() || []).find((g: any) => g && !g.isHidden?.() && g.nameSingular?.()) || null;
}

/**
 * fof/badges' earned badges: the ones it shows on cards (primary first), plus
 * the primary badge it would have drawn after the name when that one is not
 * among them (a display limit of 0, or hidden from cards).
 */
function earned(user: any): { name: string; icon: string }[] {
  const visible = user.attribute?.('visibleBadges');
  const list: { name: string; icon: string }[] = Array.isArray(visible) ? visible : [];
  const name = user.attribute?.('primaryBadgeName');
  const icon = user.attribute?.('primaryBadgeIcon');

  if (name && icon && app.forum.attribute('primaryBadgeDisplay') !== 'hidden' && !list.some((b) => b.name === name)) {
    return [{ name, icon }, ...list];
  }

  return list;
}

export default function userBadges(user: any) {
  if (!user) return null;

  const group = firstGroup(user);
  const core = user.badges?.();
  if (group && core?.has?.('group' + group.id())) core.remove('group' + group.id());

  const fof = earned(user);
  const more = Math.max(0, (user.attribute?.('badgeCount') || 0) - fof.length);
  const href = app.routes['user.badges'] ? app.route('user.badges', { username: user.slug?.() || user.username() }) : null;
  const coreItems = core ? core.toArray() : [];

  if (!group && !coreItems.length && !fof.length) return null;

  return (
    <span className="MastheadBadges">
      {group ? (
        <span className="MastheadGroupBadge" style={group.color() ? { '--mh-tag': group.color() } : undefined}>
          {group.nameSingular()}
        </span>
      ) : null}
      {coreItems.length ? <ul className="MastheadBadges-core badges">{listItems(coreItems)}</ul> : null}
      {fof.map((badge) => {
        const chip = [<i className={badge.icon} aria-hidden="true" />, <span className="MastheadBadge-name">{badge.name}</span>];

        return href ? (
          <Link className="MastheadBadge" href={href} title={badge.name}>
            {chip}
          </Link>
        ) : (
          <span className="MastheadBadge" title={badge.name}>
            {chip}
          </span>
        );
      })}
      {more && href ? (
        <Link className="MastheadBadge MastheadBadge--more" href={href}>
          +{more}
        </Link>
      ) : null}
    </span>
  );
}
