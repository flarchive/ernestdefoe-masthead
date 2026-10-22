import app from 'flarum/forum/app';

export interface Story {
  kind: 'discussion' | 'article';
  id: number;
  slug?: string;
  url?: string;
  title: string;
  excerpt: string;
  image: string | null;
  author: string | null;
  replies: number | null;
  createdAt: string | null;
  tag: { name: string; color: string | null } | null;
}

export interface Poster {
  id: number;
  slug: string;
  displayName: string;
  avatarUrl: string | null;
  posts: number;
}

export interface Game {
  id: number;
  home: string;
  away: string;
  homeLogo: string | null;
  awayLogo: string | null;
  at: string | null;
  status: string;
}

interface PortalData {
  hero: Story[];
  /** Admins only, when the chosen source has nothing to feature. */
  heroEmpty: { source: string; name: string | null } | null;
  topPosters: Poster[];
  /** null when ernestdefoe/picks is not enabled (or the actor may not see it). */
  games: Game[] | null;
}

/*
 * 🚨 Module-level, not component state.
 *
 * Widget hosts (Bespoke, fof/forum-widgets-core) remount what they place on
 * every redraw of their own. Data kept on a component instance would be
 * fetched again on each of those, so the portal and every placed widget share
 * this one copy, fetched at most once per page view.
 */
let portal: PortalData | null = null;
let loadedAt = 0;
let pending: Promise<PortalData> | null = null;
let posters: Poster[] | null = null;
let pendingPosters: Promise<Poster[]> | null = null;

/** The preloaded copy, taken once: a full page load of the portal ships it. */
function preloaded(): PortalData | null {
  const data = (app.data as any).mastheadPortal;

  if (data) delete (app.data as any).mastheadPortal;

  return data || null;
}

export function getPortal(): PortalData | null {
  if (!portal) {
    portal = preloaded();
    if (portal) {
      posters = portal.topPosters;
      loadedAt = Date.now();
    }
  }

  return portal;
}

/**
 * The portal's data, fetched when there is none or what there is has gone
 * a minute stale (a reader coming back to the front page after a while sees
 * today's stories, not the ones from when they arrived).
 */
export function loadPortal(): Promise<PortalData> {
  if (getPortal() && Date.now() - loadedAt < 60_000) return Promise.resolve(portal!);

  pending ||= app
    .request<PortalData>({ method: 'GET', url: app.forum.attribute('apiUrl') + '/masthead/portal' })
    .then((data) => {
      portal = data;
      posters = data.topPosters;
      loadedAt = Date.now();
      m.redraw();

      return data;
    })
    .finally(() => (pending = null));

  return pending;
}

export function getPosters(): Poster[] | null {
  getPortal();

  return posters;
}

export function loadPosters(): Promise<Poster[]> {
  if (getPosters()) return Promise.resolve(posters!);

  pendingPosters ||= app
    .request<{ topPosters: Poster[] }>({ method: 'GET', url: app.forum.attribute('apiUrl') + '/masthead/top-posters' })
    .then((data) => {
      posters = data.topPosters;
      m.redraw();

      return posters;
    })
    .finally(() => (pendingPosters = null));

  return pendingPosters;
}
