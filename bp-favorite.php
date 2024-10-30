<?php 
/*
Plugin Name: Bp Favorite Notifications
Plugin URI: http://webcaffe.ir
Description: Notification when user favorite activity buddypress . .
Version: 1.0
Author: asghar hatampoor
Author URI: http://webcaffe.ir

*/
if ( !defined( 'ABSPATH' ) ) exit;

define('BP_NOT_FAV_URL', plugin_dir_url(__FILE__));

function bp_favorite_load_textdomain() {
    load_plugin_textdomain('bp-fav', false, dirname(plugin_basename(__FILE__)) . "/languages/");
}
add_action('init', 'bp_favorite_load_textdomain');

function load_styles_favorite() {
    if(!is_user_logged_in())
        return;          
            wp_register_style( 'bp-fav',BP_NOT_FAV_URL.'css/bp-fav.css', array(),'20141113','all' );
            wp_enqueue_style( 'bp-fav' );
        }
add_action( 'wp_print_styles', 'load_styles_favorite' );	
add_action( 'admin_print_styles', 'load_styles_favorite' );	
function load_js_favorite() {
    if(!is_user_logged_in())
        return;           
           wp_enqueue_script("bp-fav-js",BP_NOT_FAV_URL."js/bp-fav.js",array("jquery"));            
        }
add_action( 'wp_print_scripts', 'load_js_favorite' );
add_action('admin_enqueue_scripts', 'load_js_favorite');
define("BP_FAVORITE_NOTIFIER_SLUG","fa_notification");

    function bp_favorite_setup_globals() {	
	global $bp, $current_blog;
    $bp->bp_favorite=new stdClass();
    $bp->bp_favorite->id = 'bp_favorite';
    $bp->bp_favorite->slug = BP_FAVORITE_NOTIFIER_SLUG;
    $bp->bp_favorite->notification_callback = 'bp_favorite_format_notifications';//show the notification   
    $bp->active_components[$bp->bp_favorite->id] = $bp->bp_favorite->id;
			
            do_action( 'bp_favorite_setup_globals' );
    }
            add_action( 'bp_setup_globals', 'bp_favorite_setup_globals' );
     

function bp_favorite_format_notifications(  $action, $activity_id, $secondary_item_id, $total_items,$format='string'  ) { 
    $action_checker = explode('_', $action);
    $activity = new BP_Activity_Activity( $activity_id );
	$glue = '';
	$user_names = array();

	$users = find_favorite_involved_persons($activity_id, $action);
	
	$total_user = $count = count($users);

	if($count > 2) {
		$users = array_slice($users, $count - 2);
		$count = $count - 2;
		$glue = ", ";
	} else if($total_user == 2) {
		$glue = __(" and ", "bp-fav");
	}

	foreach((array)$users as $user_id) {
		$user_names[] = bp_core_get_user_displayname($user_id);
		$user_link = bp_core_get_user_domain($user_id);
	}

	if(!empty($user_names)) {
		$favoriting_users = join($glue, $user_names);
	} 
	 
     $url = '<div id="'.$action.'"class="noti-fav"><a href="#" class="delete-fav" title="'.__(" delete ", "bp-fav") .'"  onclick="deleteFavoriteNotification(\''.$action.'\',\''.$activity_id.'\', \''.admin_url( 'admin-ajax.php' ).'\'); return false;">x</a><span class="loader-del"></span></div>';
     $link = favorite_activity_get_permalink( $activity_id );
	switch ( $action ) {
		case 'new_bp_favorite_'.$activity_id:
	    $post_type = __(" this activity :: ", "bp-fav");       
        if( $activity->type == 'new_avatar' )
        $post_type = __(" your avatar ", "bp-fav");
        else if( $activity->type == 'change_background' )
        $post_type = __(" your background ", "bp-fav");
		else if( $activity->type == 'new_member' )
        $post_type = __(" registration ", "bp-fav");
      //else if( $activity->type == 'activity_update' )
     // $post_type = __(" new update ", "bp-fav");

 $pattern = "/<a(.*?)>|<\/a>/si";
      $avatar = bp_core_fetch_avatar( array( 'item_id' => $user_id[0], 'width' => 50, 'height' => 50 ) ); //get the avatar of the latest commenter
      $date_notified = al_notifier_get_date_notified( $activity_id );
      $text = '<div class="bp-favorite"><div class="fav-avatar"><a href="'.$user_link.'">' . $avatar . '</a></div><a class="fav-block" href="'.$link.'"> <div class="fav-message">';
		if( $activity->type != 'new_avatar' && $activity->type != 'new_member'&& $activity->type != 'change_background' ) {
		if($total_user > 2) {
				$text .= $favoriting_users  .  __(" and ", "bp-fav")  .  $count  .  __(" favorited ", "bp-fav") .  $post_type . '<p>' . substr( preg_replace( $pattern, "", stripcslashes( $activity->content ) ), 0, 25 ).'</p>';
			} else {
				$text .= $favoriting_users  .  __(" favorite ", "bp-fav")  .  $post_type   .  substr( preg_replace( $pattern, "", stripcslashes( $activity->content ) ), 0, 25 ) ;
			}
					} else {
	if($total_user > 2) {
				$text .= $favoriting_users  .  __(" and ", "bp-fav") . $count . __("favorited", "bp-fav")  .  $post_type ;
				$text .=__(" favorited ", "bp-fav");
			} else {
				$text .= $favoriting_users  .  $post_type ;
				$text .= __(" favorite ", "bp-fav");
			}
			}
 
   
  
    $text .= '</div><span class="deta-fav"> ' . bp_core_time_since( $date_notified[0] ) . '</span>';
    $text .= '</a>'; 
	$text .= $url ;
    $text .= '</div>';
    
	}
	
	if($format=='string') {
		return apply_filters( 'bp_activity_multiple_new_favorite_notification', '<a href="' . $link. '">' . $text . '</a>' ,$users, $total_user, $count, $glue, $link );
	} else {
		return array(			
			'text' => $text,			
		);
	}
	return false;

}
function al_notifier_get_date_notified( $activity_id ){
   global $bp,$wpdb;
  return $wpdb->get_col($wpdb->prepare("SELECT date_notified FROM {$bp->core->table_name_notifications} WHERE component_name='bp_favorite' AND item_id=%d AND user_id=%d ORDER BY date_notified DESC LIMIT 1",$activity_id,$bp->loggedin_user->id));//get the date notified
}

function find_favorite_involved_persons($activity_id, $action) {
	global $bp,$wpdb;
	$table = $wpdb->prefix . 'bp_notifications';
	return $wpdb->get_col($wpdb->prepare("select DISTINCT(secondary_item_id) from {$table} where item_id=%d and secondary_item_id!=%d and component_action = %s",$activity_id,$bp->loggedin_user->id, $action));
}

function favorite_activity_get_permalink( $activity_id, $activity_obj = false ) {
	global $bp;
	if ( !$activity_obj )
		$activity_obj = new BP_Activity_Activity( $activity_id );                   
		if ( 'activity_comment' == $activity_obj->type )
			$link = bp_get_activity_directory_permalink(). 'p/' . $activity_obj->item_id . '/';
		else
			$link = bp_get_activity_directory_permalink() . 'p/' . $activity_obj->id . '/';
	return apply_filters( 'ac_notifier_activity_get_permalink', $link );
}

function favorite_notifier_remove_notification($activity ,$has_access){
       global $bp;
       if($has_access)		       
	   bp_notifications_delete_notifications_by_item_id( $bp->loggedin_user->id, $activity->id, $bp->bp_favorite->id, 'new_bp_favorite_'.$activity->id );
	}
add_action('bp_activity_screen_single_activity_permalink','favorite_notifier_remove_notification', 10,2);

function favorite_notification( $activity_id){  
                   global $bp; 
				   
	               $activities = bp_activity_get_specific( array( 'activity_ids' => $activity_id) );
				   $author_id = $activities['activities'][0]->user_id;
                   $user_id =  bp_loggedin_user_id();
	// if favoriting own activity, dont send notification
	if( $user_id == $author_id ) {
		return false;
	}
				   if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification( array(
			'user_id'           => $author_id,
			'item_id'           => $activity_id,
			'secondary_item_id' => $user_id,
			'component_name'    => $bp->bp_favorite->id,
			'component_action'  => 'new_bp_favorite_'.$activity_id,
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}				
}
add_action("bp_activity_add_user_favorite","favorite_notification", 10, 2);


function deleteFavoriteNotification($activity,$action){
    global $bp;                 
    $user_id=$bp->loggedin_user->id;
    $item_id=$_POST['item_id'];
    $component_name='bp_favorite';
    $component_action=$_POST['action_id'];        
    bp_core_delete_notifications_by_item_id ($user_id,  $item_id, $component_name, $component_action);     
    die(); 
}	
add_action('wp_ajax_deleteFavoriteNotification', 'deleteFavoriteNotification' );  

