<?php
define( 'SCRIPTNAME', 'get_imdb.php' );
define( 'IS_AJAX', 1 );
define( 'NO_SESSION_UPDATE', 1 );
define( 'NO_PLUGIN', 1 );
require( './../library/init/init.php' );
@ini_set('display_errors', 'on');
if (( !$TSUE['action'] || strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' )) {
	ajax_message( get_phrase( 'permission_denied' ), '-ERROR-' );
}

globalize( 'post', array( 'securitytoken' => 'TRIM' ) );

if (!isValidToken( $securitytoken )) {
	ajax_message( get_phrase( 'invalid_security_token' ), '-ERROR-' );
}

switch ($TSUE['action']) {
	case 'refresh_imdb': {

			globalize( 'post', array( 'tid' => 'INT' ) );

			if (( ( !has_permission( 'canrefresh_imdb' ) || !has_permission( 'canview_torrents' ) ) || !$tid )) {
				ajax_message( get_phrase( 'permission_denied' ), '-ERROR-' );
			}

			check_flood( 'refresh-imdb-' . $tid );
			$Torrent = $TSUE['TSUE_Database']->query_result( 'SELECT t.tid, t.options, c.cviewpermissions FROM tsue_torrents t LEFT JOIN tsue_torrents_categories c USING(cid) WHERE t.tid = ' . $TSUE['TSUE_Database']->escape( $tid ) );

			if (!$Torrent) {
				ajax_message( get_phrase( 'torrents_not_found' ), '-ERROR-' );
			}

			$torrentOptions = unserialize( $Torrent['options'] );

			if (( !hasViewPermission( $Torrent['cviewpermissions'] ) || !$torrentOptions['imdb'] )) {
				ajax_message( get_phrase( 'message_content_error' ), '-ERROR-' );
			}

			require_once( REALPATH . '/library/classes/imdb.class.php' );
			$IMDB = new IMDB($torrentOptions['imdb']);
			$posterPath = REALPATH . '/data/torrents/imdb/';
			if ($IMDB->isReady) {
				
				$movieInfo['imdb_url'] = $IMDB->getUrl();
				preg_match_all("/tt\\d{7}/", $torrentOptions['imdb'], $ids);				
				$movieInfo['title_id'] = $ids[0][0];
				$movieInfo['title'] = $IMDB->getTitle();
				$movieInfo['poster'] = $IMDB->getPoster('small', false);
				$movieInfo['directors'] = explode("/",$IMDB->getDirector());
				$movieInfo['writers'] = explode("/",$IMDB->getWriter());
				$movieInfo['genres'] = explode("/",$IMDB->getGenre());
				$movieInfo['cast'] = explode("/",$IMDB->getCast(5,false));
				$movieInfo['rating'] = $IMDB->getRating();
				$movieInfo['year'] = $IMDB->getYear();
				$movieInfo['plot'] = $IMDB->getPlot();
				$movieInfo['runtime'] = $IMDB->getRuntime();

				if (is_file( $posterPath . $torrentOptions['imdb'] . '.jpg' )) {
					@unlink( $posterPath . $torrentOptions['imdb'] . '.jpg' );
				}
				
				savePoster( $movieInfo['poster'],$posterPath,$movieInfo['title_id'] );
				require_once( REALPATH . '/library/functions/functions_getTorrents.php' );
				$BuildQuery = array( 'tid' => $tid, 'content' => serialize( $movieInfo ) );
				$TSUE['TSUE_Database']->replace( 'tsue_imdb', $BuildQuery );				
				ajax_message( IMDBContent( $movieInfo, $tid ) );
			}else{
			ajax_message( get_phrase( 'message_content_error' ), '-ERROR-' );
			}	
			break;
		}
	
	
	}


function savePoster($url,$path,$id)
    {
		
        $image_data = @file_get_contents($url);
        $image = $path.$id.".jpg";
        return file_put_contents($image, $image_data);
    }	

?>
