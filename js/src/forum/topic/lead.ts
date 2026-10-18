/**
 * Facts about the opening post read from what is already on the page: its
 * rendered HTML, which the discussion payload carries anyway. No request.
 *
 * Memoised on the HTML string itself, so a redraw costs a string compare and
 * an edited post is read again.
 */
const cache = new Map<any, { html: string; image: string | null; minutes: number }>();

function read(post: any) {
  const html: string = post?.contentHtml?.() || '';
  const hit = cache.get(post);
  if (hit && hit.html === html) return hit;

  // DOMParser, never a detached <div>: a parsed document runs no scripts and
  // fetches no images while being read.
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const img = doc.querySelector('img[src]') as HTMLImageElement | null;
  const src = img?.getAttribute('src') || '';
  const words = (doc.body.textContent || '').trim().split(/\s+/).filter(Boolean).length;

  const value = {
    html,
    // Only an http(s) or same-site address becomes a banner; a path whose
    // second character is a slash or a backslash is another site to a
    // browser, so it is refused like the server's Images::safe refuses it.
    image: /^(https?:\/\/|\/(?![\/\\]))/i.test(src) && !/[\s"'<>\\]/.test(src) ? src : null,
    // 230 words a minute, never "0 min read".
    minutes: Math.max(1, Math.round(words / 230)),
  };

  cache.set(post, value);
  if (cache.size > 50) cache.delete(cache.keys().next().value);

  return value;
}

export function leadImage(post: any): string | null {
  return post ? read(post).image : null;
}

export function readMinutes(post: any): number | null {
  return post ? read(post).minutes : null;
}
