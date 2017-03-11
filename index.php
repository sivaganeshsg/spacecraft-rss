<?php 
include_once('static_php/header.php');
include_once('static_php/body_start.php');
include_once('static_php/nav.php');

require_once('config.php');
require_once('class/feed.php');
$feed = new feed();
$feedlist_output=$feed->getAllFeedList();

?>
<div id="page-wrapper">
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"> Feeds </h1>
    </div>
    <!-- /.col-lg-12 -->
</div>
<!-- /.row -->

<?php if($_GET['show_message']){
    echo '<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>' . $_GET['show_message'] . '</div>';

} ?>

<table class="table table-hover table-striped">
<thead>
    <tr>
        <th>Title</th>
        <th>URL</th>
        <th>Last Updated</th>
        <th>View</th>
        <th>Edit/Delete</th>
        <th>Refresh</th>
    </tr>
</thead>
<tbody>
    <?php

        foreach($feedlist_output['feedlist'] as $each_feed) {
            echo "<tr>";
            echo "<td>" . "<a href='".BASE_URL."view_feed.php?feed_id=$each_feed->id'>" . $each_feed->title .  "</a>" .  "</td>";
            echo "<td>" . "<a href='$each_feed->url' target='_blank'>" . $each_feed->url . "</a>" .  "</td>";
            echo "<td>" . date("d-M-Y H:i:s", strtotime($each_feed->last_updated)) .  "</td>";
            echo "<td>" . "<a href='".BASE_URL."view_feed.php?feed_id=$each_feed->id'>View</a>" .  "</td>";
            echo "<td>" . "<a href='".BASE_URL."edit_feed.php?feed_id=$each_feed->id'>Edit</a> / <a href='".BASE_URL."edit_feed.php?feed_id=$each_feed->id&delete=yes'>Delete</a>" .  "</td>";
            echo "<td>" . "<a href='".BASE_URL."edit_feed.php?feed_id=$each_feed->id&refresh=yes'>Refresh</a>" .  "</td>";
            echo "</tr>";
        }
    ?>
</tbody>
</table>         


</div>
<!-- /#page-wrapper -->
</div>
<!-- /#wrapper -->

<?php
include_once('static_php/footer_js.php');
include_once('static_php/body_end.php');
?>