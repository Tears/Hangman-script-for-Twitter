<?php
$consumerKey    = '';

$consumerSecret = '';

$oAuthToken     = '';

$oAuthSecret     = '';

include "OAuth.php";

include "twitteroauth.php";

$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthSecret);
$timeline = $tweet->get('direct_messages',array('count' => 10));
date_default_timezone_set("Europe/Amsterdam");


mysql_connect("localhost", "", "");
mysql_select_db("");

foreach($timeline as $dm)
	{
	$starter = $dm->sender->screen_name;
	$text_dm = $dm->text;
	$date_dm = $dm->created_at;
	//$starter = "username"; # dev-variabele dus weghalen.
	//$text_dm = "@username -bloementuin-"; # dev-variabele dus weghalen.
	//$date_dm = "Tue Jun 22 17:48:26 +0000 2010"; # dev-varabele dus weghalen.
	mysql_query("SELECT * FROM `tmp_beheer` WHERE `type` = '1' AND `date` = '$date_dm'");
	if(mysql_affected_rows() != 0)
		{
		echo "deze dm is al behandeld.<br />";
		}else{
		mysql_query("INSERT INTO `tmp_beheer` (`type`, `date`) VALUES ('1', '$date_dm')");
	echo "ah, een dm. gemaakt op $date_dm door $starter. Inhoud is $text_dm. <br />";
	if(preg_match("/-/i", $text_dm))
		{
		echo "er zit een liggend streepje in, mooi.<br />";
		$tegen = explode("-", $text_dm);
		$tegen_goed = trim($tegen[0]);
		$tegen_beter = str_replace("@", "", $tegen_goed);
		echo "\$tegen_beter = $tegen_beter <br />";
		mysql_query("SELECT * FROM `potjesenwoorden` WHERE `active` = '1' AND `tegen` = '$tegen_beter'");
		if(mysql_affected_rows() != 0)
			{
			echo "ah fuck er is al een potje bezig, jammer dan.<br />";
			$error = true;
			$privatemsg = "@{$tegen_beter} is al een spel aan het spelen. Wacht tot dit spel beeindigd is en probeer het dan opnieuw.";
			}else{
			$woord = strtolower(trim($tegen[1]));
			$time_started = date("G:i:s, d-m-Y");
			echo "het woord is $woord en het spelletje wordt gestart om $time_started. <br />";
			mysql_query("INSERT INTO `potjesenwoorden` (`van`, `tegen`, `woord`, `active`, `pogingen`, `time_started`) VALUES ('$starter', '$tegen_beter', '$woord', '1', '10', '$time_started')");
			$privatemsg = "Het spel tegen @{$tegen_beter} is gestart! Hij of zij heeft 10 pogingen om het woord '$woord' te raden. Veel plezier!";
			$al = strlen($woord);
			$mention = "@{$tegen_beter} er is een spelletje galgje tegen je gestart met een woord van $al tekens! Voor informatie check http://ikspeelgalgje.blogspot.com/p/handleiding.html";
			
			}
		}else{
		echo "lolfaal er zit geen streepje in. <br />";
		$error = true;
		$privatemsg = "Er ging iets fout bij het lezen van je DM. Het streepje (-) ontbreekt. Lees de handleiding nogmaals en probeer het opnieuw.";
		}
		$tweet->post('direct_messages/new',array('screen_name' => $starter, 'text' => $privatemsg));
		if(!$error)
			{
			$tweet->post('statuses/update',array('status' => $mention));
			echo "geen error dus alles versturen maar.<br />";
			}
		}
	}
	
			
	
