function deleteFavoriteNotification(action_id, item_id, adminUrl){
    jQuery('#'+action_id).children(".bp-favorite").html("");
    jQuery('#'+action_id ).children(".loader-del").show(); 

    jQuery.ajax({
        type: 'post',
        url: adminUrl,
        data: { action: "deleteFavoriteNotification", action_id:action_id, item_id:item_id },
        success:
        function(data) {
        	jQuery('#'+action_id).parent().hide();
        	jQuery('#ab-pending-notifications').html(jQuery('#ab-pending-notifications').html() - 1);
        }
     });  
}