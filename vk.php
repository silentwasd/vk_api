<?php

class VK {
	static public $appID; // Идентификатор приложения
	static public $redirectURI = "https://oauth.vk.com/blank.html"; // Адрес переадресации
	static public $scope; // Права доступа
	static public $accessToken; // Ключ доступа
	static public $userID; // Идентификатор пользователя
	static private $apiURL = "https://api.vk.com/method/"; // URL запросов к API

	static function getURL() {
		$appID = self::$appID;
		$redirectURI = self::$redirectURI;
		$scope = self::$scope;
		return "https://oauth.vk.com/authorize?client_id=$appID&redirect_uri=$redirectURI&display=popup&scope=$scope&response_type=token&v=5.60&state=&revoke=1";
	}

	static function parseBlank($url) {
		$check = preg_match('/^' . str_replace("/", "\/", self::$redirectURI) . '#access_token=([^&]+)&expires_in=([^&]+)&user_id=([^&]+)&/', $url, $result);
		if ($check) return array($result[1], $result[3]);
		else return false;
	}

	static function execQuery($method, $params, $toArray=false) {
		$apiURL = self::$apiURL;
		$accessToken = self::$accessToken;

		// Убираем из $params пустые параметры
		$paramsArr = explode("&", $params);
		$newParams = array();
		foreach ($paramsArr as $param) {
			if (preg_match('/^[a-z_]+=.+$/', $param)) $newParams[] = $param;
		}
		$_params = implode("&", $newParams);

		$url = "{$apiURL}$method?$_params&access_token=$accessToken&v=5.60";

		$result = file_get_contents($url);
		$win1251 = iconv("utf-8", "windows-1251", $result);

		if ($toArray) {
			$arr = self::jsonDecode($result);
			if (!$arr['error']) return $arr;
			else return new VK_Error($arr['error']);
		}
		else return $win1251;
	}

	static function checkQuery($query) {
		if (is_object($query)) {
			if (get_class($query) == "VK_Error") return false;
		}
		else return true;
	}

	static private function jsonDecode($json) {
	    if (!$json) return false;
	    $decoded = json_decode($json, true);
	    return self::iconvArray($decoded);
	}

	static private function iconvArray($jsonArray) {
		$myArray = $jsonArray;
	    foreach ($jsonArray as $key => $value) {
	        if (is_array($value)) {
	            $myArray[$key] = self::iconvArray($value);
	        } else {
	            $myArray[$key] = iconv('utf-8', 'windows-1251//IGNORE', $value);
	        }
	    }
	    return $myArray;
	}

	static function handleExtended($js_array, &$result) {
		$groups = array();
		$profiles = array();

		if ($js_array['groups']) {
			foreach ($js_array['groups'] as $group) {
				$groups[] = new VK_Group($group);
			}
		}
		if ($js_array['profiles']) {
			foreach ($js_array['profiles'] as $profile) {
				$profiles[] = new VK_User($profile);
			}
		}
		
		$result['groups'] = $groups;
		$result['profiles'] = $profiles;
	}
}

class VK_User {
	public $id;
	public $name;
	public $lastName;
	public $deactivated;
	public $hidden;
	public $online;
	public $sex;
	public $photo50;
	public $photo100;
	public $photo200;
	public $photoMax;
	public $status;
	public $counters;
	public $screenName;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		if (is_array($js_array)) {
			$this->id = $js_array['id'];
			$this->name = $js_array['first_name'];
			$this->lastName = $js_array['last_name'];
			$this->deactivated = $js_array['deactivated'];
			$this->hidden = $js_array['hidden'];
			$this->online = $js_array['online'];
			$this->sex = $js_array['sex'];
			$this->photo50 = $js_array['photo_50'];
			$this->photo100 = $js_array['photo_100'];
			$this->photo200 = $js_array['photo_200'];
			$this->photoMax = $js_array['photo_max'];
			$this->status = $js_array['status'];
			$this->counters = new VK_Counters($js_array['counters']);
			$this->screenName = $js_array['screen_name'];
		}
		else $this->id = $js_array;
	}

	function update() {
		$this->syncWithJS(VK_Users::getUser($this->id, array("online", "sex", "photo_50", "photo_100", "photo_200", "photo_max", "status", "counters", "screen_name"), "nom", true));
	}
}
class VK_Message {
	public $id;
	public $date;
	public $out;
	public $userID;
	public $fromID;
	public $readState;
	public $title;
	public $text;
	public $chatID;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->id = $js_array['id'];
		$this->date = $js_array['date'];
		$this->out = $js_array['out'];
		$this->userID = $js_array['user_id'];
		$this->fromID = $js_array['from_id'];
		$this->readState = $js_array['read_state'];
		$this->title = $js_array['title'];
		$this->text = $js_array['body'];
		$this->chatID = $js_array['chat_id'];
	}

	function getPeerID() {
		if ($this->chatID) $peerID = 2000000000 + $this->chatID;
		else $peerID = $this->userID;
		return $peerID;
	}
}
class VK_Dialog {
	public $lastMessage;
	public $inRead;
	public $outRead;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function getMessages($count=20, $rev=0) {
		$message = $this->lastMessage;
		return VK_Messages::getHistory($message->getPeerID(), $count, $rev);
	}

	function syncWithJS($js_array) {
		$message = new VK_Message();
		$message->syncWithJS($js_array['message']);
		$this->lastMessage = $message;
		$this->inRead = $js_array['in_read'];
		$this->outRead = $js_array['out_read'];
	}
}
class VK_Error {
	public $code;
	public $message;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->code = $js_array['error_code'];
		$this->message = $js_array['error_msg'];
	}
}
class VK_Group {
	public $id;
	public $name;
	public $type;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		if (is_array($js_array)) {
			$this->id = $js_array['id'];
			$this->name = $js_array['name'];
			$this->type = $js_array['type'];
		}
		else $this->id = $js_array;
	}
}

class VK_Counters {
	public $albums;
	public $videos;
	public $audios;
	public $photos;
	public $notes;
	public $friends;
	public $groups;
	public $onlineFriends;
	public $mutualFriends;
	public $userVideos;
	public $followers;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->albums = $js_array['albums'];
		$this->videos = $js_array['videos'];
		$this->audios = $js_array['audios'];
		$this->photos = $js_array['photos'];
		$this->notes = $js_array['notes'];
		$this->friends = $js_array['friends'];
		$this->groups = $js_array['groups'];
		$this->onlineFriends = $js_array['online_friends'];
		$this->mutualFriends = $js_array['mutual_friends'];
		$this->userVideos = $js_array['user_videos'];
		$this->followers = $js_array['followers'];
	}
}

class VK_FriendStatus {
	public $userID;
	public $friendStatus;
	public $requestMessage;
	public $readState;
	public $sign;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->userID = $js_array['user_id'];
		$this->friendStatus = $js_array['friend_status'];
		$this->requestMessage = $js_array['request_message'];
		$this->readState = $js_array['read_state'];
		$this->sign = $js_array['sign'];
	}
}

class VK_FriendDelete {
	public $success;
	public $friendDeleted;
	public $outRequestDeleted;
	public $inRequestDeleted;
	public $suggestionDeleted;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->success = $js_array['success'];
		$this->friendDeleted = $js_array['friend_deleted'];
		$this->outRequestDeleted = $js_array['out_request_deleted'];
		$this->inRequestDeleted = $js_array['in_request_deleted'];
		$this->suggestionDeleted = $js_array['suggestion_deleted'];
	}
}

class VK_Post {
	public $id;
	public $ownerID;
	public $fromID;
	public $date;
	public $text;
	public $replyOwnerID;
	public $replyPostID;
	public $friendsOnly;
	public $comments;
	public $likes;
	public $reposts;
	public $type;
	public $source;
	public $signerID;
	public $copyHistory;
	public $canPin;
	public $canDelete;
	public $canEdit;
	public $isPinned;
	public $isAd;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->id = $js_array['id'];
		$this->ownerID = $js_array['owner_id'];
		$this->fromID = $js_array['from_id'];
		$this->date = $js_array['date'];
		$this->text = $js_array['text'];
		$this->replyOwnerID = $js_array['reply_owner_id'];
		$this->replyPostID = $js_array['reply_post_id'];
		$this->friendsOnly = $js_array['friends_only'];
		$this->comments = new VK_PostComments($js_array['comments']);
		$this->likes = new VK_PostLikes($js_array['likes']);
		$this->reposts = new VK_PostReposts($js_array['reposts']);
		$this->type = $js_array['post_type'];
		$this->source = new VK_PostSource($js_array['post_source']);
		$this->signerID = $js_array['signerID'];
		$this->canPin = $js_array['can_pin'];
		$this->canDelete = $js_array['can_delete'];
		$this->canEdit = $js_array['can_edit'];
		$this->isPinned = $js_array['is_pinned'];
		$this->isAd = $js_array['marked_as_ads'];

		if (is_array($hst = $js_array['copy_history'])) {
			$history = array();
			foreach ($hst as $post) {
				$history[] = new VK_Post($post);
			}
			$this->copyHistory = $history;
		}
	}
}

class VK_PostComments {
	public $count;
	public $canPost;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->count = $js_array['count'];
		$this->canPost = $js_array['can_post'];
	}
}

class VK_PostLikes {
	public $count;
	public $userLikes;
	public $canLike;
	public $canPublish;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->count = $js_array['count'];
		$this->userLikes = $js_array['user_likes'];
		$this->canLike = $js_array['can_like'];
		$this->canPublish = $js_array['can_publish'];
	}
}

class VK_PostReposts {
	public $count;
	public $userReposted;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->count = $js_array['count'];
		$this->userReposted = $js_array['user_reposted'];
	}
}

class VK_PostSource {
	public $type;
	public $platform;
	public $data;
	public $url;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->type = $js_array['type'];
		$this->platform = $js_array['platform'];
		$this->data = $js_array['data'];
		$this->url = $js_array['url'];
	}
}

class VK_Comment {
	public $id;
	public $fromID;
	public $date;
	public $text;
	public $replyToUser;
	public $replyToComment;

	function __construct($js_array = false) {
		if ($js_array) $this->syncWithJS($js_array);
	}

	function syncWithJS($js_array) {
		$this->id = $js_array['id'];
		$this->fromID = $js_array['from_id'];
		$this->date = $js_array['date'];
		$this->text = $js_array['text'];
		$this->replyToUser = $js_array['reply_to_user'];
		$this->replyToComment = $js_array['reply_to_comment'];
	}
}