<?php ob_start();
include_once('static_php/header.php');
include_once('static_php/body_start.php');
include_once('static_php/nav.php');

require_once('config.php');
require_once('class/feed.php');
require_once('class/helper.php');

if (!empty($_POST['feed_url'])) 
{

    $feed_url=$_POST['feed_url'];
    // URL Check
    $feed_url_check = preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i', $feed_url);
    if($feed_url_check) 
    {
        $feed = new feed();
        $feed_response_msg=$feed->addNewFeed($feed_url);
        if($feed_response_msg == "Success")
        {
            $url=BASE_URL.'index.php';
            helper::redirect($url);
        }
    
    }else
    {
      $feed_response_msg="Enter a valid URL";
    }
}

?>
<div id="page-wrapper">
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"> Add New Feed </h1>
    </div>
    <!-- /.col-lg-12 -->
</div>
<!-- /.row -->


<?php if($feed_response_msg){
    echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $feed_response_msg . '</div>';

} ?>

<form role="form" method="post" action="add_new.php" >

<div class="form-group">
    <label>RSS URL</label>
    <input class="form-control" required type="url" name="feed_url" placeholder="Enter URL here">
</div>
<button type="submit" class="btn btn-success">Add RSS</button>
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