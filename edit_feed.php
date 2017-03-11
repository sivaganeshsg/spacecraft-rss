<?php ob_start();
include_once('static_php/header.php');
include_once('static_php/body_start.php');
include_once('static_php/nav.php');

require_once('config.php');
require_once('class/feed.php');
require_once('class/helper.php');

$feed = new feed();

if (!empty($_GET['feed_id'])) {

    $feed_id=$_GET['feed_id'];
    

    if($_GET['delete'] == "yes"){

        $feed_response=$feed->deleteFeeds($feed_id);
        if($feed_response['status']){
            $url=BASE_URL.'index.php?show_message='. $feed_response['message'];
            helper::redirect($url);
        }else{
            $feed_response_msg = $feed_response['message'];
        }
    }

    if($_GET['refresh'] == "yes"){

        // Check for feed ID
        $feed_response=$feed->refreshFeed($feed_id);
        if($feed_response['message']){
            $url=BASE_URL.'index.php?show_message='. $feed_response['message'];
            helper::redirect($url);
        }
    }

    $feed_response=$feed->getFeedsForASite($feed_id);
    if(!$feed_response['status']){
        $url=BASE_URL.'index.php?show_message='. $feed_response['message'];
        helper::redirect($url);
    }
    
}
    
if (!empty($_POST['feed_url']) && !empty($_POST['feed_id'])) {

    $feed_url=$_POST['feed_url'];
    $feed_id=$_POST['feed_id'];
    // URL Check
    $feed_url_check = preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i', $feed_url);
    if($feed_url_check){
        $feed_response_msg=$feed->changeFeedURL($feed_id, $feed_url);
        if($feed_response_msg == "Success")
        {
            $url=BASE_URL.'index.php?show_message=Successfully updated';
            helper::redirect($url);
        }else{
            $url=BASE_URL.'edit_feed.php?feed_id='.$feed_id.'&show_message='.$feed_response_msg;
            helper::redirect($url);
        }
    
    }else{
      $feed_response_msg="Enter a valid URL";
    }

}

?>
<div id="page-wrapper">
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"> Edit Feed URL for <?=$feed_response['title']?></h1>
    </div>
    <!-- /.col-lg-12 -->
</div>
<!-- /.row -->


<?php if($feed_response_msg){
    echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $feed_response_msg . '</div>';

} ?>
<?php if($_GET['show_message']){
    echo '<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_GET['show_message'] . '</div>';

} ?>

<form role="form" method="post" action="edit_feed.php" >

<div class="form-group">
    <label>RSS URL</label>
    <input type="hidden" value="<?=$_GET['feed_id']?>" name="feed_id"></input>
    <input class="form-control" required type="url" value="<?=$feed_response['url']?>" name="feed_url" placeholder="Enter URL here">
</div>
<button type="submit" class="btn btn-success">Edit RSS</button>
</form>

</div>
        <!-- /#page-wrapper -->

</div>
    <!-- /#wrapper -->

<?php
include_once('static_php/footer_js.php');
include_once('static_php/body_end.php');
ob_end();
?>