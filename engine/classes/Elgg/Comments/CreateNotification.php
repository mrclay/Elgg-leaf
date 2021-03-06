<?php

namespace Elgg\Comments;

/**
 * Comment create notifications related functions
 *
 * @since 4.0
 */
class CreateNotification {
	
	/**
	 * Set the notification message for the owner of the content being commented on
	 *
	 * @param \Elgg\Hook $hook 'prepare', 'notification:create:object:comment'
	 *
	 * @return void|\Elgg\Notifications\Notification
	 */
	public static function prepareContentOwnerNotification(\Elgg\Hook $hook) {
		
		$comment = $hook->getParam('object');
		if (!$comment instanceof \ElggComment) {
			return;
		}
		
		/* @var $content \ElggEntity */
		$content = $comment->getContainerEntity();
		$recipient = $hook->getParam('recipient');
		if ($content->owner_guid !== $recipient->guid) {
			// not the content owner
			return;
		}
		
		$language = $hook->getParam('language');
		/* @var $commenter \ElggUser */
		$commenter = $comment->getOwnerEntity();
		
		$returnvalue = $hook->getValue();
		
		$returnvalue->subject = elgg_echo('generic_comment:notification:owner:subject', [], $language);
		$returnvalue->summary = elgg_echo('generic_comment:notification:owner:summary', [], $language);
		$returnvalue->body = elgg_echo('generic_comment:notification:owner:body', [
			$content->getDisplayName(),
			$commenter->getDisplayName(),
			$comment->description,
			$comment->getURL(),
		], $language);
		
		return $returnvalue;
	}
	
	/**
	 * Set the notification message for interested users
	 *
	 * @param \Elgg\Hook $hook 'prepare', 'notification:create:object:comment'
	 *
	 * @return void|\Elgg\Notifications\Notification
	 */
	public static function prepareNotification(\Elgg\Hook $hook) {
		
		$comment = $hook->getParam('object');
		if (!$comment instanceof \ElggComment) {
			return;
		}
		
		/* @var $content \ElggEntity */
		$content = $comment->getContainerEntity();
		$recipient = $hook->getParam('recipient');
		if ($content->getOwnerGUID() === $recipient->guid) {
			// the content owner, this is handled in other hook
			return;
		}
		
		$language = $hook->getParam('language');
		/* @var $commenter \ElggUser */
		$commenter = $comment->getOwnerEntity();
		
		$returnvalue = $hook->getValue();
		
		$returnvalue->subject = elgg_echo('generic_comment:notification:user:subject', [$content->getDisplayName()], $language);
		$returnvalue->summary = elgg_echo('generic_comment:notification:user:summary', [$content->getDisplayName()], $language);
		$returnvalue->body = elgg_echo('generic_comment:notification:user:body', [
			$content->getDisplayName(),
			$commenter->getDisplayName(),
			$comment->description,
			$comment->getURL(),
		], $language);
		
		$returnvalue->url = $comment->getURL();
		
		return $returnvalue;
	}
}
