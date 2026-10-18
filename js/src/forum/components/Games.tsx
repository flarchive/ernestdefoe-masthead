import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import type { Game } from '../portalData';
import { t } from '../util';

function kickoff(game: Game) {
  if (game.status === 'in_progress') return <span className="MastheadGame-live">{t('games.live')}</span>;
  if (!game.at) return null;

  // dayjs carries the forum's locale: "Sat 3:30 PM", "sam. 15:30".
  return dayjs(game.at).format('ddd LT');
}

/**
 * The next pick'em games, from ernestdefoe/picks. Drawn only when Picks is
 * enabled; the data comes with the portal payload, so it costs no request.
 */
export default class Games extends Component<{ games: Game[] }> {
  view() {
    const { games } = this.attrs;

    return (
      <section className="MastheadWidget MastheadWidget--banded MastheadGames" aria-labelledby="masthead-games">
        <h2 id="masthead-games" className="MastheadWidget-title">
          {t('games.title')}
        </h2>
        <ul className="MastheadGames-list">
          {games.map((game) => (
            <li className="MastheadGame" key={game.id}>
              <span className="MastheadGame-team">
                {game.awayLogo ? <img src={game.awayLogo} alt="" loading="lazy" /> : null}
                {game.away}
              </span>
              <span className="MastheadGame-when">{kickoff(game)}</span>
              <span className="MastheadGame-team">
                {game.homeLogo ? <img src={game.homeLogo} alt="" loading="lazy" /> : null}
                {t('games.at', { team: game.home })}
              </span>
            </li>
          ))}
        </ul>
        {app.routes.picks ? (
          <Link className="MastheadWidget-link" href={app.route('picks')}>
            {t('games.cta')}
          </Link>
        ) : null}
      </section>
    );
  }
}
