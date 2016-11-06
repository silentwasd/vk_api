<?php

class VK_Wall {
	static function get($ownerID = null, $domain = null, $count = 10, $offset = null, $filter = "all", $extended = 0, $fields = array()) {
		$_fields = implode(",", $fields);
		$query = VK::execQuery("wall.get", "owner_id=$ownerID&domain=$domain&count=$count&offset=$offset&filter=$filter&extended=$extended&fields=$_fields", true);
		if (VK::checkQuery($query)) {
			$posts = array();
			foreach ($query['response']['items'] as $post) {
				$posts[] = new VK_Post($post);
			}
			if (!$extended) return $posts;
			else {
				$result['posts'] = $posts;
				VK::handleExtended($query['response'], $result);
				return $result;
			}
		}
		else return $query;
	}

	static function getByID($posts, $extended = 0, $fields = array(), $copyHistoryDepth = 2) {
		$_fields = implode(",", $fields);
		$_posts = implode(",", $posts);
		$query = VK::execQuery("wall.getByID", "posts=$_posts&extended=$extended&fields=$_fields&copy_history_depth=$copyHistoryDepth", true);
		if (VK::checkQuery($query)) {
			$posts = array();
			foreach ($query['response']['items'] as $post) {
				$posts[] = new VK_Post($post);
			}
			if (!$extended) return $posts;
			else {
				$result['posts'] = $posts;
				VK::handleExtended($query['response'], $result);
				return $result;
			}
		}
		else return $query;
	}

	static function getComments($ownerID = null, $postID, $needLikes = 0, $count = 10, $startCommentID = null, $offset = null, $sort = "asc", $previewLength = 0, $extended = 0) {
		$query = VK::execQuery("wall.getComments", "owner_id=$ownerID&post_id=$postID&need_likes=$needLikes&count=$count&start_comment_id=$startCommentID&offset=$offset&sort=$sort&previewLength=$previewLength&extended=$extended", true);
		if (VK::checkQuery($query)) {
			$comments = array();
			foreach ($query['response']['items'] as $comment) {
				$comments[] = new VK_Comment($comment);
			}
			if (!$extended) return $comments;
			else {
				$result['comments'] = $comments;
				VK::handleExtended($query['response'], $result);
				return $result;
			}
		}
		else return $query;
	}

	static function getReposts($ownerID = null, $postID, $offset = null, $count = 20) {
		$query = VK::execQuery("wall.getReposts", "owner_id=$ownerID&post_id=$postID&offset=$offset&count=$count", true);
		if (VK::checkQuery($query)) {
			$reposts = array();
			foreach ($query['response']['items'] as $repost) {
				$reposts[] = new VK_Post($repost);
			}
			$result['reposts'] = $reposts;
			VK::handleExtended($query['response'], $result);
			return $result;
		}
		else return $query;
	}

	static function post($ownerID = null, $postID = null, $message, $publishDate = null, $friendsOnly = 0, $fromGroup = 0, $signed = 0, $isAd = 0, $guid = "") {
		$_message = urlencode(iconv('windows-1251', 'utf-8', $message));
		$query = VK::execQuery("wall.post", "owner_id=$ownerID&post_id=$postID&message=$_message&friends_only=$friendsOnly&from_group=$fromGroup&signed=$signed&marked_as_ad=$isAd&guid=$guid&publish_date=$publishDate", true);
		if (VK::checkQuery($query)) {
			return $query['response']['post_id'];
		}
		else return $query;
	}
}