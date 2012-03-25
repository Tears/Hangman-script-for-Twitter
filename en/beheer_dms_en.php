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
	//$starter = "username"; # THIS IS A DEV VAR, ONLY USE IT WHEN TESTING THIS WITHOUT TWITTER
	//$text_dm = "@username -bloementuin-"; # SAME STORY AS ABOVE
	//$date_dm = "Tue Jun 22 17:48:26 +0000 2010"; # SAME STORY AS ABOVE
	mysql_query("SELECT * FROM `tmp_beheer` WHERE `type` = '1' AND `date` = '$date_dm'");
	if(mysql_affected_rows() != 0)
		{
		echo "[Error #01] This DM has already been processed and will be ignored.<br />";
		}else{
		mysql_query("INSERT INTO `tmp_beheer` (`type`, `date`) VALUES ('1', '$date_dm')");
	echo "[1] DM found, created at $date_dm by $starter. The content of this DM is $text_dm. <br />";
	if(preg_match("/-/i", $text_dm))
		{
		echo "[2] DM seems to be valid.<br />";
		$tegen = explode("-", $text_dm);
		$tegen_goed = trim($tegen[0]);
		$tegen_beter = str_replace("@", "", $tegen_goed);
		echo "\$tegen_beter = $tegen_beter <br />";
		mysql_query("SELECT * FROM `potjesenwoorden` WHERE `active` = '1' AND `tegen` = '$tegen_beter'");
		if(mysql_affected_rows() != 0)
			{
			echo "[Error #02] Opponent is already playing a game.<br />";
			$error = true;
			$privatemsg = "@{$tegen_beter} is already playing a game. Wait until that game has been ended and try again.";
			}else{
			$woord = strtolower(trim($tegen[1]));
			$time_started = date("G:i:s, d-m-Y");
			echo "[3] The word is $woord and the game has been started at $time_started. <br />";
			mysql_query("INSERT INTO `potjesenwoorden` (`van`, `tegen`, `woord`, `active`, `pogingen`, `time_started`) VALUES ('$starter', '$tegen_beter', '$woord', '1', '10', '$time_started')");
			$privatemsg = "The game against @{$tegen_beter} has been started! He or she has 10 attempts to guess '$woord'. Thanks for playing and have fun!";
			$al = strlen($woord);
			$mention = "@{$tegen_beter} someone started a Hangman game against you with a word with $al characters! Please refer to http://bit.ly/GQsFeg";
			
			}
		}else{
		echo "[Error #03] The DM is invalid. <br />";
		$error = true;
		$privatemsg = "Something went wrong! Please read http://bit.ly/GQsFeg and try again.";
		}
		$tweet->post('direct_messages/new',array('screen_name' => $starter, 'text' => $privatemsg));
		if(!$error)
			{
			$tweet->post('statuses/update',array('status' => $mention));
			echo "[4] No error found so we've sent the mentions.<br />";
			}
		}
	}
	
			
	
