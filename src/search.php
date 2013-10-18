<?

function GetBetween($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function remove_nbsp($src) {
    return str_replace("&nbsp;", "", $src);
}

include("simple_html_dom.php");
include("workflows.php");

error_reporting(0);

$w = new Workflows();
$home = $w->home();
$path = $w->path();

$host = 'addic7ed.com'; 
$port = 80; 
$waitTimeoutInSeconds = 5; 

if($fp = fsockopen($host,$port,$errCode,$errStr,$waitTimeoutInSeconds))
{   

      if(!$argv[1]) // Automatic search
      {

        $res = exec('osascript -e "tell application \"VLC\" to name of current item"');

        if (!$res) { 

          $script = "mplayerx.scpt";
          $res = exec("osascript '$script' 2>&1", $output, $error);  
          // echo "res: " . $res . "\n\n";
          // echo "output: " . $output . "\n\n";
          // echo "error: " . $error . "\n\n";

          if($res && !$error)
            $app = "MPlayerX";
          else
            {
              $w->result( 'alfred', 'alfredapp', "Can't detect any active players.", "Video players supported: VLC, MPlayerX", 'fileicon:/Applications/Alfred.app', 'yes', 'Alfredapp' );
              echo $w->toxml();
              die();
            }
        }
        else
        {
          $app = "VLC";
        }

        // // exec("osascript -e 'set the clipboard to \"asdasdciao\"' ");
        $preliminary = $res;
        $fileName = str_replace(" ", "_", $res);

        // $friendly_query = str_replace("search", replace, subject)

        if( stristr($preliminary, ".S") || stristr($preliminary, ".s") ) // IT'S A FUCKING RELEASE NAME (I hope)
        {

          $serie_s_e = explode(".", $preliminary);
          $serie = $serie_s_e[0];

          $s_e = $serie_s_e[1];
          $s_e = explode("E", $s_e);

          $s = str_replace("S", "", $s_e[0]);
          $e = $s_e[1];

          $query =  utf8_encode(urlencode( $serie . ' ' . $s . 'x' . $e));

        }
        else // It's a clean name
        {

          preg_match('/^\D*(?=\d)/', $preliminary, $m);
          $firstNumberPos =  strlen($m[0]);
          if (!is_numeric( $preliminary[$firstNumberPos+1] )) $preliminary[$firstNumberPos-1] = "0";

          $preliminary = str_replace(".mp4", "", $preliminary);
          $preliminary = str_replace(".avi", "", $preliminary);
          $preliminary = str_replace(".mkv", "", $preliminary);
          $preliminary = str_replace(".mkv", "", $preliminary);
          $preliminary = str_replace("-", "", $preliminary);

          $query = utf8_encode(urlencode($preliminary));
   
        }

      } // END Automatic search

      else // Manual search
      {
          $serie = $argv[2];
          $s_e = $argv[3];


          $query =  utf8_encode(urlencode( $serie . ' ' . $s_e));

          $app = "VLC";
          $fileName = str_replace(" ", "_", $serie.$s_e);

          //exec("osascript -e 'set the clipboard to \"".$serie."\"' ");

      }

      $url = "https://www.google.com/search?q=site%3Aaddic7ed.com+inurl%3Aserie+-inurl%3Aarchives+-inurl%3Ablog+$query";

      //echo $url;
      //exec("osascript -e 'set the clipboard to \"".$url."\"' ");

      $html1 = file_get_html( $url );
      $found = FALSE;
      foreach($html1->find('h3[class="r"]') as $element)
      {
      	$addic7ed_link = GetBetween($element, 'href="/url?q=', '"') . '<br>'; 
      	if($addic7ed_link) {         
          $found = TRUE;
          break;
        }
      }
      if(!$found) 
        $w->result( 'alfred', 'alfredapp', "No subtitles found (on Google).", "", 'fileicon:/Applications/Alfred.app', 'yes', 'Alfredapp' );

      //exec("osascript -e 'set the clipboard to \"".$url."\"' ");

      $html2 = file_get_html( $addic7ed_link );

      $found = FALSE;
      foreach($html2->find('div[id=container95m]') as $element)
      {
         $found = TRUE;

         $hreff = $element->find('a[class=buttonDownload]');
         $finalLink =  "http://www.addic7ed.com". $hreff[0]->href;

         $releaser = $element->find('td[class=NewsTitle]');
         $releaser = trim(remove_nbsp($releaser[0]->plaintext));

         $language = $element->find('td[class=language]');
         $lanaguage = $language[0];
         $language = trim(remove_nbsp($language[0]->plaintext));

         //echo();

         $notes = $element->find('td[class=newsDate]');
         $notes = trim(remove_nbsp($notes[0]->plaintext));

         if($hreff) 
            $w->result( 'alfred', $app."|".$releaser."|".$finalLink."|".$fileName, $language . " - " . $releaser, $notes, 'fileicon:'.$path.'/icon.png', 'yes', 'Alfredapp' );
      }
      if(!$found) 
        $w->result( 'alfred', 'alfredapp', "No subtitles found (on addic7ed).", "", 'fileicon:/Applications/Alfred.app', 'yes', 'Alfredapp' );

    // exec("osascript -e 'tell application \"VLC\" to open \"$dl_locale\"'");

} else {

    $w->result( 'alfred', 'alfredapp', "Either your Internet connection or Addic7ed are down.", "", 'fileicon:/Applications/Alfred.app', 'yes', 'Alfredapp' );

} 
      //$w->result( 'alfred', 'alfredapp', "Addic7ed appears to be down.", "", 'fileicon:/Applications/Alfred.app', 'yes', 'Alfredapp' );

echo $w->toxml();
