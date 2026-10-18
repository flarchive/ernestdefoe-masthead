import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import { t } from '../util';

interface FooterLink {
  label: string;
  url: string;
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
                // The server already refused anything but a path, http(s) or
                // mailto. Off-site links open in a new tab, and are told not
                // to hand this page to the one they open.
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
