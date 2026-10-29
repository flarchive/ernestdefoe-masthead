import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';

/**
 * The masthead's title block: the logo (or a monogram tile), the forum's
 * name and the tagline.
 *
 * Drawn into core's `.Header-title`, which the server renders as a plain link
 * to the home page; mounting over it keeps the same element, the same link
 * and the same logo, and only adds what core has no slot for.
 */
export default class Brand extends Component {
  view() {
    const title = app.forum.attribute<string>('title') || '';
    /*
     * 🚨 The masthead is dark in light mode and in dark mode alike, so the
     * logo on it is the one made for dark backgrounds: Flarum's dark-mode
     * logo (Appearance → Dark mode logo) whenever one is uploaded, in BOTH
     * modes. The normal logo is the fallback. A forum whose logo has dark
     * text otherwise shows it unreadable on navy every time the reader is in
     * light mode.
     */
    const logo = app.forum.attribute<string | null>('logoDarkModeUrl') || app.forum.attribute<string | null>('logoUrl');
    const tagline = app.forum.attribute<string>('mastheadTagline');
    /*
     * The forum's name beside the brand: always without a logo (the tile
     * alone says nothing), and with one only when the admin asked for it —
     * most logos already contain the name. null means "never set".
     */
    const showName = !logo || app.forum.attribute<boolean | null>('mastheadShowName') === true;

    return (
      <Link href={app.forum.attribute<string>('basePath') + '/'} id="home-link" className="MastheadBrand">
        {logo ? (
          <span className="MastheadBrand-logo">
            <img src={logo} alt={showName ? '' : title} className="Header-logo MastheadBrand-img" />
          </span>
        ) : (
          <span className="MastheadBrand-tile" aria-hidden="true">
            {monogram(title)}
          </span>
        )}
        {/* The tagline stands on its own: beside a logo whose name is hidden it still shows. */}
        {showName || tagline ? (
          <span className="MastheadBrand-text">
            {showName ? <span className="MastheadBrand-name">{title}</span> : null}
            {tagline ? <span className="MastheadBrand-tagline">{tagline}</span> : null}
          </span>
        ) : null}
      </Link>
    );
  }
}

/**
 * The admin's monogram, or the forum name's initials: "Fourth Down" → "FD".
 * Two letters at most, so the tile never overflows.
 */
function monogram(title: string): string {
  const set = (app.forum.attribute<string>('mastheadMonogram') || '').trim();
  if (set) return Array.from(set).slice(0, 3).join('');

  const words = title.split(/\s+/).filter(Boolean);
  const letters = words.length > 1 ? words[0][0] + words[1][0] : Array.from(title).slice(0, 2).join('');

  return letters.toUpperCase();
}
