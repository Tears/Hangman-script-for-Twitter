<?php
$consumerKey    = ''; # THE CONSUMER KEY FROM YOUR TWITTER APPLICATION

$consumerSecret = ''; # THE CONSUMER SECRET FROM YOUR TWITTER APPLICATION

$oAuthToken     = ''; # THE OAUTH TOKEN FROM YOUR TWITTER APPLICATION

$oAuthSecret     = ''; # THE OAUTH SECRET FROM YOUR TWITTER APPLICATION

include "OAuth.php";

include "twitteroauth.php";

$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthSecret);
$timeline = $tweet->get('statuses/mentions',array('count' => 10));

mysql_connect("localhost", "", "");
mysql_select_db("");

###   EXTRA FUNCTIE    ###
  function strpos_andersom($haystack, $needle, $offset = 0, &$results = array()) {                
        $offset = strpos($haystack, $needle, $offset);
        if($offset === false) {
            return $results;            
        } else {
            $results[] = $offset;
            return strpos_andersom($haystack, $needle, ($offset + 1), $results);
        }
    }
### EIND EXTRA FUNCTIE ###

date_default_timezone_set("Europe/Amsterdam");
foreach($timeline as $mention)
	{
	echo "<br />[0] LOOP STARTED<br />";
	$speler_tegenstander = $mention->user->screen_name;
	$mention_date = $mention->created_at;
	mysql_query("SELECT * FROM `tmp_beheer` WHERE `type` = '0' AND `date` = '$mention_date'");
	if(mysql_affected_rows() != 0)
		{
		echo "<strong>[Error #01] This mention has already been processed and will be ingenored.</strong><br />";
		}else{
		mysql_query("INSERT INTO `tmp_beheer` (`type`, `date`) VALUES ('0', '$mention_date')");
		$tweet->post('friendships/create', array('screen_name' => $speler_tegenstander));
	//$speler_tegenstander = $_GET['speler_tegenstander'];
	# EDIT THE VARIABLE ABOVE TO TEST THIS GAME WITHOUT TWITTER
	echo "[1] The author of this mention is $speler_tegenstander<br />";
	$data = mysql_query("SELECT * FROM `potjesenwoorden` WHERE `tegen` = '$speler_tegenstander' AND `active` = '1'");
	if(mysql_affected_rows() != 0)
		{
		
		echo "[2] The author of this mention is in a game, so we will continue with the validation of the guess.<br />";
		while($data2 = mysql_fetch_array($data))
			{
			$woord = $data2['woord'];
			$pogingen = $data2['pogingen'];
			$tegen = $data2['tegen'];
			$van_speler = $data2['van'];
			}
			echo "[3] The word to guess is $woord, and the opponent has $pogingen  attemts left.<br />";
			$mention1 = $mention->text;
			//$mention1 = $_GET['mention1'];
			# EDIT THE VARIABEL ABOVE TO TEST THE GAME WITHOUT TWITTER
			$last = $mention1[strlen($mention1)-1];
			echo "[4] The last character of this mention is {$last} <br />";
			$pos = strpos($woord, $last);
			echo "[5] The last character of this mention ({$last}) has the {$pos} position in this word.<br />";
			if(preg_match("/\bThis is the word\b/i", $mention1))
						{
						echo "[6] The opponent is guessing the whole word.<br />";
						$woordraw = explode("-", $mention1);
						$woordgoed = trim($woordraw[1]);
						echo "[7] The opponent thinks that $woordgoed is the right word. <br />";
						if($woordgoed == $woord)
							{
							echo "[8] He was right, and he wins the game.<br />";
							$tweetmsg = "@{$speler_tegenstander} Correct! You won with {$pogingen} attempts left! @{$van_speler} lost the game!";
							$time_ended = date("G:i:s, d-m-Y");
							mysql_query("UPDATE `potjesenwoorden` SET `active` = '0' AND `time_ended` = '$time_ended' WHERE `tegen` = '$speler_tegenstander'");
							}else{
							$pogingen_nu = --$pogingen;
							$pogingen_lol = 10 - $pogingen_nu;
							echo "[7] Too bad for the opponent, he wasn't right. He had $pogingen_nu attempts and he now has $pogingen_lol left.<br />";
							mysql_query("UPDATE `potjesenwoorden` SET `pogingen` = '$pogingen_nu' WHERE `tegen` = '$speler_tegenstander'");
							$tweetmsg = "@{$speler_tegenstander} Too bad, $woordgoed wasn't right. You have $pogingen_lol attempt(s) left.";
							}
							}else{
							if($found = strpos_andersom($woord, $last)) {
							        foreach($found as $pos) {
							        	$plaats = ++$pos;
							            $tweetmsg1[] = "@{$speler_tegenstander} The character $last is on the {$plaats} place! Well done!";
								         
								echo "[6] Well done, opponent. The character $last is in the word on the $plaats place.<br />";
									}
								//$tweetmsg = "@{$speler_tegenstander} De letter $last zit in het woord op de $plaats plaats! Goedzo!";
										
							} else {
								if($pogingen == 1)
									{
									$gelukt = "nee";
									echo "[6] Too bad for the opponent, the character wasn't right and it was his last attempt.<br />";
									$tweetmsg = "@{$speler_tegenstander} {$last} is wrong. You have no attempts left. The word was {$woord}. @{$van_speler} is the winner!";
									$time_ended = date("G:i:s, d-m-Y");
									mysql_query("UPDATE `potjesenwoorden` SET `active` = '0' AND  `time_ended` = '$time_ended' WHERE `tegen` = '$speler_tegenstander'");
									}else{
								$pogingen_nu = --$pogingen;
								$pogingen_lol = 10 - $pogingen_nu;
								echo "[6] Too bad for the opponent, he wasn't right. He had $pogingen_nu attempts and he now has $pogingen_lol left.<br />";
								mysql_query("UPDATE `potjesenwoorden` SET `pogingen` = '$pogingen_nu' WHERE `tegen` = '$speler_tegenstander'");
								$tweetmsg = "@{$speler_tegenstander} Too bad, the character $last is not in the word. You have $pogingen_nu attempt(s) left.";
									}
								}	
						}
				if(isset($tweetmsg1))
					{
					foreach($tweetmsg1 as $meerdere_letters)
						{
						$tweet->post('statuses/update', array('status' => $meerdere_letters));
						echo "[SUCCESS] The tweets with this information have been sent. We'll continue with the next tweet.<br />";
						}
					}else{
					$tweet->post('statuses/update',array('status' => $tweetmsg));
					echo "[SUCCESS] The tweet with this information has been sent. We'll continue with the next tweet.<br />";
					}
		}else{
		echo "[Error #02] The author of this mention is NOT playing a game right now, so we will ignore this tweet.<br/ >";
		}
		}
	}		

?>
