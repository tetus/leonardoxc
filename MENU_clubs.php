<?
//************************************************************************
// Leonardo XC Server, http://leonardo.thenet.gr
//
// Copyright (c) 2004-8 by Andreadakis Manolis
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
// $Id: MENU_clubs.php,v 1.5 2009/03/12 15:13:33 manolis Exp $                                                                 
//
//************************************************************************
?>

<div id="clubDropDownID" class="secondMenuDropLayer"  >
<div class='closeButton closeLayerButton'></div>        
<div class='content'>
<div style='height:4px;'></div>
<table  cellpadding="4" cellspacing="0"><tr><td valign="top">

<?

$num_of_cols=1;
$tblWidth=$num_of_cols*260;

if ($showNacClubSelection ) {
/**
 * PopUp Menu for NACclubs
 * Martin Jursa 22.05.2007
 */
 
 	$thisPageUrl=getLeonardoLink(array('op'=>'useCurrent','nacid'=>'%nacid%','nacclubid'=>'%nacclubid%'));	
	$urlparameters=urlencode($thisPageUrl);
	
	$js="<script type=\"text/javascript\" laguage=\"javascript\"><!--
	function setClub(nacid, nacclubid) {
		var h=screen.availHeight*0.9;
		var w=450;
		var t=screen.availHeight*0.1;
		var l=450;
		var optionstring='height='+Math.round(h);
		optionstring+=',width='+Math.round(w);
		optionstring+=',top='+Math.round(t);
		optionstring+=',left='+Math.round(l);
		optionstring+=',dependent=yes,resizable=yes,scrollbars=yes,status=yes ';
		window.open('$moduleRelPath/GUI_EXT_set_club.php?NAC_ID='+nacid+'&clubID='+nacclubid+'&option=2&params=$urlparameters', '_blank',	optionstring, false);
	}//--></script>";
	
	if (!empty($forceNacId)) {
		$nacids=array($forceNacId);
	}else {
		$nacids=array();
		foreach ($CONF_NAC_list as $nid=>$nacdata) {
			if (!empty($nacdata['use_clubs'])) $nacids[]=$nid;
		}
	}
	$nacnames=array();
	foreach ($nacids as $nid) {
		if ($CONF_NAC_list[$nid]['localLanguage']==$currentlang) {
			$nacnames[$nid]=!empty($CONF_NAC_list[$nid]['localName']) ? $CONF_NAC_list[$nid]['localName'] : 'NAC '.$nid;		
		} else {
			$nacnames[$nid]=!empty($CONF_NAC_list[$nid]['name']) ? $CONF_NAC_list[$nid]['name'] : 'NAC '.$nid;
		}	
	}

?>
<?=$js?>
<table  width="<?=$tblWidth?>" cellpadding="0" cellspacing="0">
<tr>
	<td height=25 colspan=<?=$num_of_cols ?> class="main_text">
      
      <strong><?=_Select_Club?> <?=_OR?></strong>&nbsp;
    	<div class="buttonLink">
			<a style='text-align:center; text-decoration:underline;' href='<?=
					getLeonardoLink(array('op'=>'useCurrent','nacid'=>(!empty($forceNacId) ? $forceNacId:'0'),
					'nacclubid'=>'0' ))	?>'><?=_Display_ALL?></a>
		</div>
	  
	</td>
</tr>
</table>
<table  width="<?=$tblWidth?>" cellpadding="0" cellspacing="0">
<tr>
	<td colspan='<?=$num_of_cols ?>'  class="datesColumn">	
     <ul class='simpleList'>
<? foreach ($nacids as $nid) {
	$nacname=$nacnames[$nid];
	// Manolis 2007/11/06
	// use [nacname] instread of eval to be able to use " at the defines
	$labelSelectNacclub=str_replace('[nacname]',$nacname,_SELECT_NACCLUB);
	
	//if ( $clubsItem['id'] == $clubID ) $a_class="class='boldFont'";
	//		else $a_class="";
	
	// eval('$labelSelectNacclub="'._SELECT_NACCLUB.'";');
	?>
	<li><a href='#' onclick="setClub(<?=$nid?>, <?=($nid==$nacid) ? $nacclubid : '0'?>)"><?=$labelSelectNacclub?></a></li>

<? } ?>
	</ul>
	</td>
</tr>

<?



/* not needed currently
if (!$useNacClubPopUp) {
	require_once(dirname(__FILE__)."/CL_NACclub.php");
	$nacClubs=NACclub::getClubs($nacid, true);
	$nacClubNum=count($nacClubs);
	$num_of_rows=ceil($nacClubNum/$num_of_cols);
	$nacClubNames=array_values($nacClubs);
	$nacClubIds=array_keys($nacClubs);

	$ii=0;
	if ($nacClubNum) {
		for( $r=0;$r<$num_of_rows;$r++) {
			$sortRowClass=($ii%2)?"l_row1":"l_row2";
			$ii++;
			echo "\n\n<tr class='$sortRowClass'>";
			for( $c=0;$c<$num_of_cols;$c++) {
				echo "<td align='left'>";
				//compute which to show
				//echo "c=$c r=$r i=$i<br>";
				$i= $c * $num_of_rows +( $r % $num_of_rows);
				if ($i<$nacClubNum) {
					$nacClubName=$nacClubNames[$i];
					echo "<a href='?name=".$module_name."&nacid=$nacid&nacclubid=".$nacClubIds[$i]."'>$nacClubName</a>\n";
				}
				else echo "&nbsp;";

				echo "</td>";
			}
			echo '</tr>';
		}
	}
}
*/
?>
</table>
<?
} // endif nacclub selection
?>
</td><td valign="top">
<? if (count($clubsList) && $op!='comp') { ?>
<table  width="<?=$tblWidth?>" cellpadding="0" cellspacing="0">
<tr>
	<td height=25 colspan=<?=$num_of_cols ?> class="main_text">
      
      <strong><?=_Select_Club?> <?=_OR?></strong>&nbsp;
    	<div class="buttonLink">
			<a style='text-align:center; text-decoration:underline;' href='<?=
					getLeonardoLink(array('op'=>'useCurrent','clubID'=>'0' ))	?>'><?=_Display_ALL?></a>
		</div>
	  
	</td>
</tr>
</table>
<table  width="<?=$tblWidth?>" cellpadding="0" cellspacing="0" >
<tr>
	<td colspan=<? echo $num_of_cols ; ?> height=8 class="datesColumnHeader" >
    <?=_Clubs_Leagues?>
    </td>
 </tr>
<tr>
	<td colspan=<? echo $num_of_cols ; ?> height=8 class="datesColumn" >
    <ul class='simpleList'>
    <?
	
		foreach( $clubsList as $clubsItem) {
			if ( $clubsItem['id'] == $clubID ) $a_class="class='boldFont'";
			else $a_class="";
			echo "<li $a_class><a $a_class href='".getLeonardoLink(array('op'=>'useCurrent','clubID'=>$clubsItem['id'],'nacclubid'=>'0','nacid'=>'0'))."'>".$clubsItem['desc']."</a></li>\n";
		
	}
	
	?>
    </ul>
    
    </td>
</tr>
</TABLE>
<? } ?>

</td></tr></table>

<div id='filterResultDiv'></div>
</div>
</div>