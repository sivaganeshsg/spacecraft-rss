<?php
class feed
{

     private $db;

     /**
          * __construct function.
          * Initialize db variable
     */
     public function __construct() {
          $this->db = getDB();
     }

     /**
          * __destruct function.
          * Set db variable to null
     */
     public function __destruct() {
          $this->db = null;
     }

     /**
          * getAllFeedList function.
          * Returns all available Feed List in the DB
          * @return "feeds object"
     */
     public function getAllFeedList()
     {
        try{

               $stmt = $this->db->prepare("SELECT * FROM feeds");
               $stmt->execute();
               $data = $stmt->fetchAll(PDO::FETCH_OBJ);
               
               $output_array = array();
               $output_array['status'] = true;
               $output_array['message'] = "Found";
               $output_array['feedlist'] = $data;

               return $output_array;
         }
          catch(PDOException $e) {

               $output_array = array();
               $output_array['status'] = false;
               $output_array['message'] = $e->getMessage();
               $output_array['title'] = "";

               return $output_array;
          }

     }

     /**
          * addNewFeed function.
          * Adds New feed 
          * @param feed_url
          * @return String message
     */
     public function addNewFeed($feed_url)
     {

          $content = file_get_contents($feed_url); 
          // To see if it's a valid XML
          try { 
               $rss = new SimpleXmlElement($content);
               // For DB Operations
               try{
               
                    $stmt = $this->db->prepare("SELECT url FROM feeds WHERE url=:feed_url");  
                    $stmt->bindParam("feed_url", $feed_url,PDO::PARAM_STR);
                    $stmt->execute();
                    $count=$stmt->rowCount();

                    // To check if the URL already exists or not
                    if($count<1)
                    {

                         $feed_title = $rss->channel->title;

                         $stmt = $this->db->prepare("INSERT INTO feeds(title, url) VALUES (:title, :feed_url)");  
                         $stmt->bindParam("title", $feed_title,PDO::PARAM_STR);
                         $stmt->bindParam("feed_url", $feed_url,PDO::PARAM_STR);
                         $stmt->execute();

                         $feed_id=$this->db->lastInsertId();
                         
                         $this->getFeedItemsFromRSS($feed_url, $feed_id, true);

                         $return_msg = "Success";
                    }
                    else
                    {
                        $return_msg = "RSS URL already exists";
                    }
                    
                    return $return_msg;
                   
               } 
               catch(PDOException $e) {
                    return $e->getMessage();
               }

          }
          catch(Exception $e){ 
               return $e->getMessage();
          }
     }

     /**
          * getFeedItemsFromRSS function.
          *  Fetch and insert all new feed items.
          * @param feed_url, $feed_id, $new_feed ($new_feed - bool - either new feed or refresh mechanism)
          * @return String message
     */
     public function getFeedItemsFromRSS($feed_url, $feed_id, $new_feed = true)
     {

          $content = file_get_contents($feed_url); 
          try { 

               $rss = new SimpleXmlElement($content); 

               try{

                    $rss_data = json_decode(json_encode((array)$rss), TRUE);
                    $feed_item_array = $rss_data['item'];
                    $feed_item_array = array_reverse($feed_item_array);


                    if(!$new_feed){
                         // Get the *last* stored Feed Item
                         $stmt = $this->db->prepare("SELECT * FROM feed_contents WHERE feed_id=:feed_id ORDER BY id DESC LIMIT 1");
                         $stmt->bindParam("feed_id", $feed_id, PDO::PARAM_INT);
                         $stmt->execute();
                         $feed_last_content = $stmt->fetch(PDO::FETCH_OBJ);

                         $count=$stmt->rowCount();

                         // Can use array_column for > PHP 5.5
                         // Get all Feed title from the feed(external)
                         $feed_titles_array = array_map(function($element) {
                             return $element['title'];
                           }, $feed_item_array);

                         // Compare the last stored from DB to feed_titles_array to save new content
                         $key_element = array_search($feed_last_content->title, $feed_titles_array);

                         // if found, remove old content.
                         if($key_element){
                              $feed_item_array = array_slice($feed_item_array, $key_element+1);
                         }elseif($count){
                              // If the last saved item is not in the fetched list, then the DB feed content is outdated. So remove old contents in DB.
                              $this->deleteFeedContent($feed_id);
                         }
                    }

                    /*
                    // Insert by single row. Large number of SQL queries
                    foreach($feed_item_array as $each_item) {
                              
                         $stmt = $this->db->prepare("INSERT INTO feed_contents(feed_id, title, description, permalink) VALUES ($feed_id, :title, :description, :permalink)");  
                         $stmt->bindParam("title", $each_item['title'], PDO::PARAM_STR);

                         $description = mb_strimwidth($each_item['description'], 0, 197, '...');
                         $stmt->bindParam("description", $description,PDO::PARAM_STR);
                         $stmt->bindParam("permalink", $each_item['link'],PDO::PARAM_STR);
                         // $stmt->bindParam("publish_date", $each_item->dc:date,PDO::PARAM_STR) ;
                         $stmt->execute();
                    }
                    */

                    // Insertion by batch. Single query to insert all elements.
                    $stmt = $this->db->prepare('INSERT INTO feed_contents (feed_id, title, description, permalink) VALUES (:feed_id, :title, :description, :permalink)');

                    $this->db->beginTransaction();

                    foreach ($feed_item_array as $each_item) {
                         $description = mb_strimwidth($each_item['description'], 0, 397, '...');
                         $stmt->execute([
                            ':feed_id' => $feed_id,
                            ':title' => $each_item['title'],
                            ':description' => $description,
                            ':permalink' => $each_item['link'],
                         ]);
                    }

                    $this->db->commit();

                    // update last_updated timeframe.
                    $stmt=$this->db->prepare("UPDATE feeds SET last_updated = NOW() WHERE id = $feed_id");
                    $stmt->execute();

                    return "Success";
                    
                   
               }
               catch(PDOException $e) {
                    return $e->getMessage();
               }

          }
          catch(Exception $e){ 
               return $e->getMessage();
          }
          
     }

     /**
          * getFeedsForASite function.
          * Get feed Content from DB
          * @param feed_id
          * @return output_array with relevant details
     */
     public function getFeedsForASite($feed_id)
     {
        try{
               $stmt = $this->db->prepare("SELECT * FROM feeds WHERE id=:feed_id");
               $stmt->bindParam("feed_id", $feed_id, PDO::PARAM_INT);
               $stmt->execute();
               $count=$stmt->rowCount();
               $data = $stmt->fetch(PDO::FETCH_OBJ);

               $output_array = array();

               if($count)
               {
                    $stmt = $this->db->prepare("SELECT * FROM feed_contents WHERE feed_id=:feed_id ORDER BY id DESC LIMIT 20");
                    $stmt->bindParam("feed_id", $feed_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $feed_content_data = $stmt->fetchAll(PDO::FETCH_OBJ);

                    $output_array['status'] = true;
                    $output_array['message'] = "Found";
                    $output_array['title'] = $data->title;
                    $output_array['url'] = $data->url;
                    $output_array['last_updated'] = $data->last_updated;
                    $output_array['feed_content_data'] = $feed_content_data;
               }else{

                    $output_array['status'] = false;
                    $output_array['message'] = "Feed not Found";
                    $output_array['data'] = "";
                    
               }

               return $output_array;

         }
         catch(PDOException $e) {
               $output_array['status'] = false;
               $output_array['message'] = $e->getMessage();
               $output_array['data'] = "";
               return $output_array;
          }

     }

     /**
          * deleteFeeds function.
          * Removes the Feeds from DB
          * @param feed_id
          * @return output_array with relevant details
     */
     public function deleteFeeds($feed_id)
     {
          $output_array = array();
          try{

               $this->deleteFeedContent($feed_id);

               $stmt = $this->db->prepare("DELETE FROM feeds WHERE id= :feed_id");
               $stmt->bindParam(':feed_id', $feed_id);
               $stmt->execute();

               $output_array['status'] = true;
               $output_array['message'] = "Feed Deleted Successfully";

               return $output_array;
          }
          catch(PDOException $e) {

               $output_array['status'] = false;
               $output_array['message'] = $e->getMessage();

               return $output_array;
          }

     }

     /**
          * deleteFeedContent function.
          * Remove Feed Content from feed_contents table
          * @param feed_id
          * @return String message
     */
     public function deleteFeedContent($feed_id)
     {
          
          try{

               // Delete content too
               $stmt = $this->db->prepare("DELETE FROM feed_contents WHERE feed_id= :feed_id");
               $stmt->bindParam(':feed_id', $feed_id);
               $stmt->execute();

               return "Success";
          }
          catch(PDOException $e) {
               return $e->getMessage();
          }

     }

     /**
          * changeFeedURL function.
          * Change feed URL
          * @param feed_id, feed_url
          * @return String message
     */
     public function changeFeedURL($feed_id, $feed_url)
     {

          $content = file_get_contents($feed_url); 
          try { 

               $rss = new SimpleXmlElement($content); 

               try{

                    $stmt=$this->db->prepare("UPDATE feeds SET url = :feed_url WHERE id = :feed_id");
                    $stmt->bindParam(':feed_url', $feed_url);
                    $stmt->bindParam(':feed_id', $feed_id);
                    $stmt->execute();

                    return "Success";
                   
               } 
               catch(PDOException $e) {
                    return $e->getMessage();
               }
          }
          catch(Exception $e){ 
               return $e->getMessage();
          }

     }


     /**
          * refreshFeed function.
          * Refresh/Update feed contents
          * @param feed_id
          * @return output_array with relevant details
     */
     public function refreshFeed($feed_id)
     {
        try{
          $stmt = $this->db->prepare("SELECT * FROM feeds WHERE id=:feed_id");
          $stmt->bindParam("feed_id", $feed_id, PDO::PARAM_INT);
          $stmt->execute();
          $count=$stmt->rowCount();

          $output_array = array();

          if($count)
          {
               $data=$stmt->fetch(PDO::FETCH_OBJ);

               $this->getFeedItemsFromRSS($data->url, $feed_id, false);

               $output_array['status'] = true;
               $output_array['message'] = "Refreshed the feed";
          }else{
               $output_array['status'] = false;
               $output_array['message'] = "Unable to find the feed";
               
          }

          return $output_array;

         }
          catch(PDOException $e) {
               return $e->getMessage();
          }

     }

     /**
          * *NOT USING*
          * refreshFeedOld function.
          * Refresh/Update feed contents - *Simple logic - Delete all old content and save all new* 
          * @param feed_id
          * @return output_array with relevant details
     */
     public function refreshFeedOld($feed_id)
     {
        try{
          $stmt = $this->db->prepare("SELECT * FROM feeds WHERE id=:feed_id");
          $stmt->bindParam("feed_id", $feed_id, PDO::PARAM_INT);
          $stmt->execute();
          $count=$stmt->rowCount();

          $output_array = array();
          
          if($count)
          {
               $data=$stmt->fetch(PDO::FETCH_OBJ);

               //Delete all old content and save all new feeds
               $this->deleteFeedContent($feed_id);
               $this->getFeedItemsFromRSS($data->url, $feed_id, false);

               $output_array['status'] = true;
               $output_array['message'] = "Refreshed the feed";
          }else{
               $output_array['status'] = false;
               $output_array['message'] = "Unable to refresh the feed";
               
          }

         }
          catch(PDOException $e) {
               $output_array['status'] = false;
               $output_array['message'] = $e->getMessage();
               return $output_array;
          }

     }

}
?>