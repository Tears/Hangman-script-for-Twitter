<?php
$consumerKey    = '';

$consumerSecret = '';

$oAuthToken     = '';

$oAuthSecret     = '';

include "OAuth.php";

include "twitteroauth.php";

$tweet = new TwitterOAuth($consumerKey, $consumerSecret, $oAuthToken, $oAuthSecret);
$timeline = $tweet->get('statuses/mentions',array('count' => 10));

mysql_connect("localhost", "", "");
mysql_select_db("");

###   EXTRA FUNCTIE    ###
  function strpos_recursive($haystack, $needle, $offset = 0, &$results = array()) {                
        $offset = strpos($haystack, $needle, $offset);
        if($offset === false) {
            return $results;            
        } else {
            $results[] = $offset;
            return strpos_recursive($haystack, $needle, ($offset + 1), $results);
        }
    }
### EIND EXTRA FUNCTIE ###

date_default_timezone_set("Europe/Amsterdam");
foreach($timeline as $mention)
	{
	echo "<br />hee. loop gestart <br />";
	$speler_tegenstander = $mention->user->screen_name;
	$mention_date = $mention->created_at;
	mysql_query("SELECT * FROM `tmp_beheer` WHERE `type` = '0' AND `date` = '$mention_date'");
	if(mysql_affected_rows() != 0)
		{
		echo "deze mention is al behandeld.<br />";
		}else{
		mysql_query("INSERT INTO `tmp_beheer` (`type`, `date`) VALUES ('0', '$mention_date')");
		$tweet->post('friendships/create', array('screen_name' => $speler_tegenstander));
	//$speler_tegenstander = $_GET['speler_tegenstander'];
	echo "mention is van $speler_tegenstander<br />";
	$data = mysql_query("SELECT * FROM `potjesenwoorden` WHERE `tegen` = '$speler_tegenstander' AND `active` = '1'");
	if(mysql_affected_rows() != 0)
		{
		
		echo "oke er is een potje bezig<br />";
		while($data2 = mysql_fetch_array($data))
			{
			$woord = $data2['woord'];
			$pogingen = $data2['pogingen'];
			$tegen = $data2['tegen'];
			$van_speler = $data2['van'];
			}
			echo "woord: $woord, pogingen: $pogingen <br />";
			$mention1 = $mention->text;
			//$mention1 = $_GET['mention1'];
			$last = $mention1[strlen($mention1)-1];
			echo "laatste letter is {$last} <br />";
			$pos = strpos($woord, $last);
			echo "laatste letter ({$last}) staat op de {$pos}e plaats<br />";
			if(preg_match("/\bDit is het woord\b/i", $mention1))
						{
						echo "er wordt een woord geraden <br />";
						$woordraw = explode("-", $mention1);
						$woordgoed = trim($woordraw[1]);
						echo "het gerade woord is $woordgoed <br />";
						if($woordgoed == $woord)
							{
							echo "jeuj het woord is goed!<br />";
							$tweetmsg = "@{$speler_tegenstander} correct! Je hebt gewonnen terwijl je nog {$pogingen} pogingen had! @{$van_speler} is de verliezer";
							$time_ended = date("G:i:s, d-m-Y");
							mysql_query("UPDATE `potjesenwoorden` SET `active` = '0' AND `time_ended` = '$time_ended' WHERE `tegen` = '$speler_tegenstander'");
							}else{
							$pogingen_nu = --$pogingen;
							$pogingen_lol = 10 - $pogingen_nu;
							echo "haha kut voor je, woord niet goed. $pogingen_nu pogingen gehad dus nog $pogingen_lol over<br />";
							mysql_query("UPDATE `potjesenwoorden` SET `pogingen` = '$pogingen_nu' WHERE `tegen` = '$speler_tegenstander'");
							$tweetmsg = "@{$speler_tegenstander} Helaas, dat is niet juist. Je hebt nog $pogingen_lol poging(en) over.";
							}
							}else{
							if($found = strpos_recursive($woord, $last)) {
							        foreach($found as $pos) {
							        	$plaats = ++$pos;
							            $tweetmsg1[] = "@{$speler_tegenstander} De letter $last zit in het woord op de {$plaats}e plaats! Goedzo (:";
								         
								echo "de letter staat op de goede plaats<br />";
									}
								//$tweetmsg = "@{$speler_tegenstander} De letter $last zit in het woord op de $plaats plaats! Goedzo!";
										
							} else {
								if($pogingen == 1)
									{
									$gelukt = "nee";
									echo "laatste poging mislukt ahahahaha loser<br />";
									$tweetmsg = "@{$speler_tegenstander} {$last} is fout. Je hebt je pogingen verspeeld. Het woord was {$woord}. @{$van_speler} is de winnaar!";
									$time_ended = date("G:i:s, d-m-Y");
									mysql_query("UPDATE `potjesenwoorden` SET `active` = '0' AND  `time_ended` = '$time_ended' WHERE `tegen` = '$speler_tegenstander'");
									}else{
								$pogingen_nu = --$pogingen;
								$pogingen_lol = 10 - $pogingen_nu;
								echo "haha kut voor je, letter niet goed. $pogingen_nu pogingen gehad dus nog $pogingen_lol over<br />";
								mysql_query("UPDATE `potjesenwoorden` SET `pogingen` = '$pogingen_nu' WHERE `tegen` = '$speler_tegenstander'");
								$tweetmsg = "@{$speler_tegenstander} Helaas, de letter $last zit niet in het woord. Je hebt nog $pogingen_nu poging(en) over.";
									}
								}	
						}
				if(isset($tweetmsg1))
					{
					foreach($tweetmsg1 as $meerdere_letters)
						{
						$tweet->post('statuses/update', array('status' => $meerdere_letters));
						echo "tweet verzonden yeah<br />";
						}
					}else{
					$tweet->post('statuses/update',array('status' => $tweetmsg));
					echo "tweet verzonden jeuj<br />";
					}
		}else{
		echo "niet in db doei<br/ >";
		}
		}
	}		

?>
