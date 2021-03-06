<?php
  session_start();
  include 'php/analytics.php';
  require 'php/view/functions.php';
  require 'php/osuApiFunctions.php';
  require 'secure/osu_api_key.php';
  require 'secure/mysql_pass.php';
  require 'secure/admins.php';
  require 'php/navbar.php';

  function URL_exists($url){
   $headers=get_headers($url);
   return stripos($headers[0],"200 OK")?true:false;
 }

  $conn = new mysqli($mySQLservername, $mySQLusername, $mySQLpassword, $mySQLdatabase);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    header("Location:index.php?error=3");
    exit;
  }

  //get replay data
  $replayDATA = getReplayArray($_GET['id'],$conn);
  $beatmapURL = "https://osu.ppy.sh/beatmapsets/".$replayDATA['beatmapSetId'];

  //Check if the replay exist
  if(empty($replayDATA)){
    header("Location:index.php");
  }

  //Check if the user is logged
  if(isset($_SESSION['userId']) && isset($_SESSION['username'])){
    if(strcmp($_SESSION['userId'],$replayDATA['userId']) == 0){
      $isLogged = true;
    }
  }else{
    $isLogged = false;
  }

  $userJSON = getUserJSON($replayDATA['userId'],$osuApiKey);

  $urlRaw = "https://peertube.osureplayviewer.xyz/client/assets/replayList/".$_GET['id']."/".$_GET['id'].".mp4";
  if(URL_exists($urlRaw)){
    $showRaw = true;
    $metaUrl = "https://peertube.osureplayviewer.xyz/client/assets/replayList/".$_GET['id']."/".$_GET['id'].".mp4";
  }else{
    $showRaw = false;
  }

  $osrUrl = "https://peertube.osureplayviewer.xyz/client/assets/replayList/".$_GET['id']."/".base64_decode($replayDATA['OFN']);
  if(URL_exists($osrUrl)){
    $showOsr = true;
    $osuUrl2 = "https://peertube.osureplayviewer.xyz/client/assets/replayList/".$_GET['id']."/".rawurlencode(base64_decode($replayDATA['OFN']));
  }else{
    $showOsr = false;
  }

  $BFN = base64_decode($replayDATA['BFN']);
  $BFN = str_replace(".osz",'', $BFN);
  $BFN = str_replace($replayDATA['beatmapSetId'],'', $BFN);

  $twitterText = 'Come and see my osu! performance on '.$BFN.' - http://osureplayviewer.xyz/view.php?id='.$_GET['id'];
  $twitterURL = '"'."https://twitter.com/intent/tweet?text=".$twitterText.'"';

  $facebookURL = '"'."https://www.facebook.com/plugins/share_button.php?href=".urlencode('http://osureplayviewer.xyz/view.php?id='.$_GET['id'])."&layout=button&size=large&mobile_iframe=true&width=91&height=28&appId".'"';

  $redditURL = '"'."http://www.reddit.com/submit?url=".urlencode('http://osureplayviewer.xyz/view.php?id='.$_GET['id']).'"';
?>

<!DOCTYPE html>
<html>

  <head>
    <!-- Discord metadata -->
    <meta content="osu!replayViewer - osu! replays sharing" property="og:title">
    <meta content=<?php echo '"'."View ".$userJSON[0]['username']." replay".'"'; ?> property="og:description">
    <meta content="osu!replayViewer" property="og:site_name">
    <meta content=<?php echo '"'.'https://b.ppy.sh/thumb/'.$replayDATA['beatmapSetId']."l.jpg".'"'; ?> property='og:image'>

    <?php
      if($showRaw){
        echo '<meta content='.'"'.$metaUrl.'"'.' property="og:video">';
        echo "\n";
      }
    ?>
    <title>osu!replayViewer - View <?php echo $userJSON[0]['username']; ?> replay</title>
    <link rel="icon" type="image/png" href="images/icon.png" />

    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <link rel="stylesheet" type="text/css" href="css/footer.css">
    <link rel="stylesheet" type="text/css" href="css/view.css">
    <link rel="stylesheet" type="text/css" href="css/loader.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.2/modernizr.js"></script>
    <script src="js/loader.js"></script>
    <script src="js/view/modal.js"></script>

    <script src="https://cdn.plyr.io/3.2.4/plyr.js"></script>
    <script src="js/plyr.js "></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.plyr.io/3.2.4/plyr.css">
    <!-- Cookie bar -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/cookie-bar/cookiebar-latest.min.js?theme=flying&tracking=1&always=1&scrolling=1"></script>
  </head>

  <body>
    <div class="loaderCustom"></div>
    <!-- Modal -->
    <div class="modal" id="delete_modal">
      <div class="modal-content">
        <h2>Do you really want to delete this replay ?</h2>
        <h4>The replay link will no longer work after this</h4>
        <form action="php/view/deleteReplay.php" method="post">
          <input type="submit" id="button_yes" value="Yes please !"/>
          <input type="hidden" name="replayId" value=<?php echo '"'.$replayDATA['replayId'].'"' ?>/>
        </form>
        <button id="button_no" onclick="closeModalDelete()">No stop !</button>
      </div>
    </div>

    <div class="modal" id="rerecord_modal">
      <div class="modal-content">
        <h2>Do you really want to re-record this replay ?</h2>
        <h4>This will send the replay to the processing waiting line</h4>
        <form action="php/view/re_record_replay.php" method="post">
          <input type="submit" id="button_yes" value="Yes please !"/>
          <input type="hidden" name="replayId" value=<?php echo '"'.$replayDATA['replayId'].'"' ?>/>
        </form>
        <button id="button_no" onclick="closeModalRerecord()">No stop !</button>
      </div>
    </div>
    <!-- Top navigation bar -->
    <?php showNavbar(); ?>

    <!-- Video -->
    <h1 id="title"><?php echo $BFN;?></h1>
    <h1 id="subtitle"><?php echo $userJSON[0]['username']; ?></h1>

    <div class="first_block">
      <div class="player_container">
      <?php
        if(empty($replayDATA['youtubeId'])){
          echo '<video id="player" controls crossorigin playsinline>';
    			echo "<source src=$urlRaw  type='video/mp4'>";
    			echo '</video>';
        }else{
          echo '<div class="plyr__video-embed" id="player">';
          echo '<iframe sandbox="allow-same-origin allow-scripts" src='.'"'.'https://peertube.osureplayviewer.xyz/videos/embed/'.$replayDATA['youtubeId'].'"'.' frameborder="0" allowfullscreen></iframe>';
          echo '</div>';
        }
      ?>
      </div>
      <!-- Right content block -->
      <div class="right_content">
        <span id="section_title">Visit <?php echo $userJSON[0]['username']; ?> profiles</span>
        <!-- <img id="beatmap_image" src=<?php echo '"'.'https://b.ppy.sh/thumb/'.$replayDATA['beatmapSetId']."l.jpg".'"'; ?> /> -->
        <br>

        <?php generateAccountBlock($replayDATA['userId'],$userJSON[0]['username'],$conn); ?>

        <span id="section_title">Mods</span>
        <?php drawMod($replayDATA['binaryMods']);   ?>

        <span id="section_title">Downloads / Links</span>
        <div id="download_section">
          <a href=<?php echo $beatmapURL; ?> target="_blank"><img src="images/view/download_beatmap.png"/></a>
          <?php
            if($showRaw){
              echo "<a href=$urlRaw target=\"_blank\"><img src=\"images/view/video_source.png\"/></a>";
            }else{
              echo "<img src=\"images/view/video_source.png\" class=\"disabled\"/>";
            }

            if($showOsr){
              echo "<a href=$osuUrl2 target=\"_blank\"><img src=\"images/view/download_replay.png\"/></a>";
            }else{
              echo "<img src=\"images/view/download_replay.png\" class=\"disabled\"/>";
            }

            if(!empty($replayDATA['youtubeId'])){
              $ytLink = "https://peertube.osureplayviewer.xyz/videos/watch/".$replayDATA['youtubeId'];
              echo "<a href=".$ytLink." target=\"_blank\"><img src=\"images/view/peertube_logo.png\"/></a>";
            }else{
              echo "<img src=\"images/view/peertube_logo.png\" class=\"disabled\"/>";
            }
          ?>
          <!-- <a href="#"><img src="images/view/download_skin.png"/> -->
        </div>
      </div>
    </div>

    <div class="bottom_block">
      <span id="section_title">Share</span><br>
      <div id="share_section">
        <a class="twitter-share-button" target="_blank"
        href=<?php echo $twitterURL; ?>>
        <img src="images/twitter_icon.png"/></a>

        <a class="reddit-share-button" target="_blank"
        href=<?php echo $redditURL; ?>>
        <img src="images/reddit_icon.png"/></a>
      </div>
    <br>

    <?php

    if(isset($_SESSION['userId'])){
      if(strcmp($_SESSION['userId'],$userJSON[0]['user_id']) == 0 || isAdmin($_SESSION['userId'])){
        echo'<span id="section_title">Manage the replay</span>
          <div class="third_block">
          <div id="manage_section">
            <a onclick="openModalDelete()"><img src="images/view/delete_replay.png"/></a>';
            require_once 'php/disableUploads.php';
            if(!$disableUploads){
              echo '<a onclick="openModalRerecord()"><img src="images/view/rerecord_replay.png"/></a>';
            }
        echo '</div>
        </div>';
      }
    }
    ?>
  </div>

    <!-- Disqus -->
    <div id="disqus_thread"></div>
      <script>

      /**
      *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
      *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables*/
      /*
      var disqus_config = function () {
      this.page.url = PAGE_URL;  // Replace PAGE_URL with your page's canonical URL variable
      this.page.identifier = PAGE_IDENTIFIER; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
      };
      */
      (function() { // DON'T EDIT BELOW THIS LINE
      var d = document, s = d.createElement('script');
      s.src = 'https://osureplayviewer.disqus.com/embed.js';
      s.setAttribute('data-timestamp', +new Date());
      (d.head || d.body).appendChild(s);
      })();
      </script>
      <noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>

    <!-- Footer -->
    <footer>
      <h3 class="align_center">osu!replayViewer is not affiliated with osu! - All credit to Dean Herbert</h3>
      <div class="footer_img">
        <a href="https://discord.gg/pqvhvxx" title="join us on discord!" target="_blank">
          <img src="images/index/discord_logo.png"/>
        </a>
        <a href="https://osu.ppy.sh/community/forums/topics/697883" target="_blank">
          <img src="images/index/osu forums.png"/>
        </a>
        <a href="https://github.com/codevirtuel/osu-replayViewer-web" target="_blank">
          <img src="images/index/github_logo.png"/>
        </a>
        <a href="https://paypal.me/codevirtuel" target="_blank">
          <img src="images/index/paypal_me.png"/>
        </a>
      </div>

      <div id="created">
        <span> website created by codevirtuel <a href="https://osu.ppy.sh/u/3481725" target="_blank"><img src="images/codevirtuel.jpg"/></a></span>
      </div>
    </footer>
    <script id="dsq-count-scr" src="//osureplayviewer.disqus.com/count.js" async></script>
  </body>

</html>
