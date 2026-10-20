import app from 'flarum/forum/app';

/** A forum-side Masthead string. Every word on screen comes through here. */
export const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`ernestdefoe-masthead.forum.${key}`, params as any);

/**
 * `flarum` is a global the bundle never imports; reached through globalThis
 * so its absence is `undefined`, not a ReferenceError thrown out of an
 * initializer.
 */
export function flarumGlobal(): any {
  return typeof globalThis !== 'undefined' ? (globalThis as any).flarum : undefined;
}

/** Whether an extension with forum JS is enabled. Never a build-time import. */
export function has(id: string): boolean {
  const extensions = flarumGlobal()?.extensions;

  return !!extensions && id in extensions;
}

/*
 * One media query for the whole theme, matching Flarum's own phone breakpoint
 * (@screen-phone-max). The header decides what to draw from it, so a window
 * dragged across the line redraws instead of keeping the wrong layout until
 * the next navigation.
 */
const phone = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;

phone?.addEventListener?.('change', () => m.redraw());

export function isPhone(): boolean {
  return !!phone?.matches;
}

/** True on the portal itself, wherever it is mounted (`/` or `/portal`). */
export function onPortal(): boolean {
  return app.current.matches(PortalMarker);
}

/**
 * The portal page class registers itself here, so this module never imports
 * it (and nothing that only needs `onPortal` pulls in the whole page).
 */
export let PortalMarker: any = null;

export function markPortal(component: any) {
  PortalMarker = component;
}
