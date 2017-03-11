<?php ob_start();
include_once('static_php/header.php');
include_once('static_php/body_start.php');
include_once('static_php/nav.php');

require_once('config.php');
require_once('class/feed.php');
require_once('class/helper.php');

if (!empty($_GET['feed_id'])) 
{

    $feed_id=$_GET['feed_id'];
    $feed = new feed();
    $feed_response=$feed->getFeedsForASite($feed_id);
    
    if(!$feed_response['status']){
        $url=BASE_URL.'index.php?show_message='. $feed_response['message'];
        helper::redirect($url);
    }
 

}

?>
<div id="page-wrapper">
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"> Feeds from <?=htmlspecialchars($feed_response['title'])?> </h1>
    </div>
    <!-- /.col-lg-12 -->
</div>
<!-- /.row -->

<div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?=htmlspecialchars($feed_response['title'])?> <i>- Last updated on <?=htmlspecialchars(date("d-M-Y H:i:s", strtotime($feed_response['last_updated'])))?> </i>
                        </div>
                        <!-- .panel-heading -->
                        <div class="panel-body">
                            <div class="panel-group" id="accordion">

                                <?php $collapse_item_number = 1;
                                foreach($feed_response['feed_content_data'] as $each_feed_content) {
                                ?> 

                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?=$collapse_item_number?>" class="collapsed" aria-expanded="false"><?=htmlspecialchars($each_feed_content->title)?></a>
                                        </h4>
                                    </div>
                                    <div id="collapse<?=$collapse_item_number?>" class="panel-collapse collapse" aria-expanded="false" style="height: 0px;">
                                        <div class="panel-body">
                                            <?=htmlspecialchars($each_feed_content->description)?>
                                            <a href="<?=htmlspecialchars($each_feed_content->permalink)?>" target="_blank">Read more</a>

                                        </div>
                                    </div>
                                </div>

                                <?php   
                                $collapse_item_number++; 
                                }
                                ?>
                                
                            </div>
                        </div>
                        <!-- .panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>

</div>
        <!-- /#page-wrapper -->

</div>
    <!-- /#wrapper -->

<?php
include_once('static_php/footer_js.php');
include_once('static_php/body_end.php');
ob_end();
?>