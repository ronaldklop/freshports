<?php
	#
	# $Id: index.php,v 1.5 2012-12-21 18:20:53 dan Exp $
	#
	# Copyright (c) 1998-2006 DVL Software Limited
	#

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/common.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/freshports.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/databaselogin.php');

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/getvalues.php');


	$Debug = 0;

	if (IsSet($_REQUEST['branch'])) {
		$Branch = htmlspecialchars($_REQUEST['branch']);
	} else {
		$Branch = BRANCH_HEAD;
	}

	if ($Debug) echo 'Branch is ' . $Branch . '<br>';

	#
	# If they supply a package name, go for it.
	#
	if (IsSet($_REQUEST['package'])) {
	    if ($Debug) echo "package is specfied on the URL<br>\n";
		$package = pg_escape_string($_REQUEST['package']);
		if ($package != '') {
			require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/packages.php');

			$Packages = new Packages($db);

			$CategoryPort = $Packages->GetCategoryPortFromPackageName($package);
			switch ($CategoryPort) {
				case "0":
					# no such port found
					header('Location: /package.php?package=' . $package . '&notfound');
					exit;

				case "-1":
					# multiple ports have that package name
					# search for them all and let the users decide which one they want
					require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/searches.php');
					$Searches = new Searches($dbh);
					$Redirect = $Searches->GetLink($package, FRESHPORTS_SEARCH_METHOD_Exact, 1);
					header('Location: ' . $Redirect);
					exit;

				default:
					# one port found with that name, show that page.
					header('Location: /' . $CategoryPort . '/');
					exit;
			}
		}
	}
	else
	{
	    if ($Debug) echo "package is not specified on the URL<br>\n";
    }

	freshports_Start($FreshPortsSlogan,
					$FreshPortsName . ' - new ports, applications',
					'FreeBSD, index, applications, ports');
if ($Debug) echo "\$User->id='$User->id'";

function freshports_SummaryForDay($MinusN) {
   $BaseDirectory = "./archives";
   $Now = time();
//   echo "$MinusN<br>\n";
   $File = $BaseDirectory . "/" . date("Y/m/d", $Now - 60*60*24*$MinusN) . ".inc";
//   echo "$File<br>\n";
   if (file_exists($File)) {
      echo '<br><TABLE WIDTH="152" BORDER="1" CELLSPACING="0" CELLPADDING="5">';
      echo '  <TR>';
      echo '<TD bgcolor="' . BACKGROUND_COLOUR . '" height="30"><font color="#FFFFFF" SIZE="+1">';
      echo date("l j M", $Now - 60*60*24*$MinusN);
      echo '</font></TD>';
      echo '       </TR>';
      echo '        <TR>';
      echo '         <TD>';
      include($File);
      echo '   </TD>';
      echo '   </TR>';
      echo '   </TABLE>';
   }
}

echo freshports_MainTable();

$num          = $MaxNumberOfPortsLong;
$days         = $NumberOfDays;
$dailysummary = 7;

if (In_Array('num',          $_GET)) $num          = pg_escape_string($_GET["num"]);
if (In_Array('dailysummary', $_GET)) $dailysummary = pg_escape_string($_GET["dailysummary"]);
if (In_Array('days',         $_GET)) $days         = pg_escape_string($_GET["days"]);

if (Is_Numeric($num)) {
	$MaxNumberOfPortsLong = min($MaxNumberOfPortsLong, max(10, $num));
} else {
	$num = MaxNumberOfPortsLong;
}

if (Is_Numeric($days)) {
	$NumberOfDays = min($NumberOfDays, max(0, $days));
} else {
	$days = $NumberOfDays;
}

if (Is_Numeric($dailysummary)) {
	$dailysummary = min($NumberOfDays, max(0, $dailysummary));
} else {
	unset($dailysummary);
}


if ($db) {
?>
<TR><TD VALIGN="top" WIDTH="100%">

<?php echo freshports_MainContentTable(); ?>

<TR>
<?php
 if (IsSet($_REQUEST['branch'])) {
   $BranchName = htmlspecialchars($_REQUEST['branch']);
 } else {
   $BranchName = BRANCH_HEAD;
 }

 if ( $BranchName == BRANCH_HEAD) {
   echo freshports_PageBannerText("$MaxNumberOfPortsLong most recent commits", 3);
 } else {
   echo freshports_PageBannerText("Commits from the $Branch branch", 3);
 }
 
?>
        <? //echo ($StartAt + 1) . " - " . ($StartAt + $MaxNumberOfPortsLong) ?>
</TR>
<TR><TD>
<p><?php echo EVERYTHING; ?>

<?php
	if ($ShowAds && $BannerAd) {
		echo "</td></tr>\n<tr><td>\n<CENTER>\n";
		echo Ad_728x90();
		echo "</CENTER>\n\n";
	}
?>


</TD></TR>
<?php

	$UseCache = FALSE;

	DEFINE('CACHEFILE', PAGES_DIRECTORY . '/index.html');

	if ($User->id == '') {
		if (file_exists(CACHEFILE) && is_readable(CACHEFILE)) {
			$UseCache = TRUE;
		}
	}

	if ($UseCache) {
		readfile(CACHEFILE);
	} else {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/commits.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/display_commit.php');

		$LatestCommits = new Commits($db);
		$LatestCommits->SetBranch($Branch);
		$LatestCommits->FetchLimit(date('Y-m-d'), isset($User) ? $User->id : null, 100);
		
		$DisplayCommit = new DisplayCommit($db, $LatestCommits->LocalResult);
		$DisplayCommit->ShowLinkToSanityTestFailure = true;
		$RetVal = $DisplayCommit->CreateHTML();

		echo $DisplayCommit->HTML;
	}

}

?>
</TABLE>
</TD>
  <TD VALIGN="top" WIDTH="*" ALIGN="center">
   <? echo freshports_SideBar(); ?>

<BR>
<?

	if ($dailysummary) {
		for ($i = 0; $i < $dailysummary; $i++) {
			freshports_SummaryForDay($i);
		}
	} else {
		if ($NumberOfDays) {
			$Today = time();
			echo '
<TABLE WIDTH="155" BORDER="1" CELLSPACING="0" CELLPADDING="5">
	<TR>
		<TD BGCOLOR="' . BACKGROUND_COLOUR . '" height="30"><FONT COLOR="#FFFFFF"><BIG><B>Previous days</B></BIG></FONT></TD>
	</TR>
	<TR><TD>
';
			for ($i = 1; $i <= $NumberOfDays; $i++) {
				echo freshports_LinkToDate($Today - $i * 86400) . "<br>\n";
			}
			echo '
	</TD></TR>
</TABLE>

';
		}
	}
?>
 </TD>
</TR>
</TABLE>

<BR>

<?php
define('RELATIVE_DATE_24HOURS', 24 * 60 * 60);	# seconds in a day
$Date = date('Y/m/d');
$Yesterday = freshports_LinkToDate(strtotime($Date) - RELATIVE_DATE_24HOURS, "Yesterday's Commits");

echo '&lt; ' . $Yesterday . ' &gt;';
?>

<?
echo freshports_ShowFooter();
?>

</body>
</html>
