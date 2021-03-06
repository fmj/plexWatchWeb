<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>plexWatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="css/plexwatch.css" rel="stylesheet">
	<link href="css/plexwatch-tables.css" rel="stylesheet">
	<link href="css/font-awesome.min.css" rel="stylesheet" >
	<link href="css/xcharts.css" rel="stylesheet" >
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
    </style>

    <!-- touch icons -->
    <link rel="shortcut icon" href="images/favicon.ico">
    <link rel="apple-touch-icon" href="images/icon_iphone.png">
    <link rel="apple-touch-icon" sizes="72x72" href="images/icon_ipad.png">
    <link rel="apple-touch-icon" sizes="114x114" href="images/icon_iphone@2x.png">
	<link rel="apple-touch-icon" sizes="144x144" href="images/icon_ipad@2x.png">
  </head>

  <body>

	
  
	<div class="container">
		    			
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<a href="index.php"><div class="logo"></div></a>
				<ul class="nav">
					
					<li><a href="index.php"><i class="icon-2x icon-home icon-white" data-toggle="tooltip" data-placement="bottom" title="Home" id="home"></i></a></li>
					<li class="active"><a href="history.php"><i class="icon-2x icon-calendar icon-white" data-toggle="tooltip" data-placement="bottom" title="History" id="history"></i></a></li>
					<li><a href="users.php"><i class="icon-2x icon-group icon-white" data-toggle="tooltip" data-placement="bottom" title="Users" id="users"></i></a></li>
					<li><a href="charts.php"><i class="icon-2x icon-bar-chart icon-white" data-toggle="tooltip" data-placement="bottom" title="Charts" id="charts"></i></a></li>
					<li><a href="settings.php"><i class="icon-2x icon-wrench icon-white" data-toggle="tooltip" data-placement="bottom" title="Settings" id="settings"></i></a></li>
					
				</ul>
			</div>
		</div>
    </div>
	<?php
	
	date_default_timezone_set(@date_default_timezone_get());
	
	
	
	echo "<div class='container-fluid'>";
		echo "<div class='row-fluid'>";
			echo "<div class='span12'>";
				echo "<div class='wellbg'>";
					echo "<div class='wellheader'>";
						echo "<div class='dashboard-wellheader'>";
							echo "<h3>Watching History Stats</h3>";
						echo "</div>";
					echo "</div>";
					
					echo "<div class='history-charts-instance'><div class='wellbg'><strong>Hourly Plays</strong><br><figure style='width: 540px; height: 200px;' id='playChartHourly'></figure></div></div>";
					echo "<div class='history-charts-instance'><div class='wellbg'><strong>Daily Plays</strong><br><figure style='width: 540px; height: 200px;' id='playChartDaily'></figure></div></div>";
					echo "<div class='history-charts-instance'><div class='wellbg'><strong>Monthly Plays</strong><br><figure style='width: 540px; height: 200px;' id='playChartMonthly'></figure></div></div>";
				
				echo "</div>";
		echo "</div>";
		echo "<div class='row-fluid'>";
			echo "<div class='span12'>";
				echo "<div class='wellbg'>";
					echo "<div class='wellheader'>";
						echo "<div class='dashboard-wellheader'>";
							echo "<h3>Watching History</h3>";
						echo "</div>";
					echo "</div>";
					
					
					$guisettingsFile = "config/config.php";
					if (file_exists($guisettingsFile)) { 
						require_once(dirname(__FILE__) . '/config/config.php');
					}else{
						header("Location: settings.php");
					}

					
					if ($plexWatch['https'] == 'yes') {
						$plexWatchPmsUrl = "https://".$plexWatch['pmsIp'].":".$plexWatch['pmsHttpsPort']."";
					}else if ($plexWatch['https'] == 'no') {
						$plexWatchPmsUrl = "http://".$plexWatch['pmsIp'].":".$plexWatch['pmsHttpPort']."";
					}
					
					if (!empty($plexWatch['myPlexAuthToken'])) {
						$myPlexAuthToken = $plexWatch['myPlexAuthToken'];
						
					}else{
						$myPlexAuthToken = '';
						
					}
										
					$db = dbconnect();

					if ($plexWatch['globalHistoryGrouping'] == "yes") {
						$plexWatchDbTable = "grouped";
						$numRows = $db->querySingle("SELECT COUNT(*) as count FROM $plexWatchDbTable ");
						$results = $db->query("SELECT title, user, platform, time, stopped, ip_address, xml, paused_counter FROM processed WHERE stopped IS NULL UNION ALL SELECT title, user, platform, time, stopped, ip_address, xml, paused_counter FROM $plexWatchDbTable ORDER BY time DESC") or die ("Failed to access plexWatch database. Please check your settings.");
							
						
					}else if ($plexWatch['globalHistoryGrouping'] == "no") {
						$plexWatchDbTable = "processed";
					
						$numRows = $db->querySingle("SELECT COUNT(*) as count FROM $plexWatchDbTable ");
						$results = $db->query("SELECT title, user, platform, time, stopped, ip_address, xml, paused_counter FROM $plexWatchDbTable ORDER BY time DESC") or die ("Failed to access plexWatch database. Please check settings.");
					}	
						
					$hourlyPlays = $db->query("SELECT strftime('%Y-%m-%d %H', datetime(time, 'unixepoch', 'localtime')) as date, COUNT(title) as count FROM $plexWatchDbTable WHERE datetime(time, 'unixepoch', 'localtime') >= datetime('now', '-24 hours', 'localtime') GROUP BY strftime('%Y-%m-%d %H', datetime(time, 'unixepoch', 'localtime')) ORDER BY date ASC;") or die ("Failed to access plexWatch database. Please check your settings.");
					$hourlyPlaysNum = 0;
					$hourlyPlayFinal = '';
					while ($hourlyPlay = $hourlyPlays->fetchArray()) {
						$hourlyPlaysNum++;
						$hourlyPlayDate[$hourlyPlaysNum] = $hourlyPlay['date'];
						$hourlyPlayCount[$hourlyPlaysNum] = $hourlyPlay['count'];
						$hourlyPlayTotal = "{ \"x\": \"".$hourlyPlayDate[$hourlyPlaysNum]."\", \"y\": ".$hourlyPlayCount[$hourlyPlaysNum]." }, ";
						$hourlyPlayFinal .= $hourlyPlayTotal;
					}
						
							
						
					$dailyPlays = $db->query("SELECT date(time, 'unixepoch','localtime') as date, count(title) as count FROM $plexWatchDbTable GROUP BY date ORDER BY time DESC LIMIT 30") or die ("Failed to access plexWatch database. Please check your settings.");
					$dailyPlaysNum = 0;
					$dailyPlayFinal = '';
					while ($dailyPlay = $dailyPlays->fetchArray()) {
						$dailyPlaysNum++;
						$dailyPlayDate[$dailyPlaysNum] = $dailyPlay['date'];
						$dailyPlayCount[$dailyPlaysNum] = $dailyPlay['count'];
						$dailyPlayTotal = "{ \"x\": \"".$dailyPlayDate[$dailyPlaysNum]."\", \"y\": ".$dailyPlayCount[$dailyPlaysNum]." }, ";
						$dailyPlayFinal .= $dailyPlayTotal;
					}
						
					$monthlyPlays = $db->query("SELECT strftime('%m', datetime(time, 'unixepoch', 'localtime')) as date, COUNT(title) as count FROM $plexWatchDbTable WHERE datetime(time, 'unixepoch', 'localtime') >= datetime('now', '-12 months', 'localtime') GROUP BY strftime('%m', datetime(time, 'unixepoch', 'localtime'))  ORDER BY date DESC LIMIT 6;") or die ("Failed to access plexWatch database. Please check your settings.");
					$monthlyPlaysNum = 0;
					$monthlyPlayFinal = '';
					while ($monthlyPlay = $monthlyPlays->fetchArray()) {
						$monthlyPlaysNum++;
						$monthlyPlayDate[$monthlyPlaysNum] = $monthlyPlay['date'];
						$monthlyPlayCount[$monthlyPlaysNum] = $monthlyPlay['count'];
						$monthlyPlayTotal = "{ \"x\": \"".$monthlyPlayDate[$monthlyPlaysNum]."\", \"y\": ".$monthlyPlayCount[$monthlyPlaysNum]." }, ";
						$monthlyPlayFinal .= $monthlyPlayTotal;
					}	
					
					
					
					if ($numRows < 1) {

					echo "No Results.";

					} else {
					
					echo "<table id='globalHistory' class='display'>";
						echo "<thead>";
							echo "<tr>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Date</th>";
								echo "<th align='left'><i class='icon-sort icon-white'></i> User </th>";
								echo "<th align='left'><i class='icon-sort icon-white'></i> Platform</th>";
								echo "<th align='left'><i class='icon-sort icon-white'></i> IP Address</th>";
								echo "<th align='left'><i class='icon-sort icon-white'></i> Title</th>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Started</th>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Paused</th>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Stopped</th>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Duration</th>";
								echo "<th align='center'><i class='icon-sort icon-white'></i> Completed</th>";
							echo "</tr>";
						echo "</thead>";
						echo "<tbody>";
						while ($row = $results->fetchArray()) {
						
						echo "<tr>";
							if (empty($row['stopped'])) {
											echo "<td class='currentlyWatching' align='center'>Currently watching...</td>";
										}else{
											echo "<td align='center'>".date("m/d/Y",$row['time'])."</td>";
							}
							
							echo "<td align='left'><a href='user.php?user=".$row['user']."'>".FriendlyName($row['user'],$row['platform'])."</td>";
							echo "<td align='left'>".$row['platform']."</td>";

							if (empty($row['ip_address'])) {
								echo "<td align='left'>n/a</td>";

							}else{

								echo "<td align='left'>".$row['ip_address']."</td>";
							}
							$request_url = $row['xml'];
							$xmlfield = simplexml_load_string($request_url) ; 
							$ratingKey = $xmlfield['ratingKey'];
							$type = $xmlfield['type'];
							$duration = $xmlfield['duration'];
							$viewOffset = $xmlfield['viewOffset'];

							if ($type=="movie") {
								echo "<td class='title' align='left'><a href='info.php?id=".$ratingKey."'>".$row['title']."</a></td>";
							}else if ($type=="episode") {
								echo "<td class='title' align='left'><a href='info.php?id=".$ratingKey."'>".$row['title']."</a></td>";
							}else if (!array_key_exists('',$type)) {
								echo "<td class='title' align='left'><a href='".$ratingKey."'>".$row['title']."</a></td>";
							}else{

							}
							
							echo "<td align='center'>".date("g:i a",$row['time'])."</td>";
							
							$paused_time = round(abs($row['paused_counter']) / 60,1);
							echo "<td align='center'>".$paused_time." min</td>";
							
							$stopped_time = date("g:i a",$row['stopped']);
							
							if (empty($row['stopped'])) {								
								echo "<td align='center'>n/a</td>";
							}else{
								echo "<td align='center'>".$stopped_time."</td>";
							}

							$to_time = strtotime(date("m/d/Y g:i a",$row['stopped']));
							$from_time = strtotime(date("m/d/Y g:i a",$row['time']));
							
							$viewed_time = round(abs($to_time - $from_time - $paused_time) / 60,0);
							$viewed_time_length = strlen($viewed_time);
							
							
							
							if ($viewed_time_length == 8) {
								echo "<td align='center'>n/a</td>";
							}else{
								echo "<td align='center'>".$viewed_time. " min</td>";
							}
							
							$percentComplete = ($duration == 0 ? 0 : sprintf("%2d", ($viewOffset / $duration) * 100));
								if ($percentComplete >= 90) {	
								  $percentComplete = 100;    
								}

							echo "<td align='center'><span class='badge badge-warning'>".$percentComplete."%</span></td>";
						echo "</tr>";   
					}
					}
						echo "</tbody>";
					echo "</table>";

					?>
						
				</div>
			</div>
			
		</div>
	</div>			

		<footer>
		
		</footer>
		
    
    
    <!-- javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery-2.0.3.js"></script>
	<script src="js/bootstrap.js"></script>
	<script src="js/jquery.dataTables.js"></script>
	<script src="js/jquery.dataTables.plugin.bootstrap_pagination.js"></script>
	<script src="js/d3.v3.js"></script> 
	<script src="js/xcharts.min.js"></script> 
	
	<script>
		$(document).ready(function() {
			var oTable = $('#globalHistory').dataTable( {
				"bPaginate": true,
				"bLengthChange": true,
				"bFilter": true,
				"bSort": true,
				"bInfo": true,
				"bAutoWidth": true,
				"aaSorting": [[ 0, "desc" ]],
				"bStateSave": true,
				"bSortClasses": false,
				"sPaginationType": "bootstrap"	
			} );
		} );
	</script>
	
	<script>
	$(document).ready(function() {
		$('#home').tooltip();
	});
	$(document).ready(function() {
		$('#history').tooltip();
	});
	$(document).ready(function() {
		$('#users').tooltip();
	});
	$(document).ready(function() {
		$('#charts').tooltip();
	});
	$(document).ready(function() {
		$('#settings').tooltip();
	});
	</script>

	<script>
	var tt = document.createElement('div'),
	  leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
	  topOffset = -35;
	tt.className = 'ex-tooltip';
	document.body.appendChild(tt);

	var data = {
	  "xScale": "ordinal",
	  "yScale": "linear",
	  
	  "main": [
		{
		  "className": ".playChartHourly",
		  "data": [
			<?php echo $hourlyPlayFinal ?>
		  ]
		}
	  ]
	};
	var opts = {
	  "dataFormatX": function (x) { return d3.time.format('%Y-%m-%d %H').parse(x); },
	  "tickFormatX": function (x) { return d3.time.format('%-I:00 %p')(x); },
	  "paddingLeft": ('25'),
	  "paddingRight": ('35'),
	  "paddingTop": ('10'),
	  "tickHintY": ('5'),
	  "mouseover": function (d, i) {
		var pos = $(this).offset();
		$(tt).text(d3.time.format('%-I:00 %p')(d.x) + ': ' + d.y + ' play(s)')
		  .css({top: topOffset + pos.top, left: pos.left + leftOffset})
		  .show();
	  },
	  "mouseout": function (x) {
		$(tt).hide();
	  }
	};
	var myChart = new xChart('line-dotted', data, '#playChartHourly', opts);
	</script>
	
	<script>
	var tt = document.createElement('div'),
	  leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
	  topOffset = -35;
	tt.className = 'ex-tooltip';
	document.body.appendChild(tt);

	var data = {
	  "xScale": "ordinal",
	  "yScale": "linear",
	  "main": [
		{
		  "className": ".playcount",
		  "data": [
			<?php echo $dailyPlayFinal ?>
		  ]
		}
	  ]
	};
	var opts = {
	  "dataFormatX": function (x) { return d3.time.format('%Y-%m-%d').parse(x); },
	  "tickFormatX": function (x) { return d3.time.format('%b %e')(x); },
	  "paddingLeft": ('25'),
	  "paddingRight": ('35'),
	  "paddingTop": ('10'),
	  "tickHintY": ('10'),
	  "mouseover": function (d, i) {
		var pos = $(this).offset();
		$(tt).text(d3.time.format('%b %e')(d.x) + ': ' + d.y + ' play(s)')
		  .css({top: topOffset + pos.top, left: pos.left + leftOffset})
		  .show();
	  },
	  "mouseout": function (x) {
		$(tt).hide();
	  }
	};
	var myChart = new xChart('bar', data, '#playChartDaily', opts);
	</script>

	<script>
	var tt = document.createElement('div'),
	  leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
	  topOffset = -35;
	tt.className = 'ex-tooltip';
	document.body.appendChild(tt);

	var data = {
	  "xScale": "ordinal",
	  "yScale": "linear",
	  "main": [
		{
		  "className": ".playcount",
		  "data": [
			<?php echo $monthlyPlayFinal ?>
		  ]
		}
	  ]
	};
	var opts = {
	  "dataFormatX": function (x) { return d3.time.format('%m').parse(x); },
	  "tickFormatX": function (x) { return d3.time.format('%b')(x); },
	  "paddingLeft": ('25'),
	  "paddingRight": ('35'),
	  "paddingTop": ('10'),
	  "tickHintY": ('10'),
	  "mouseover": function (d, i) {
		var pos = $(this).offset();
		$(tt).text(d3.time.format('%b')(d.x) + ': ' + d.y + ' play(s)')
		  .css({top: topOffset + pos.top, left: pos.left + leftOffset})
		  .show();
	  },
	  "mouseout": function (x) {
		$(tt).hide();
	  }
	};
	var myChart = new xChart('line-dotted', data, '#playChartMonthly', opts);
	</script>
	
	
  </body>
</html>
