import app from 'flarum/forum/app';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';

/**
 * Core's own "Start a discussion" action: the composer for a member, the
 * log-in modal for a guest. Borrowed from the sidebar rather than rewritten,
 * so whatever an extension changes about it (a tag picker, a draft prompt)
 * applies to Masthead's buttons too.
 */
export function startDiscussion() {
  return IndexSidebar.prototype.newDiscussionAction.call(Object.create(IndexSidebar.prototype)).catch(() => {});
}

/** Core's rule: a guest is offered the button (and asked to log in). */
export function canStart(): boolean {
  return !app.session.user || !!app.forum.attribute('canStartDiscussion');
}
