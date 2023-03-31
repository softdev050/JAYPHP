<?php


	function uploadJSONErrorMessage($msg, $isError = true) {
		if ($isError) {
			jsonError( $msg );
			return null;
		}

		jsonHeaders( $msg );
	}

	define( 'SCRIPTNAME', 'upload_file.php' );
	define( 'IS_AJAX', 1 );
	define( 'NO_SESSION_UPDATE', 1 );
	define( 'NO_PLUGIN', 1 );
	require( './../library/init/init.php' );

	if ($TSUE['action'] == 'upload_file') {
		globalize( 'get', array( 'securitytoken' => 'TRIM', 'content_type' => 'TRIM', 'forumid' => 'INT', 'postid' => 'INT' ) );

		if (!isValidToken( $securitytoken )) {
			uploadJSONErrorMessage( get_phrase( 'invalid_security_token' ) );
		}

		require( REALPATH . '/library/classes/class_ajax_upload.php' );

		if (( $content_type == 'torrent_files' || $content_type == 'torrent_screenshots' )) {
			if (( !has_permission( 'canupload_torrents' ) || !has_permission( 'canview_torrents' ) )) {
				uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
			}
		} 
else {
			if (in_array( $content_type, array( 'threads', 'posts' ) )) {
				require( REALPATH . '/library/classes/class_forums.php' );
				$TSUE_Forums = new forums(  );

				if (( !$TSUE_Forums->forumCategories || !isset( $TSUE_Forums->availableForums[$forumid] ) )) {
					uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
				}

				$forum = $TSUE_Forums->availableForums[$forumid];

				if (( !$TSUE_Forums->checkForumPassword( $forum['forumid'], $forum['password'] ) || !has_forum_permission( 'canupload', $TSUE_Forums->forumPermissions[$forum['forumid']][$TSUE['TSUE_Member']->info['membergroupid']] ) )) {
					uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
				}

				switch ($content_type) {
					case 'threads': {
						if (!has_forum_permission( 'canpost_new_thread', $TSUE_Forums->forumPermissions[$forum['forumid']][$TSUE['TSUE_Member']->info['membergroupid']] )) {
							uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
						}

						$content_type = 'posts';
						break;
					}

					case 'posts': {
						if (!has_forum_permission( array( 'canview_thread_list', 'canview_thread_posts', 'canreply_threads' ), $TSUE_Forums->forumPermissions[$forum['forumid']][$TSUE['TSUE_Member']->info['membergroupid']] )) {
							uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
						}

						break;
					}

					default: {
						uploadJSONErrorMessage( get_phrase( 'permission_denied' ) );
						break;
					}
				}
			}
		}


		if ($content_type == 'torrent_screenshots') {
			$allowedFileTypes = array( 'jpg', 'jpeg', 'gif', 'png' );
		} 
else {
			$allowedFileTypes = tsue_explode( ',', $TSUE['TSUE_Settings']->settings['global_settings']['allowed_file_types'] );
		}

		$uploader = new qqFileUploader( $allowedFileTypes, $TSUE['TSUE_Settings']->settings['global_settings']['max_file_size'] );
		$result = $uploader->handleUpload( $content_type, $postid );
		uploadJSONErrorMessage( $result, false );
	}

?>