import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import { t } from '../util';

interface FooterLink {
  label: string;
  url: string;
}

/**
 * A #fragment link: "#" (a placeholder while a site is being built) does
 * nothing, and "#id" scrolls to that element when the page has one. Neither
 * touches the address bar, so Flarum's router never sees a navigation.
 */
function jump(e: MouseEvent, url: string) {
  e.preventDefault();

  const target = url.length > 1 ? document.getElementById(decodeURIComponent(url.slice(1))) : null;
  target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/** The footer: the forum's name, the admin's links and "Powered by Flarum". */
export default class MastheadFooter extends Component {
  view() {
    const links = app.forum.attribute<FooterLink[]>('mastheadFooterLinks') || [];
    const base = app.forum.attribute<string>('baseUrl');

    return (
      <div className="MastheadFooter">
        <div className="container MastheadFooter-inner">
          <span className="MastheadFooter-name">{app.forum.attribute<string>('title')}</span>
          {links.length ? (
            <nav className="MastheadFooter-links" aria-label={t('footer.label')}>
              {links.map((link) => {
                // The server already refused anything but a path, http(s),
                // mailto or a #fragment. Off-site links open in a new tab, and
                // are told not to hand this page to the one they open.
                if (link.url.startsWith('#')) {
                  return (
                    <a href={link.url} onclick={(e: MouseEvent) => jump(e, link.url)}>
                      {link.label}
                    </a>
                  );
                }

                const external = !link.url.startsWith('/') && !link.url.startsWith(base);

                return (
                  <a href={link.url} {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}>
                    {link.label}
                  </a>
                );
              })}
            </nav>
          ) : null}
          {app.forum.attribute('mastheadPoweredBy') ? (
            <a className="MastheadFooter-powered" href="https://flarum.org" target="_blank" rel="noopener noreferrer">
              {t('footer.powered_by')}
            </a>
          ) : null}
        </div>
      </div>
    );
  }
}
