/**
 * The author's first displayed group as a text badge ("Moderator"), from the
 * groups the post payload already includes. Members-only users get none.
 */
export default function groupBadge(user: any) {
  const groups = (user?.groups?.() || []).filter((g: any) => g && !g.isHidden?.() && g.nameSingular?.());
  const group = groups[0];

  if (!group) return null;

  return (
    <span className="MastheadGroupBadge" style={group.color() ? { '--mh-tag': group.color() } : undefined}>
      {group.nameSingular()}
    </span>
  );
}
